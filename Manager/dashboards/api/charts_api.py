"""
Charts API - Extended Python Backend for Interactive Features
نظام Python موسع لتوليد الرسوم البيانية والوظائف التفاعلية

Requirements:
pip install flask plotly pandas mysql-connector-python flask-cors
"""

from flask import Flask, jsonify, request
from flask_cors import CORS
import plotly.graph_objects as go
import plotly.express as px
import mysql.connector
from datetime import datetime, timedelta
import json
import pandas as pd
from decimal import Decimal

app = Flask(__name__)
CORS(app)  # Enable CORS for all routes

# Database Configuration
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'ibdaa_platform'
}

def get_db_connection():
    """إنشاء اتصال بقاعدة البيانات"""
    return mysql.connector.connect(**DB_CONFIG)

def decimal_default(obj):
    """معالج لتحويل Decimal إلى float في JSON"""
    if isinstance(obj, Decimal):
        return float(obj)
    raise TypeError

@app.route('/api/charts/students-status', methods=['GET'])
def students_status_chart():
    """رسم بياني لحالة الطلاب"""
    try:
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        
        cursor.execute("""
            SELECT status, COUNT(*) as count 
            FROM users 
            WHERE role = 'student' 
            GROUP BY status
        """)
        
        data = cursor.fetchall()
        cursor.close()
        conn.close()
        
        labels = [row['status'] for row in data]
        values = [row['count'] for row in data]
        
        fig = go.Figure(data=[go.Pie(
            labels=labels,
            values=values,
            hole=0.3,
            marker=dict(colors=['#0ea5e9', '#f59e0b', '#10b981'])
        )])
        
        fig.update_layout(
            title='حالة الطلاب',
            font=dict(family="Cairo, sans-serif", size=14),
            showlegend=True
        )
        
        return jsonify({
            'success': True,
            'chart': fig.to_json()
        })
        
    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500

@app.route('/api/charts/courses-status', methods=['GET'])
def courses_status_chart():
    """رسم بياني لحالة الدورات"""
    try:
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        
        cursor.execute("""
            SELECT status, COUNT(*) as count 
            FROM courses 
            GROUP BY status
        """)
        
        data = cursor.fetchall()
        cursor.close()
        conn.close()
        
        labels = [row['status'] for row in data]
        values = [row['count'] for row in data]
        
        fig = go.Figure(data=[go.Pie(
            labels=labels,
            values=values,
            hole=0.3,
            marker=dict(colors=['#10b981', '#0ea5e9', '#6366f1'])
        )])
        
        fig.update_layout(
            title='حالة الدورات',
            font=dict(family="Cairo, sans-serif", size=14)
        )
        
        return jsonify({
            'success': True,
            'chart': fig.to_json()
        })
        
    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500

@app.route('/api/charts/revenue-monthly', methods=['GET'])
def revenue_monthly_chart():
    """رسم بياني للإيرادات الشهرية"""
    try:
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        
        cursor.execute("""
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) * 500 as revenue
            FROM enrollments
            WHERE payment_status = 'paid'
            GROUP BY month
            ORDER BY month DESC
            LIMIT 12
        """)
        
        data = cursor.fetchall()
        cursor.close()
        conn.close()
        
        # عكس الترتيب لعرض من القديم للحديث
        data.reverse()
        
        months = [row['month'] for row in data]
        revenues = [row['revenue'] for row in data]
        
        fig = go.Figure(data=[go.Bar(
            x=months,
            y=revenues,
            marker=dict(color='#0ea5e9'),
            text=revenues,
            textposition='auto'
        )])
        
        fig.update_layout(
            title='الإيرادات الشهرية',
            xaxis_title='الشهر',
            yaxis_title='الإيرادات (ريال)',
            font=dict(family="Cairo, sans-serif", size=14),
            showlegend=False
        )
        
        return jsonify({
            'success': True,
            'chart': fig.to_json()
        })
        
    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500

