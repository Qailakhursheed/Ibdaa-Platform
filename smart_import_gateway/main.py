"""
Smart Ingestion Gateway - FastAPI Backend
بوابة الاستيراد الذكية - الخادم الخلفي

هذا النظام يوفر:
1. تحليل ذكي للملفات (Excel/CSV)
2. كشف تلقائي عن صف العناوين
3. تصنيف دلالي للأعمدة
4. معالجة وتنظيف البيانات
"""

from fastapi import FastAPI, File, UploadFile, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import List, Dict, Any, Optional
import pandas as pd
import openpyxl
import io
import re
from datetime import datetime
import tempfile
import os
from pathlib import Path

app = FastAPI(
    title="Smart Ingestion Gateway",
    description="بوابة استيراد ذكية لمنصة إبداع",
    version="1.0.0"
)

# CORS Configuration - السماح للواجهة الأمامية بالاتصال
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # في الإنتاج: حدد النطاقات المسموحة
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# ============================================
# Models - النماذج
# ============================================

class ColumnInfo(BaseModel):
    index: int
    header: str
    type: str
    semantic_guess: str
    confidence: float
    sample_values: List[Any]

class AnalyzeResponse(BaseModel):
    success: bool
    detected_header_row: int
    total_rows: int
    total_data_rows: int
    columns: List[ColumnInfo]
    preview_rows: List[List[Any]]
    file_id: str

class MappingRule(BaseModel):
    source_column: str  # اسم العمود في الملف
    target_field: str   # اسم الحقل في النظام

class ProcessRequest(BaseModel):
    file_id: str
    mapping: List[MappingRule]
    skip_empty: bool = True

class ProcessedRecord(BaseModel):
    data: Dict[str, Any]
    warnings: List[str] = []

class ProcessResponse(BaseModel):
    success: bool
    processed_data: List[ProcessedRecord]
    total_processed: int
    total_skipped: int
    report: str

# ============================================
# Helper Functions - دوال مساعدة
# ============================================

# تخزين مؤقت للملفات (في الإنتاج استخدم Redis أو قاعدة بيانات)
TEMP_FILES = {}

def detect_arabic_keywords(text: str) -> Dict[str, float]:
    """
    كشف الكلمات المفتاحية العربية وإرجاع درجة الثقة
    """
    if not isinstance(text, str):
        return {}
    
    text_lower = text.lower().strip()
    
    patterns = {
        'student_name': [
            'اسم', 'الاسم', 'طالب', 'متدرب', 'name', 'student'
        ],
        'student_email': [
            'بريد', 'ايميل', 'email', 'mail', '@'
        ],
        'student_phone': [
            'هاتف', 'جوال', 'phone', 'mobile', 'tel'
        ],
        'course_title': [
            'دورة', 'كورس', 'برنامج', 'course', 'program'
        ],
        'grade_value': [
            'درجة', 'نتيجة', 'علامة', 'grade', 'score', 'mark'
        ],
        'grade_percent': [
            'نسبة', 'مئوية', 'percent', '%'
        ],
        'governorate': [
            'محافظة', 'المحافظة', 'governorate', 'province'
        ],
        'district': [
            'مديرية', 'منطقة', 'district', 'region'
        ],
        'date': [
            'تاريخ', 'date', 'وقت', 'time'
        ],
        'status': [
            'حالة', 'الحالة', 'status', 'state'
        ],
        'notes': [
            'ملاحظات', 'ملاحظة', 'notes', 'note', 'تعليق'
        ]
    }
    
    matches = {}
    for semantic_type, keywords in patterns.items():
        for keyword in keywords:
            if keyword in text_lower:
                # حساب درجة الثقة بناءً على مدى تطابق الكلمة
                if text_lower == keyword:
                    confidence = 0.95
                elif text_lower.startswith(keyword) or text_lower.endswith(keyword):
                    confidence = 0.85
                else:
                    confidence = 0.70
                
                if semantic_type not in matches or confidence > matches[semantic_type]:
                    matches[semantic_type] = confidence
    
    return matches

