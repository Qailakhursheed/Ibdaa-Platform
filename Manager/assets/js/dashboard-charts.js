/**
 * ═══════════════════════════════════════════════════════════════
 * ADVANCED DASHBOARD CHARTS SYSTEM v3.0
 * نظام الرسومات البيانية المتقدمة للوحة التحكم
 * ═══════════════════════════════════════════════════════════════
 * Features:
 * - Interactive and dynamic charts with Chart.js v4.4
 * - Real-time data updates
 * - Smooth animations
 * - Responsive design
 * - Arabic RTL support
 * - Multiple chart types (Line, Bar, Doughnut, Pie, Radar)
 * ═══════════════════════════════════════════════════════════════
 */

// Chart Color Palette
const CHART_COLORS = {
    primary: {
        blue: 'rgb(59, 130, 246)',
        indigo: 'rgb(99, 102, 241)',
        purple: 'rgb(139, 92, 246)',
        pink: 'rgb(236, 72, 153)',
    },
    success: {
        emerald: 'rgb(16, 185, 129)',
        teal: 'rgb(20, 184, 166)',
        green: 'rgb(34, 197, 94)',
    },
    warning: {
        amber: 'rgb(251, 191, 36)',
        orange: 'rgb(251, 146, 60)',
        yellow: 'rgb(250, 204, 21)',
    },
    danger: {
        red: 'rgb(239, 68, 68)',
        rose: 'rgb(244, 63, 94)',
    },
    neutral: {
        slate: 'rgb(100, 116, 139)',
        gray: 'rgb(107, 114, 128)',
    }
};

// Global Chart Configuration
Chart.defaults.font.family = 'Cairo, sans-serif';
Chart.defaults.color = '#64748b';
Chart.defaults.borderColor = 'rgba(226, 232, 240, 0.5)';

// Common Chart Options
const commonChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
        mode: 'index',
        intersect: false,
    },
    plugins: {
        legend: {
            display: true,
            position: 'bottom',
            rtl: true,
            labels: {
                font: { family: 'Cairo', size: 12, weight: 600 },
                padding: 15,
                usePointStyle: true,
                pointStyle: 'circle',
                boxWidth: 8,
                boxHeight: 8,
            }
        },
        tooltip: {
            rtl: true,
            backgroundColor: 'rgba(15, 23, 42, 0.95)',
            titleColor: '#fff',
            bodyColor: '#e2e8f0',
            borderColor: 'rgba(148, 163, 184, 0.3)',
            borderWidth: 1,
            padding: 12,
            cornerRadius: 8,
            titleFont: { family: 'Cairo', size: 14, weight: 'bold' },
            bodyFont: { family: 'Cairo', size: 13 },
            callbacks: {
                title: (context) => {
                    return context[0].label;
                }
            }
        }
    },
    animation: {
        duration: 1000,
        easing: 'easeInOutQuart'
    }
};

/**
 * Revenue Trend Chart - اتجاه الإيرادات
 * Advanced line chart with gradient fill
 */
function initRevenueTrendChart(canvasId, data = null) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;
    
    const defaultData = {
        labels: ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو'],
        values: [25000, 32000, 28000, 42000, 38000, 55000]
    };
    
    const chartData = data || defaultData;
    
    // Create gradient
    const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(99, 102, 241, 0.2)');
    gradient.addColorStop(1, 'rgba(99, 102, 241, 0)');
    
    return new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [{
                label: 'الإيرادات (ريال)',
                data: chartData.values,
                borderColor: CHART_COLORS.primary.indigo,
                backgroundColor: gradient,
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 5,
                pointHoverRadius: 8,
                pointBackgroundColor: CHART_COLORS.primary.indigo,
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointHoverBorderWidth: 3
            }]
        },
        options: {
            ...commonChartOptions,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(203, 213, 225, 0.3)',
                        drawBorder: false
                    },
                    ticks: {
                        font: { family: 'Cairo', size: 11 },
                        callback: (value) => value.toLocaleString('ar-SA') + ' ريال'
                    }
                },
                x: {
                    grid: { display: false, drawBorder: false },
                    ticks: { font: { family: 'Cairo', size: 11 } }
                }
            },
            plugins: {
                ...commonChartOptions.plugins,
                tooltip: {
                    ...commonChartOptions.plugins.tooltip,
                    callbacks: {
                        label: (context) => {
                            return ` الإيرادات: ${context.parsed.y.toLocaleString('ar-SA')} ريال`;
                        }
                    }
                }
            }
        }
    });
}