@app.route('/api/charts/attendance-rate', methods=['GET'])
def attendance_rate_chart():
    """رسم بياني لمعدل الحضور"""
    try:
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        
        # التحقق من وجود جدول attendance
        cursor.execute("SHOW TABLES LIKE 'attendance'")
        if not cursor.fetchone():
            return jsonify({
                'success': False,
                'error': 'جدول الحضور غير موجود'
            }), 404
        
        cursor.execute("""
            SELECT 
                c.name as course_name,
                COUNT(CASE WHEN a.status = 'present' THEN 1 END) * 100.0 / COUNT(*) as attendance_rate
            FROM attendance a
            JOIN courses c ON a.course_id = c.id
            GROUP BY c.id, c.name
            ORDER BY attendance_rate DESC
            LIMIT 10
        """)
        
        data = cursor.fetchall()
        cursor.close()
        conn.close()
        
        courses = [row['course_name'] for row in data]
        rates = [float(row['attendance_rate']) for row in data]
        
        fig = go.Figure(data=[go.Bar(
            x=rates,
            y=courses,
            orientation='h',
            marker=dict(color='#10b981'),
            text=[f"{r:.1f}%" for r in rates],
            textposition='auto'
        )])
        
        fig.update_layout(
            title='معدل الحضور حسب الدورة',
            xaxis_title='معدل الحضور (%)',
            yaxis_title='الدورة',
            font=dict(family="Cairo, sans-serif", size=14)
        )
        
        return jsonify({
            'success': True,
            'chart': fig.to_json()
        })
        
    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500

@app.route('/api/charts/performance-overview', methods=['GET'])
def performance_overview_chart():
    """رسم بياني شامل للأداء"""
    try:
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        
        # جلب البيانات
        cursor.execute("SELECT COUNT(*) as count FROM users WHERE role = 'student'")
        students = cursor.fetchone()['count']
        
        cursor.execute("SELECT COUNT(*) as count FROM users WHERE role = 'trainer'")
        trainers = cursor.fetchone()['count']
        
        cursor.execute("SELECT COUNT(*) as count FROM courses WHERE status = 'active'")
        courses = cursor.fetchone()['count']
        
        cursor.execute("SELECT COUNT(*) as count FROM enrollments WHERE certificate_issued = 1")
        certificates = cursor.fetchone()['count']
        
        cursor.close()
        conn.close()
        
        categories = ['الطلاب', 'المدربون', 'الدورات النشطة', 'الشهادات']
        values = [students, trainers, courses, certificates]
        
        fig = go.Figure(data=[go.Bar(
            x=categories,
            y=values,
            marker=dict(color=['#0ea5e9', '#10b981', '#f59e0b', '#8b5cf6']),
            text=values,
            textposition='auto'
        )])
        
        fig.update_layout(
            title='نظرة شاملة على الأداء',
            yaxis_title='العدد',
            font=dict(family="Cairo, sans-serif", size=14),
            showlegend=False
        )
        
        return jsonify({
            'success': True,
            'chart': fig.to_json()
        })
        
    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500

@app.route('/api/charts/grades-distribution', methods=['GET'])
def grades_distribution_chart():
    """توزيع الدرجات"""
    try:
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        
        cursor.execute("""
            SELECT 
                CASE 
                    WHEN final_grade >= 90 THEN 'ممتاز'
                    WHEN final_grade >= 80 THEN 'جيد جداً'
                    WHEN final_grade >= 70 THEN 'جيد'
                    WHEN final_grade >= 60 THEN 'مقبول'
                    ELSE 'راسب'
                END as grade_category,
                COUNT(*) as count
            FROM enrollments
            WHERE final_grade IS NOT NULL
            GROUP BY grade_category
            ORDER BY final_grade DESC
        """)
        
        data = cursor.fetchall()
        cursor.close()
        conn.close()
        
        categories = [row['grade_category'] for row in data]
        counts = [row['count'] for row in data]
        
        fig = go.Figure(data=[go.Pie(
            labels=categories,
            values=counts,
            marker=dict(colors=['#10b981', '#0ea5e9', '#f59e0b', '#f97316', '#ef4444'])
        )])
        
        fig.update_layout(
            title='توزيع التقديرات',
            font=dict(family="Cairo, sans-serif", size=14)
        )
        
        return jsonify({
            'success': True,
            'chart': fig.to_json()
        })
        
    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500