def infer_data_type(series: pd.Series) -> str:
    """
    استنتاج نوع البيانات من العينة
    """
    # إزالة القيم الفارغة
    series_clean = series.dropna()
    if len(series_clean) == 0:
        return "empty"
    
    # محاولة تحويل إلى رقم
    try:
        pd.to_numeric(series_clean)
        return "numeric"
    except:
        pass
    
    # محاولة تحويل إلى تاريخ
    try:
        pd.to_datetime(series_clean, errors='raise')
        return "datetime"
    except:
        pass
    
    # البحث عن البريد الإلكتروني
    email_pattern = r'[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}'
    if series_clean.astype(str).str.match(email_pattern).sum() > len(series_clean) * 0.5:
        return "email"
    
    # البحث عن أرقام الهاتف
    phone_pattern = r'[\d\s\-\+\(\)]{8,}'
    if series_clean.astype(str).str.match(phone_pattern).sum() > len(series_clean) * 0.5:
        return "phone"
    
    return "string"

def detect_header_row(df: pd.DataFrame, max_search_rows: int = 50) -> int:
    """
    كشف ذكي عن صف العناوين
    
    الطريقة:
    1. البحث في أول 50 صف
    2. تحليل كل صف وحساب "درجة العنوان"
    3. الصف ذو أعلى درجة هو صف العناوين
    """
    scores = []
    
    for idx in range(min(max_search_rows, len(df))):
        row = df.iloc[idx]
        score = 0
        
        # عدد الخلايا غير الفارغة
        non_empty = row.notna().sum()
        if non_empty < len(row) * 0.5:  # أقل من 50% ممتلئ
            scores.append(0)
            continue
        
        score += non_empty * 2
        
        # طول النص (العناوين عادة قصيرة)
        text_lengths = [len(str(val)) for val in row if pd.notna(val)]
        if text_lengths:
            avg_length = sum(text_lengths) / len(text_lengths)
            if 3 <= avg_length <= 50:
                score += 10
        
        # البحث عن كلمات مفتاحية
        for val in row:
            if pd.notna(val):
                keywords = detect_arabic_keywords(str(val))
                if keywords:
                    score += 15
        
        # تفضيل الصفوف الأولى قليلاً
        if idx < 5:
            score += 3
        
        scores.append(score)
    
    if not scores or max(scores) == 0:
        return 0
    
    return scores.index(max(scores))

def analyze_column_semantics(header: str, data_series: pd.Series, data_type: str) -> tuple:
    """
    تحليل دلالي للعمود وإرجاع (التصنيف، درجة الثقة)
    """
    # أولاً: التحليل بناءً على العنوان
    keyword_matches = detect_arabic_keywords(header)
    
    if keyword_matches:
        best_match = max(keyword_matches.items(), key=lambda x: x[1])
        semantic_type, confidence = best_match
        
        # تعديل الثقة بناءً على توافق نوع البيانات
        if semantic_type == 'grade_value' and data_type != 'numeric':
            confidence *= 0.7
        elif semantic_type == 'student_email' and data_type != 'email':
            confidence *= 0.7
        elif semantic_type == 'student_phone' and data_type != 'phone':
            confidence *= 0.7
        
        return semantic_type, confidence
    
    # ثانياً: التحليل بناءً على نوع البيانات
    if data_type == 'numeric':
        # قد يكون درجة أو عمر أو رقم
        return 'numeric_field', 0.50
    elif data_type == 'email':
        return 'student_email', 0.85
    elif data_type == 'phone':
        return 'student_phone', 0.85
    elif data_type == 'datetime':
        return 'date_field', 0.70
    
    return 'text_field', 0.30

# ============================================
# API Endpoints - نقاط النهاية
# ============================================

