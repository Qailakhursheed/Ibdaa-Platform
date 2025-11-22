/**
 * Charts Loader - Simple JavaScript for Loading Python API Charts
 * محمل الرسوم البيانية - بديل بسيط لـ dynamic-charts.js
 * 
 * يستخدم Python API مباشرة بدلاً من JavaScript المعقد
 */

const ChartLoader = {
    apiBase: 'http://localhost:5000',
    
    /**
     * Load chart from Python API
     */
    async loadChart(endpoint, containerId, params = {}) {
        try {
            const queryString = new URLSearchParams(params).toString();
            const url = `${this.apiBase}${endpoint}${queryString ? '?' + queryString : ''}`;
            
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.success && data.chart) {
                const chartData = JSON.parse(data.chart);
                Plotly.newPlot(containerId, chartData.data, chartData.layout, {
                    responsive: true,
                    displayModeBar: false
                });
                return data;
            } else {
                this.showError(containerId, 'لا توجد بيانات متاحة');
                return null;
            }
        } catch (error) {
            console.error('Chart loading error:', error);
            this.showError(containerId, '⚠️ تأكد من تشغيل Python API Server');
            return null;
        }
    },
    
    /**
     * Show error message in chart container
     */
    showError(containerId, message) {
        const container = document.getElementById(containerId);
        if (container) {
            container.innerHTML = `
                <div class="flex items-center justify-center h-full text-amber-600">
                    <p class="text-center">${message}</p>
                </div>
            `;
        }
    },
    
    /**
     * Show loading indicator
     */
    showLoading(containerId) {
        const container = document.getElementById(containerId);
        if (container) {
            container.innerHTML = `
                <div class="flex items-center justify-center h-full">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                </div>
            `;
        }
    },
    
    // ===============================================
    // Manager Dashboard Charts
    // ===============================================
    
    loadStudentsStatus(containerId) {
        this.showLoading(containerId);
        return this.loadChart('/api/charts/students-status', containerId);
    },
    
    loadCoursesStatus(containerId) {
        this.showLoading(containerId);
        return this.loadChart('/api/charts/courses-status', containerId);
    },
    
    loadRevenueMonthly(containerId) {
        this.showLoading(containerId);
        return this.loadChart('/api/charts/revenue-monthly', containerId);
    },
    
    loadAttendanceRate(containerId) {
        this.showLoading(containerId);
        return this.loadChart('/api/charts/attendance-rate', containerId);
    },
    
    loadPerformanceOverview(containerId) {
        this.showLoading(containerId);
        return this.loadChart('/api/charts/performance-overview', containerId);
    },
    
    loadGradesDistribution(containerId) {
        this.showLoading(containerId);
        return this.loadChart('/api/charts/grades-distribution', containerId);
    },
    
    // ===============================================
    // Student Dashboard Charts
    // ===============================================
    
    loadStudentCoursesProgress(containerId, studentId) {
        this.showLoading(containerId);
        return this.loadChart('/api/student/courses-progress', containerId, { student_id: studentId });
    },
    
    loadStudentAttendanceRate(containerId, studentId) {
        this.showLoading(containerId);
        return this.loadChart('/api/student/attendance-rate', containerId, { student_id: studentId });
    },
    
    loadStudentGradesOverview(containerId, studentId) {
        this.showLoading(containerId);
        return this.loadChart('/api/student/grades-overview', containerId, { student_id: studentId });
    },
    
    // ===============================================
    // Trainer Dashboard Charts
    // ===============================================
    
    loadTrainerStudentsPerformance(containerId, trainerId, courseId = null) {
        this.showLoading(containerId);
        const params = { trainer_id: trainerId };
        if (courseId) params.course_id = courseId;
        return this.loadChart('/api/trainer/students-performance', containerId, params);
    },
    
    loadTrainerCourseAttendance(containerId, courseId) {
        this.showLoading(containerId);
        return this.loadChart('/api/trainer/course-attendance', containerId, { course_id: courseId });
    },
    
    loadTrainerGradesDistribution(containerId, courseId) {
        this.showLoading(containerId);
        return this.loadChart('/api/trainer/grades-distribution', containerId, { course_id: courseId });
    },
    
    // ===============================================
    // Analytics Charts
    // ===============================================
    
    async loadDashboardStats() {
        try {
            const response = await fetch(`${this.apiBase}/api/analytics/dashboard-stats`);
            const data = await response.json();
            
            if (data.success) {
                return data.statistics;
            }
            return null;
        } catch (error) {
            console.error('Stats loading error:', error);
            return null;
        }
    },
    
    loadMonthlyRevenue(containerId) {
        this.showLoading(containerId);
        return this.loadChart('/api/analytics/monthly-revenue', containerId);
    }
};

// Make it globally available
window.ChartLoader = ChartLoader;

// Auto-load charts on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('🎨 Chart Loader initialized - Python API integration ready');
});