@app.route('/health', methods=['GET'])
def health_check():
    """فحص صحة الخادم"""
    return jsonify({
        'status': 'healthy',
        'timestamp': datetime.now().isoformat()
    })

# ==============================================
# Student Dashboard APIs
# ==============================================

@app.route('/api/student/courses-progress', methods=['GET'])
def student_courses_progress():
    """تقدم الطالب في الدورات"""
    try:
        student_id = request.args.get('student_id', type=int)
        if not student_id:
            return jsonify({'success': False, 'error': 'student_id required'}), 400
        
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        
        cursor.execute("""
            SELECT 
                c.course_name,
                e.progress,
                e.final_grade,
                e.status
            FROM enrollments e
            JOIN courses c ON e.course_id = c.course_id
            WHERE e.user_id = %s
            ORDER BY e.enrollment_date DESC
        """, (student_id,))
        
        data = cursor.fetchall()
        cursor.close()
        conn.close()
        
        if not data:
            return jsonify({'success': True, 'data': [], 'message': 'No courses found'})
        
        courses = [row['course_name'] for row in data]
        progress = [row['progress'] or 0 for row in data]
        
        fig = go.Figure(data=[
            go.Bar(
                x=courses,
                y=progress,
                marker=dict(
                    color=progress,
                    colorscale='Blues',
                    showscale=True
                ),
                text=[f'{p}%' for p in progress],
                textposition='outside'
            )
        ])
        
        fig.update_layout(
            title='تقدم الدورات',
            xaxis_title='الدورة',
            yaxis_title='نسبة الإنجاز (%)',
            font=dict(family="Cairo, sans-serif", size=12),
            yaxis=dict(range=[0, 100])
        )
        
        return jsonify({
            'success': True,
            'chart': fig.to_json(),
            'data': data
        })
        
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500

@app.route('/api/student/attendance-rate', methods=['GET'])
def student_attendance_rate():
    """معدل حضور الطالب"""
    try:
        student_id = request.args.get('student_id', type=int)
        if not student_id:
            return jsonify({'success': False, 'error': 'student_id required'}), 400
        
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        
        cursor.execute("""
            SELECT 
                c.course_name,
                COUNT(CASE WHEN a.status = 'present' THEN 1 END) as present,
                COUNT(*) as total
            FROM attendance a
            JOIN courses c ON a.course_id = c.course_id
            WHERE a.student_id = %s
            GROUP BY c.course_id, c.course_name
        """, (student_id,))
        
        data = cursor.fetchall()
        cursor.close()
        conn.close()
        
        if not data:
            return jsonify({'success': True, 'data': [], 'message': 'No attendance data'})
        
        courses = [row['course_name'] for row in data]
        rates = [(row['present'] / row['total'] * 100) if row['total'] > 0 else 0 for row in data]
        
        fig = go.Figure(data=[
            go.Bar(
                y=courses,
                x=rates,
                orientation='h',
                marker=dict(color='#10b981'),
                text=[f'{r:.1f}%' for r in rates],
                textposition='outside'
            )
        ])
        
        fig.update_layout(
            title='معدل الحضور حسب الدورة',
            xaxis_title='نسبة الحضور (%)',
            yaxis_title='الدورة',
            font=dict(family="Cairo, sans-serif", size=12),
            xaxis=dict(range=[0, 100])
        )
        
        return jsonify({
            'success': True,
            'chart': fig.to_json(),
            'average_rate': sum(rates) / len(rates) if rates else 0
        })
        
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500