@app.get("/")
async def root():
    """الصفحة الرئيسية"""
    return {
        "service": "Smart Ingestion Gateway",
        "version": "1.0.0",
        "status": "running",
        "endpoints": {
            "analyze": "POST /analyze_spreadsheet",
            "process": "POST /process_spreadsheet"
        }
    }

@app.post("/analyze_spreadsheet", response_model=AnalyzeResponse)
async def analyze_spreadsheet(file: UploadFile = File(...)):
    """
    تحليل ملف Excel/CSV واكتشاف البنية
    
    - يكتشف صف العناوين تلقائياً
    - يصنف الأعمدة دلالياً
    - يعيد معاينة للبيانات
    """
    try:
        # قراءة الملف
        contents = await file.read()
        file_extension = Path(file.filename).suffix.lower()
        
        # تحديد طريقة القراءة بناءً على نوع الملف
        if file_extension in ['.xlsx', '.xls']:
            if file_extension == '.xlsx':
                df = pd.read_excel(io.BytesIO(contents), engine='openpyxl', header=None)
            else:
                df = pd.read_excel(io.BytesIO(contents), engine='xlrd', header=None)
        elif file_extension == '.csv':
            df = pd.read_csv(io.BytesIO(contents), header=None, encoding='utf-8-sig')
        else:
            raise HTTPException(status_code=400, detail="نوع الملف غير مدعوم. استخدم Excel أو CSV")
        
        if df.empty:
            raise HTTPException(status_code=400, detail="الملف فارغ")
        
        # كشف صف العناوين
        header_row_idx = detect_header_row(df)
        
        # إعادة قراءة البيانات مع تحديد صف العناوين
        if file_extension in ['.xlsx', '.xls']:
            if file_extension == '.xlsx':
                df = pd.read_excel(io.BytesIO(contents), engine='openpyxl', header=header_row_idx)
            else:
                df = pd.read_excel(io.BytesIO(contents), engine='xlrd', header=header_row_idx)
        else:
            df = pd.read_csv(io.BytesIO(contents), header=header_row_idx, encoding='utf-8-sig')
        
        # تحليل الأعمدة
        columns_info = []
        for idx, col_name in enumerate(df.columns):
            col_data = df[col_name]
            
            # استنتاج نوع البيانات
            data_type = infer_data_type(col_data)
            
            # التحليل الدلالي
            semantic_type, confidence = analyze_column_semantics(
                str(col_name), 
                col_data, 
                data_type
            )
            
            # أخذ عينة من القيم
            sample_values = col_data.head(3).fillna("").tolist()
            
            columns_info.append(ColumnInfo(
                index=idx,
                header=str(col_name),
                type=data_type,
                semantic_guess=semantic_type,
                confidence=round(confidence, 2),
                sample_values=sample_values
            ))
        
        # معاينة البيانات (أول 5 صفوف)
        preview_rows = df.head(5).fillna("").values.tolist()
        
        # حفظ الملف مؤقتاً للمعالجة اللاحقة
        file_id = f"file_{datetime.now().strftime('%Y%m%d_%H%M%S_%f')}"
        TEMP_FILES[file_id] = {
            'content': contents,
            'filename': file.filename,
            'header_row': header_row_idx,
            'uploaded_at': datetime.now()
        }
        
        return AnalyzeResponse(
            success=True,
            detected_header_row=header_row_idx,
            total_rows=len(df) + header_row_idx + 1,
            total_data_rows=len(df),
            columns=columns_info,
            preview_rows=preview_rows,
            file_id=file_id
        )
        
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"فشل تحليل الملف: {str(e)}")

