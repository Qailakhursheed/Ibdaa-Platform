/**
 * نظام الرسوم البيانية الديناميكية
 * Dynamic Charts System
 * Ibdaa Training Platform
 * 
 * يربط Chart.js بالبيانات الحقيقية من API
 * مع تحديث تلقائي وتفاعلي
 */

// استخدام getBasePath من manager-features.js إذا كان موجود أو إنشاء دالة محلية
(function() {
    if (typeof window.getBasePath !== 'function') {
        window.getBasePath = function() {
            const path = window.location.pathname;
            const match = path.match(/(.*?\/Ibdaa-Taiz)/);
            return match ? match[1] : '';
        };
    }
})();

const dynamicChartsBasePath = window.getBasePath();
const ANALYTICS_API = dynamicChartsBasePath + '/Manager/api/dynamic_analytics.php';

// تصحيح الأخطاء
console.log('🔍 Dynamic Charts - Base Path:', dynamicChartsBasePath);
console.log('🔍 Dynamic Charts - Full API URL:', ANALYTICS_API);

// تخزين مؤقت للرسوم البيانية
const chartInstances = {};

// ==============================================
// تحميل إحصائيات لوحة التحكم
// ==============================================
async function loadDashboardStats() {
    try {
        const response = await fetch(ANALYTICS_API + '?action=dashboard_stats');
        const data = await response.json();
        
        if (!data.success) {
            console.error('Failed to load stats:', data.message);
            return;
        }
        
        const stats = data.statistics;
        
        // تحديث البطاقات الإحصائية
        updateStatCard('totalStudents', stats.total_students, 'طالب مسجل');
        updateStatCard('activeStudents', stats.active_students, 'طالب نشط');
        updateStatCard('totalTrainers', stats.total_trainers, 'مدرب');
        updateStatCard('totalCourses', stats.total_courses, 'دورة نشطة');
        updateStatCard('totalRevenue', formatMoney(stats.total_revenue), 'إجمالي الإيرادات');
        updateStatCard('pendingAmount', formatMoney(stats.pending_amount), 'دفعات معلقة');
        updateStatCard('pendingRequests', stats.pending_requests, 'طلب معلق');
        updateStatCard('issuedCards', stats.issued_cards, 'بطاقة صادرة');
        
        return stats;
    } catch (error) {
        console.error('Error loading dashboard stats:', error);
        showNotification('فشل تحميل الإحصائيات', 'error');
    }
}

/**
 * تحديث بطاقة إحصائية
 */
function updateStatCard(elementId, value, label) {
    const card = document.getElementById(elementId);
    if (card) {
        const valueEl = card.querySelector('.stat-value') || card;
        const labelEl = card.querySelector('.stat-label');
        
        // تأثير العد التصاعدي
        if (typeof value === 'number') {
            animateValue(valueEl, 0, value, 1000);
        } else {
            valueEl.textContent = value;
        }
        
        if (labelEl) {
            labelEl.textContent = label;
        }
    }
}

/**
 * تأثير العد التصاعدي للأرقام
 */
function animateValue(element, start, end, duration) {
    const range = end - start;
    const increment = range / (duration / 16);
    let current = start;
    
    const timer = setInterval(() => {
        current += increment;
        if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
            current = end;
            clearInterval(timer);
        }
        element.textContent = Math.floor(current);
    }, 16);
}

