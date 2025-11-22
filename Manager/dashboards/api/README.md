# Charts API - تشغيل خادم الرسوم البيانية

## التثبيت

```powershell
# الانتقال لمجلد API
cd api

# تثبيت المتطلبات
pip install -r requirements.txt
```

## التشغيل

```powershell
# تشغيل الخادم
python charts_api.py
```

الخادم سيعمل على: `http://localhost:5000`

## نقاط النهاية المتاحة

1. **حالة الطلاب**: `GET /api/charts/students-status`
2. **حالة الدورات**: `GET /api/charts/courses-status`
3. **الإيرادات الشهرية**: `GET /api/charts/revenue-monthly`
4. **معدل الحضور**: `GET /api/charts/attendance-rate`
5. **نظرة شاملة**: `GET /api/charts/performance-overview`
6. **توزيع الدرجات**: `GET /api/charts/grades-distribution`

## الاستخدام في PHP

```php
<?php
$chart_url = 'http://localhost:5000/api/charts/students-status';
$response = file_get_contents($chart_url);
$data = json_decode($response, true);

if ($data['success']) {
    $chart_json = $data['chart'];
    // استخدم chart_json مع Plotly.js
}
?>
```

## ملاحظات

- تأكد من تشغيل خادم Python قبل استخدام الرسوم البيانية
- يمكن تشغيل الخادم في الخلفية باستخدام: `python charts_api.py &`
- الرسوم البيانية تفاعلية بالكامل باستخدام Plotly