@app.post("/process_spreadsheet", response_model=ProcessResponse)
async def process_spreadsheet(request: ProcessRequest):
    """
    معالجة الملف وتطبيق الربط (Mapping)
    
    - يطبق قواعد الربط المحددة
    - ينظف البيانات
    - يعيد البيانات جاهزة للإدخال
    """
    try:
        # التحقق من وجود الملف
        if request.file_id not in TEMP_FILES:
            raise HTTPException(status_code=404, detail="الملف غير موجود أو انتهت صلاحيته")
        
        file_info = TEMP_FILES[request.file_id]
        contents = file_info['content']
        header_row = file_info['header_row']
        
        # قراءة الملف
        file_extension = Path(file_info['filename']).suffix.lower()
        if file_extension in ['.xlsx', '.xls']:
            if file_extension == '.xlsx':
                df = pd.read_excel(io.BytesIO(contents), engine='openpyxl', header=header_row)
            else:
                df = pd.read_excel(io.BytesIO(contents), engine='xlrd', header=header_row)
        else:
            df = pd.read_excel(io.BytesIO(contents), header=header_row, encoding='utf-8-sig')
        
        # بناء خريطة الربط
        mapping_dict = {rule.source_column: rule.target_field for rule in request.mapping}
        
        # معالجة البيانات
        processed_records = []
        skipped_count = 0
        
        for idx, row in df.iterrows():
            # تطبيق الربط
            mapped_data = {}
            warnings = []
            is_empty = True
            
            for source_col, target_field in mapping_dict.items():
                if source_col not in df.columns:
                    warnings.append(f"العمود '{source_col}' غير موجود")
                    continue
                
                value = row[source_col]
                
                # التحقق من القيم الفارغة
                if pd.isna(value) or value == "":
                    if request.skip_empty:
                        continue
                    value = None
                else:
                    is_empty = False
                    
                    # تنظيف البيانات بناءً على نوع الحقل
                    if 'grade' in target_field.lower() or 'percent' in target_field.lower():
                        # تحويل إلى رقم
                        try:
                            value = float(str(value).replace('%', '').replace(',', '.').strip())
                        except:
                            warnings.append(f"فشل تحويل '{value}' إلى رقم في {target_field}")
                            value = None
                    
                    elif 'email' in target_field.lower():
                        # تنظيف البريد الإلكتروني
                        value = str(value).strip().lower()
                        if '@' not in value:
                            warnings.append(f"بريد إلكتروني غير صالح: {value}")
                    
                    elif 'phone' in target_field.lower():
                        # تنظيف رقم الهاتف
                        value = str(value).strip()
                        value = re.sub(r'[^\d+]', '', value)
                    
                    else:
                        # نص عادي
                        value = str(value).strip()
                
                mapped_data[target_field] = value
            
            # تخطي الصفوف الفارغة
            if is_empty and request.skip_empty:
                skipped_count += 1
                continue
            
            processed_records.append(ProcessedRecord(
                data=mapped_data,
                warnings=warnings
            ))
        
        # إنشاء التقرير
        report = f"""
تم معالجة الملف بنجاح!
━━━━━━━━━━━━━━━━━━━━━━
📊 إجمالي الصفوف المعالجة: {len(processed_records)}
⏭️  الصفوف المتخطاة (فارغة): {skipped_count}
✅ الصفوف الجاهزة للإدخال: {len(processed_records)}
━━━━━━━━━━━━━━━━━━━━━━
        """.strip()
        
        return ProcessResponse(
            success=True,
            processed_data=processed_records,
            total_processed=len(processed_records),
            total_skipped=skipped_count,
            report=report
        )
        
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"فشلت المعالجة: {str(e)}")

@app.delete("/cleanup/{file_id}")
async def cleanup_file(file_id: str):
    """
    تنظيف الملفات المؤقتة
    """
    if file_id in TEMP_FILES:
        del TEMP_FILES[file_id]
        return {"success": True, "message": "تم حذف الملف المؤقت"}
    return {"success": False, "message": "الملف غير موجود"}

# ============================================
# Startup
# ============================================

if __name__ == "__main__":
    import uvicorn
    print("🚀 بدء تشغيل بوابة الاستيراد الذكية...")
    print("📍 العنوان: http://localhost:8008")
    print("📖 التوثيق: http://localhost:8008/docs")
    uvicorn.run(app, host="0.0.0.0", port=8008)