@app.route('/api/student/grades-overview', methods=['GET'])
def student_grades_overview():
    """نظرة عامة على درجات الطالب"""
    try:
        student_id = request.args.get('student_id', type=int)
        if not student_id:
            return jsonify({'success': False, 'error': 'student_id required'}), 400
        
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        
        cursor.execute("""
            SELECT 
                c.course_name,
                e.midterm_grade,
                e.final_grade
            FROM enrollments e
            JOIN courses c ON e.course_id = c.course_id
            WHERE e.user_id = %s AND e.final_grade IS NOT NULL
            ORDER BY e.enrollment_date DESC
        """, (student_id,))
        
        data = cursor.fetchall()
        cursor.close()
        conn.close()
        
        if not data:
            return jsonify({'success': True, 'data': [], 'message': 'No grades yet'})
        
        courses = [row['course_name'] for row in data]
        midterm = [row['midterm_grade'] or 0 for row in data]
        final = [row['final_grade'] or 0 for row in data]
        
        fig = go.Figure()
        
        fig.add_trace(go.Bar(
            name='النصفي',
            x=courses,
            y=midterm,
            marker=dict(color='#0ea5e9')
        ))
        
        fig.add_trace(go.Bar(
            name='النهائي',
            x=courses,
            y=final,
            marker=dict(color='#10b981')
        ))
        
        fig.update_layout(
            title='الدرجات - نصفي ونهائي',
            xaxis_title='الدورة',
            yaxis_title='الدرجة',
            font=dict(family="Cairo, sans-serif", size=12),
            barmode='group',
            yaxis=dict(range=[0, 100])
        )
        
        return jsonify({
            'success': True,
            'chart': fig.to_json(),
            'gpa': sum(final) / len(final) if final else 0
        })
        
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500

# ==============================================
# Trainer Dashboard APIs
# ==============================================

@app.route('/api/trainer/students-performance', methods=['GET'])
def trainer_students_performance():
    """أداء طلاب المدرب"""
    try:
        trainer_id = request.args.get('trainer_id', type=int)
        course_id = request.args.get('course_id', type=int)
        
        if not trainer_id:
            return jsonify({'success': False, 'error': 'trainer_id required'}), 400
        
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        
        query = """
            SELECT 
                u.full_name,
                AVG(e.final_grade) as avg_grade,
                COUNT(DISTINCT e.course_id) as courses_count
            FROM enrollments e
            JOIN users u ON e.user_id = u.user_id
            JOIN courses c ON e.course_id = c.course_id
            WHERE c.trainer_id = %s
        """
        params = [trainer_id]
        
        if course_id:
            query += " AND c.course_id = %s"
            params.append(course_id)
        
        query += " GROUP BY u.user_id, u.full_name ORDER BY avg_grade DESC LIMIT 20"
        
        cursor.execute(query, params)
        data = cursor.fetchall()
        cursor.close()
        conn.close()
        
        if not data:
            return jsonify({'success': True, 'data': [], 'message': 'No students found'})
        
        students = [row['full_name'] for row in data]
        grades = [float(row['avg_grade'] or 0) for row in data]
        
        colors = ['#10b981' if g >= 80 else '#f59e0b' if g >= 60 else '#ef4444' for g in grades]
        
        fig = go.Figure(data=[
            go.Bar(
                y=students,
                x=grades,
                orientation='h',
                marker=dict(color=colors),
                text=[f'{g:.1f}' for g in grades],
                textposition='outside'
            )
        ])
        
        fig.update_layout(
            title='أداء الطلاب - المعدلات',
            xaxis_title='المعدل',
            yaxis_title='الطالب',
            font=dict(family="Cairo, sans-serif", size=11),
            height=max(400, len(students) * 30)
        )
        
        return jsonify({
            'success': True,
            'chart': fig.to_json(),
            'avg_class_grade': sum(grades) / len(grades) if grades else 0
        })
        
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500