/**
 * Enrollments Distribution Chart - توزيع التسجيلات
 * Doughnut chart with hover effects
 */
function initEnrollmentsChart(canvasId, data = null) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;
    
    const defaultData = {
        labels: ['البرمجة', 'التصميم', 'التسويق', 'إدارة الأعمال', 'أخرى'],
        values: [45, 25, 15, 10, 5]
    };
    
    const chartData = data || defaultData;
    
    return new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: chartData.labels,
            datasets: [{
                data: chartData.values,
                backgroundColor: [
                    CHART_COLORS.primary.blue,
                    CHART_COLORS.primary.purple,
                    CHART_COLORS.success.emerald,
                    CHART_COLORS.warning.amber,
                    CHART_COLORS.neutral.slate
                ],
                borderWidth: 0,
                hoverOffset: 15,
                hoverBorderWidth: 0
            }]
        },
        options: {
            ...commonChartOptions,
            cutout: '70%',
            plugins: {
                ...commonChartOptions.plugins,
                legend: {
                    ...commonChartOptions.plugins.legend,
                    position: 'bottom'
                },
                tooltip: {
                    ...commonChartOptions.plugins.tooltip,
                    callbacks: {
                        label: (context) => {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return ` ${context.label}: ${context.parsed} طالب (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

/**
 * Payment Methods Chart - طرق الدفع
 * Pie chart with custom colors
 */
function initPaymentMethodsChart(canvasId, data = null) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;
    
    const defaultData = {
        labels: ['نقداً', 'بطاقة', 'تحويل بنكي', 'أخرى'],
        values: [40, 35, 20, 5]
    };
    
    const chartData = data || defaultData;
    
    return new Chart(ctx, {
        type: 'pie',
        data: {
            labels: chartData.labels,
            datasets: [{
                data: chartData.values,
                backgroundColor: [
                    CHART_COLORS.success.emerald,
                    CHART_COLORS.primary.purple,
                    CHART_COLORS.primary.blue,
                    CHART_COLORS.warning.amber
                ],
                borderWidth: 3,
                borderColor: '#fff',
                hoverOffset: 10
            }]
        },
        options: {
            ...commonChartOptions,
            plugins: {
                ...commonChartOptions.plugins,
                legend: {
                    ...commonChartOptions.plugins.legend,
                    position: 'bottom'
                },
                tooltip: {
                    ...commonChartOptions.plugins.tooltip,
                    callbacks: {
                        label: (context) => {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return ` ${context.label}: ${percentage}%`;
                        }
                    }
                }
            }
        }
    });
}

/**
 * Course Completion Rate Chart - معدل إنجاز الدورات
 * Bar chart with gradient
 */
function initCompletionRateChart(canvasId, data = null) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;
    
    const defaultData = {
        labels: ['أسبوع 1', 'أسبوع 2', 'أسبوع 3', 'أسبوع 4'],
        values: [75, 82, 88, 92]
    };
    
    const chartData = data || defaultData;
    
    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartData.labels,
            datasets: [{
                label: 'معدل الإنجاز %',
                data: chartData.values,
                backgroundColor: CHART_COLORS.primary.blue,
                borderRadius: 10,
                borderSkipped: false,
                barThickness: 40,
                maxBarThickness: 50
            }]
        },
        options: {
            ...commonChartOptions,
            plugins: {
                ...commonChartOptions.plugins,
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    grid: {
                        color: 'rgba(203, 213, 225, 0.3)',
                        drawBorder: false
                    },
                    ticks: {
                        font: { family: 'Cairo', size: 11 },
                        callback: (value) => value + '%'
                    }
                },
                x: {
                    grid: { display: false, drawBorder: false },
                    ticks: { font: { family: 'Cairo', size: 11 } }
                }
            }
        }
    });
}

/**
 * Monthly Growth Chart - النمو الشهري
 * Area chart with smooth lines
 */
function initGrowthChart(canvasId, data = null) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;
    
    const defaultData = {
        labels: ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو'],
        values: [12, 19, 15, 25, 22, 30]
    };
    
    const chartData = data || defaultData;
    
    // Create gradient
    const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 250);
    gradient.addColorStop(0, 'rgba(251, 191, 36, 0.3)');
    gradient.addColorStop(1, 'rgba(251, 191, 36, 0)');
    
    return new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [{
                label: 'متدربون جدد',
                data: chartData.values,
                borderColor: CHART_COLORS.warning.amber,
                backgroundColor: gradient,
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 7,
                pointBackgroundColor: CHART_COLORS.warning.amber,
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options: {
            ...commonChartOptions,
            plugins: {
                ...commonChartOptions.plugins,
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(203, 213, 225, 0.3)',
                        drawBorder: false
                    },
                    ticks: {
                        font: { family: 'Cairo', size: 11 },
                        stepSize: 5
                    }
                },
                x: {
                    grid: { display: false, drawBorder: false },
                    ticks: { font: { family: 'Cairo', size: 11 } }
                }
            }
        }
    });
}

/**
 * Student Performance Radar Chart - أداء الطلاب
 * Radar chart for multi-dimensional data
 */
function initPerformanceRadarChart(canvasId, data = null) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;
    
    const defaultData = {
        labels: ['الحضور', 'الواجبات', 'الاختبارات', 'المشاركة', 'المشاريع'],
        values: [85, 90, 78, 88, 92]
    };
    
    const chartData = data || defaultData;
    
    return new Chart(ctx, {
        type: 'radar',
        data: {
            labels: chartData.labels,
            datasets: [{
                label: 'متوسط الأداء',
                data: chartData.values,
                backgroundColor: 'rgba(59, 130, 246, 0.2)',
                borderColor: CHART_COLORS.primary.blue,
                borderWidth: 2,
                pointBackgroundColor: CHART_COLORS.primary.blue,
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            ...commonChartOptions,
            scales: {
                r: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        stepSize: 20,
                        font: { family: 'Cairo', size: 10 }
                    },
                    grid: {
                        color: 'rgba(203, 213, 225, 0.3)'
                    },
                    pointLabels: {
                        font: { family: 'Cairo', size: 12, weight: 600 }
                    }
                }
            }
        }
    });
}

/**
 * Initialize All Dashboard Charts
 */
function initAllDashboardCharts() {
    // Main charts
    const charts = {
        revenue: initRevenueTrendChart('revenueChart'),
        enrollments: initEnrollmentsChart('enrollmentsChart'),
        paymentMethods: initPaymentMethodsChart('paymentMethodsChart'),
        completionRate: initCompletionRateChart('completionRateChart'),
        growth: initGrowthChart('growthChart')
    };
    
    // Additional charts if canvas exists
    const performanceCanvas = document.getElementById('performanceRadarChart');
    if (performanceCanvas) {
        charts.performance = initPerformanceRadarChart('performanceRadarChart');
    }
    
    return charts;
}

/**
 * Update Chart Data Dynamically
 */
function updateChartData(chart, newData) {
    if (!chart) return;
    
    if (newData.labels) {
        chart.data.labels = newData.labels;
    }
    
    if (newData.values && chart.data.datasets[0]) {
        chart.data.datasets[0].data = newData.values;
    }
    
    chart.update('active');
}

/**
 * Destroy Chart
 */
function destroyChart(chart) {
    if (chart && typeof chart.destroy === 'function') {
        chart.destroy();
    }
}

/**
 * Export Chart as Image
 */
function exportChartAsImage(chart, filename = 'chart.png') {
    if (!chart) return;
    
    const url = chart.toBase64Image();
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    link.click();
}

// Export functions for global use
if (typeof window !== 'undefined') {
    window.DashboardCharts = {
        init: initAllDashboardCharts,
        initRevenueTrendChart,
        initEnrollmentsChart,
        initPaymentMethodsChart,
        initCompletionRateChart,
        initGrowthChart,
        initPerformanceRadarChart,
        updateChartData,
        destroyChart,
        exportChartAsImage,
        CHART_COLORS
    };
}

// Auto-initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAllDashboardCharts);
} else {
    // DOM is already loaded
    setTimeout(initAllDashboardCharts, 100);
}