// ==============================================
// رسم بياني: الطلاب حسب الحالة
// ==============================================
async function renderStudentsByStatusChart(canvasId = 'studentsStatusChart') {
    try {
        const response = await fetch(ANALYTICS_API + '?action=students_by_status');
        const data = await response.json();
        
        if (!data.success) {
            console.error('Failed to load chart data:', data.message);
            return;
        }
        
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        
        // إزالة الرسم القديم إن وجد
        if (chartInstances[canvasId]) {
            chartInstances[canvasId].destroy();
        }
        
        const ctx = canvas.getContext('2d');
        chartInstances[canvasId] = new Chart(ctx, {
            type: 'doughnut',
            data: data.chart_data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        rtl: true,
                        labels: {
                            font: {
                                family: 'Cairo, sans-serif',
                                size: 12
                            },
                            padding: 15
                        }
                    },
                    title: {
                        display: true,
                        text: 'توزيع الطلاب حسب الحالة',
                        font: {
                            family: 'Cairo, sans-serif',
                            size: 16,
                            weight: 'bold'
                        }
                    },
                    tooltip: {
                        rtl: true,
                        textDirection: 'rtl',
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
        
    } catch (error) {
        console.error('Error rendering students status chart:', error);
    }
}

// ==============================================
// رسم بياني: الإيرادات الشهرية
// ==============================================
async function renderMonthlyRevenueChart(canvasId = 'monthlyRevenueChart', year = null) {
    try {
        const currentYear = year || new Date().getFullYear();
        const response = await fetch(ANALYTICS_API + `?action=monthly_revenue&year=${currentYear}`);
        const data = await response.json();
        
        if (!data.success) {
            console.error('Failed to load chart data:', data.message);
            return;
        }
        
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        
        if (chartInstances[canvasId]) {
            chartInstances[canvasId].destroy();
        }
        
        const ctx = canvas.getContext('2d');
        chartInstances[canvasId] = new Chart(ctx, {
            type: 'line',
            data: data.chart_data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: `الإيرادات الشهرية ${currentYear}`,
                        font: {
                            family: 'Cairo, sans-serif',
                            size: 16,
                            weight: 'bold'
                        }
                    },
                    tooltip: {
                        rtl: true,
                        textDirection: 'rtl',
                        callbacks: {
                            label: function(context) {
                                return `الإيرادات: ${formatMoney(context.parsed.y)}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return formatMoney(value);
                            },
                            font: {
                                family: 'Cairo, sans-serif'
                            }
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                family: 'Cairo, sans-serif'
                            }
                        }
                    }
                }
            }
        });
        
    } catch (error) {
        console.error('Error rendering monthly revenue chart:', error);
    }
}

// ==============================================
// رسم بياني: الطلاب حسب الدورة
// ==============================================
async function renderStudentsPerCourseChart(canvasId = 'studentsPerCourseChart') {
    try {
        const response = await fetch(ANALYTICS_API + '?action=students_per_course');
        const data = await response.json();
        
        if (!data.success) {
            console.error('Failed to load chart data:', data.message);
            return;
        }
        
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        
        if (chartInstances[canvasId]) {
            chartInstances[canvasId].destroy();
        }
        
        const ctx = canvas.getContext('2d');
        chartInstances[canvasId] = new Chart(ctx, {
            type: 'bar',
            data: data.chart_data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y', // أفقي
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'توزيع الطلاب على الدورات',
                        font: {
                            family: 'Cairo, sans-serif',
                            size: 16,
                            weight: 'bold'
                        }
                    },
                    tooltip: {
                        rtl: true,
                        textDirection: 'rtl',
                        callbacks: {
                            label: function(context) {
                                const details = data.courses_details[context.dataIndex];
                                return [
                                    `الطلاب: ${details.students}`,
                                    `الإيرادات: ${formatMoney(details.revenue)}`,
                                    `النسبة: ${details.percentage}%`
                                ];
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            font: {
                                family: 'Cairo, sans-serif'
                            }
                        }
                    },
                    y: {
                        ticks: {
                            font: {
                                family: 'Cairo, sans-serif',
                                size: 11
                            }
                        }
                    }
                }
            }
        });
        
    } catch (error) {
        console.error('Error rendering students per course chart:', error);
    }
}

// ==============================================
// رسم بياني: الطلاب حسب المنطقة
// ==============================================
async function renderStudentsByRegionChart(canvasId = 'studentsByRegionChart') {
    try {
        const response = await fetch(ANALYTICS_API + '?action=students_by_region');
        const data = await response.json();
        
        if (!data.success) {
            console.error('Failed to load chart data:', data.message);
            return;
        }
        
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        
        if (chartInstances[canvasId]) {
            chartInstances[canvasId].destroy();
        }
        
        const ctx = canvas.getContext('2d');
        chartInstances[canvasId] = new Chart(ctx, {
            type: 'pie',
            data: data.chart_data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        rtl: true,
                        labels: {
                            font: {
                                family: 'Cairo, sans-serif',
                                size: 11
                            },
                            padding: 10
                        }
                    },
                    title: {
                        display: true,
                        text: 'توزيع الطلاب الجغرافي',
                        font: {
                            family: 'Cairo, sans-serif',
                            size: 16,
                            weight: 'bold'
                        }
                    },
                    tooltip: {
                        rtl: true,
                        textDirection: 'rtl'
                    }
                }
            }
        });
        
    } catch (error) {
        console.error('Error rendering students by region chart:', error);
    }
}

// ==============================================
// رسم بياني: حالة الدفع
// ==============================================
async function renderPaymentStatusChart(canvasId = 'paymentStatusChart') {
    try {
        const response = await fetch(ANALYTICS_API + '?action=payment_status_distribution');
        const data = await response.json();
        
        if (!data.success) {
            console.error('Failed to load chart data:', data.message);
            return;
        }
        
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        
        if (chartInstances[canvasId]) {
            chartInstances[canvasId].destroy();
        }
        
        const ctx = canvas.getContext('2d');
        chartInstances[canvasId] = new Chart(ctx, {
            type: 'doughnut',
            data: data.chart_data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        rtl: true,
                        labels: {
                            font: {
                                family: 'Cairo, sans-serif'
                            }
                        }
                    },
                    title: {
                        display: true,
                        text: 'حالة الدفع',
                        font: {
                            family: 'Cairo, sans-serif',
                            size: 16,
                            weight: 'bold'
                        }
                    },
                    tooltip: {
                        rtl: true,
                        textDirection: 'rtl'
                    }
                }
            }
        });
        
    } catch (error) {
        console.error('Error rendering payment status chart:', error);
    }
}

// ==============================================
// تحميل جميع الرسوم البيانية
// ==============================================
async function loadAllCharts() {
    console.log('🔄 Loading all charts...');
    
    await loadDashboardStats();
    
    // تحميل متوازٍ لجميع الرسوم البيانية
    await Promise.all([
        renderStudentsByStatusChart(),
        renderMonthlyRevenueChart(),
        renderStudentsPerCourseChart(),
        renderStudentsByRegionChart(),
        renderPaymentStatusChart()
    ]);
    
    console.log('✅ All charts loaded successfully!');
}

// ==============================================
// تحديث تلقائي كل 5 دقائق
// ==============================================
function startAutoRefresh(intervalMinutes = 5) {
    const intervalMs = intervalMinutes * 60 * 1000;
    
    setInterval(async () => {
        console.log('🔄 Auto-refreshing charts...');
        await loadAllCharts();
    }, intervalMs);
    
    console.log(`✅ Auto-refresh enabled (every ${intervalMinutes} minutes)`);
}

// ==============================================
// دوال مساعدة
// ==============================================

function formatMoney(amount) {
    return new Intl.NumberFormat('ar-SA', {
        style: 'currency',
        currency: 'SAR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    }).format(amount || 0);
}

function showNotification(message, type = 'info') {
    console.log(`[${type.toUpperCase()}] ${message}`);
    // يمكن إضافة نظام إشعارات مرئي هنا
}

// ==============================================
// تهيئة عند تحميل الصفحة
// ==============================================
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        loadAllCharts();
        startAutoRefresh(5);
    });
} else {
    loadAllCharts();
    startAutoRefresh(5);
}

// تصدير الدوال للاستخدام الخارجي
window.ChartsSystem = {
    loadAllCharts,
    loadDashboardStats,
    renderStudentsByStatusChart,
    renderMonthlyRevenueChart,
    renderStudentsPerCourseChart,
    renderStudentsByRegionChart,
    renderPaymentStatusChart,
    startAutoRefresh
};

console.log('✅ Dynamic Charts System Initialized!');