@app.route('/api/trainer/course-attendance', methods=['GET'])
def trainer_course_attendance():
    """تقرير الحضور لدورة المدرب"""
    try:
        course_id = request.args.get('course_id', type=int)
        if not course_id:
            return jsonify({'success': False, 'error': 'course_id required'}), 400
        
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        
        cursor.execute("""
            SELECT 
                DATE(attendance_date) as date,
                COUNT(CASE WHEN status = 'present' THEN 1 END) as present,
                COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent,
                COUNT(*) as total
            FROM attendance
            WHERE course_id = %s
            GROUP BY DATE(attendance_date)
            ORDER BY attendance_date DESC
            LIMIT 30
        """, (course_id,))
        
        data = cursor.fetchall()
        cursor.close()
        conn.close()
        
        if not data:
            return jsonify({'success': True, 'data': [], 'message': 'No attendance records'})
        
        dates = [row['date'].strftime('%Y-%m-%d') for row in data]
        present = [row['present'] for row in data]
        absent = [row['absent'] for row in data]
        
        fig = go.Figure()
        
        fig.add_trace(go.Scatter(
            x=dates,
            y=present,
            mode='lines+markers',
            name='حاضر',
            line=dict(color='#10b981', width=3),
            marker=dict(size=8)
        ))
        
        fig.add_trace(go.Scatter(
            x=dates,
            y=absent,
            mode='lines+markers',
            name='غائب',
            line=dict(color='#ef4444', width=3),
            marker=dict(size=8)
        ))
        
        fig.update_layout(
            title='الحضور والغياب - آخر 30 يوم',
            xaxis_title='التاريخ',
            yaxis_title='عدد الطلاب',
            font=dict(family="Cairo, sans-serif", size=12),
            hovermode='x unified'
        )
        
        return jsonify({
            'success': True,
            'chart': fig.to_json()
        })
        
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500

@app.route('/api/trainer/grades-distribution', methods=['GET'])
def trainer_grades_distribution():
    """توزيع الدرجات لدورة المدرب"""
    try:
        course_id = request.args.get('course_id', type=int)
        if not course_id:
            return jsonify({'success': False, 'error': 'course_id required'}), 400
        
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        
        cursor.execute("""
            SELECT 
                CASE 
                    WHEN final_grade >= 90 THEN 'A (ممتاز)'
                    WHEN final_grade >= 80 THEN 'B (جيد جداً)'
                    WHEN final_grade >= 70 THEN 'C (جيد)'
                    WHEN final_grade >= 60 THEN 'D (مقبول)'
                    ELSE 'F (راسب)'
                END as grade,
                COUNT(*) as count
            FROM enrollments
            WHERE course_id = %s AND final_grade IS NOT NULL
            GROUP BY grade
            ORDER BY final_grade DESC
        """, (course_id,))
        
        data = cursor.fetchall()
        cursor.close()
        conn.close()
        
        if not data:
            return jsonify({'success': True, 'data': [], 'message': 'No grades yet'})
        
        grades = [row['grade'] for row in data]
        counts = [row['count'] for row in data]
        
        fig = go.Figure(data=[
            go.Pie(
                labels=grades,
                values=counts,
                hole=0.4,
                marker=dict(colors=['#10b981', '#0ea5e9', '#f59e0b', '#f97316', '#ef4444'])
            )
        ])
        
        fig.update_layout(
            title='توزيع التقديرات',
            font=dict(family="Cairo, sans-serif", size=13)
        )
        
        return jsonify({
            'success': True,
            'chart': fig.to_json(),
            'total_students': sum(counts)
        })
        
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500

# ==============================================
# Dynamic Analytics (replacing dynamic-charts.js)
# ==============================================

@app.route('/api/analytics/dashboard-stats', methods=['GET'])
def analytics_dashboard_stats():
    """إحصائيات عامة للوحة التحكم"""
    try:
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        
        stats = {}
        
        # Total students
        cursor.execute("SELECT COUNT(*) as count FROM users WHERE role = 'student'")
        stats['total_students'] = cursor.fetchone()['count']
        
        # Active students
        cursor.execute("SELECT COUNT(DISTINCT user_id) as count FROM enrollments WHERE status = 'active'")
        stats['active_students'] = cursor.fetchone()['count']
        
        # Total trainers
        cursor.execute("SELECT COUNT(*) as count FROM users WHERE role = 'trainer'")
        stats['total_trainers'] = cursor.fetchone()['count']
        
        # Total courses
        cursor.execute("SELECT COUNT(*) as count FROM courses WHERE status = 'active'")
        stats['total_courses'] = cursor.fetchone()['count']
        
        # Total revenue
        cursor.execute("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE status = 'completed'")
        stats['total_revenue'] = float(cursor.fetchone()['total'])
        
        # Pending requests
        cursor.execute("SELECT COUNT(*) as count FROM requests WHERE status = 'pending'")
        stats['pending_requests'] = cursor.fetchone()['count']
        
        cursor.close()
        conn.close()
        
        return jsonify({
            'success': True,
            'statistics': stats
        })
        
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500

@app.route('/api/analytics/monthly-revenue', methods=['GET'])
def analytics_monthly_revenue():
    """الإيرادات الشهرية - 12 شهر"""
    try:
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        
        cursor.execute("""
            SELECT 
                DATE_FORMAT(transaction_date, '%Y-%m') as month,
                SUM(amount) as revenue
            FROM transactions
            WHERE status = 'completed' 
                AND transaction_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY month
            ORDER BY month ASC
        """)
        
        data = cursor.fetchall()
        cursor.close()
        conn.close()
        
        months = [row['month'] for row in data]
        revenue = [float(row['revenue']) for row in data]
        
        fig = go.Figure(data=[
            go.Scatter(
                x=months,
                y=revenue,
                mode='lines+markers',
                fill='tozeroy',
                line=dict(color='#10b981', width=3),
                marker=dict(size=10)
            )
        ])
        
        fig.update_layout(
            title='الإيرادات الشهرية - آخر 12 شهر',
            xaxis_title='الشهر',
            yaxis_title='الإيراد (ريال)',
            font=dict(family="Cairo, sans-serif", size=13)
        )
        
        return jsonify({
            'success': True,
            'chart': fig.to_json(),
            'total': sum(revenue)
        })
        
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500

if __name__ == '__main__':
    print("🚀 Starting Extended Charts API Server...")
    print("📊 Manager Dashboard Endpoints:")
    print("   - GET /api/charts/students-status")
    print("   - GET /api/charts/courses-status")
    print("   - GET /api/charts/revenue-monthly")
    print("   - GET /api/charts/attendance-rate")
    print("   - GET /api/charts/performance-overview")
    print("   - GET /api/charts/grades-distribution")
    print("\n📚 Student Dashboard Endpoints:")
    print("   - GET /api/student/courses-progress?student_id=X")
    print("   - GET /api/student/attendance-rate?student_id=X")
    print("   - GET /api/student/grades-overview?student_id=X")
    print("\n👨‍🏫 Trainer Dashboard Endpoints:")
    print("   - GET /api/trainer/students-performance?trainer_id=X&course_id=Y")
    print("   - GET /api/trainer/course-attendance?course_id=X")
    print("   - GET /api/trainer/grades-distribution?course_id=X")
    print("\n📈 Analytics Endpoints:")
    print("   - GET /api/analytics/dashboard-stats")
    print("   - GET /api/analytics/monthly-revenue")
    print("\n✅ Server running on http://localhost:5000")
    
    app.run(host='0.0.0.0', port=5000, debug=True)
