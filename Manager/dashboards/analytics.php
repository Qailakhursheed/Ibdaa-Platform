<!-- Grid for charts and AI analysis -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    <!-- Main column for charts -->
    <div class="lg:col-span-2 space-y-8">
        
        <!-- Enrollments Over Time Chart -->
        <div class="bg-white rounded-2xl shadow p-6 border border-slate-100">
            <h3 class="text-xl font-bold text-slate-800 mb-4">تسجيل الطلاب على مدار الوقت</h3>
            <div class="h-72">
                <canvas id="enrollmentsChart"></canvas>
            </div>
        </div>

        <!-- Course Popularity and Student Performance -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="bg-white rounded-2xl shadow p-6 border border-slate-100">
                <h3 class="text-xl font-bold text-slate-800 mb-4">الدورات الأكثر شيوعًا</h3>
                <div class="h-72">
                    <canvas id="popularityChart"></canvas>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow p-6 border border-slate-100">
                <h3 class="text-xl font-bold text-slate-800 mb-4">توزيع أداء الطلاب</h3>
                 <div class="h-72">
                    <canvas id="performanceChart"></canvas>
                </div>
            </div>
        </div>

    </div>

    <!-- AI Analysis Sidebar -->
    <div class="lg:col-span-1 bg-white rounded-2xl shadow p-6 border border-slate-100">
        <div id="ai-analysis-container">
            <div class="flex items-center gap-3 mb-4">
                <i data-lucide="brain-circuit" class="w-8 h-8 text-sky-600"></i>
                <h3 id="ai-report-title" class="text-xl font-bold text-slate-800">تقرير التحليل الذكي</h3>
            </div>
            <div id="ai-report-body" class="text-slate-600 space-y-4">
                <p>جاري تحليل البيانات...</p>
            </div>
        </div>
    </div>

</div>

<script>
// Ensure Chart.js is loaded
if (typeof Chart === 'undefined') {
    console.error('Chart.js is not loaded!');
} else {
    // --- Chart Colors & Config ---
    const chartColors = {
        primary: 'rgba(14, 165, 233, 0.6)',
        primary_border: 'rgba(14, 165, 233, 1)',
        secondary: 'rgba(234, 179, 8, 0.6)',
        secondary_border: 'rgba(234, 179, 8, 1)',
        success: 'rgba(22, 163, 74, 0.6)',
        success_border: 'rgba(22, 163, 74, 1)',
        danger: 'rgba(220, 38, 38, 0.6)',
        danger_border: 'rgba(220, 38, 38, 1)',
    };

    const defaultChartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: '#f1f5f9' // slate-100
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    };

    // --- Fetch and Render Functions ---

    // 1. Enrollments Chart (Line)
    fetch('<?php echo $managerBaseUrl; ?>/api/ai_analytics_handler.php?action=enrollment_over_time')
        .then(res => res.json())
        .then(response => {
            if (response.success) {
                const ctx = document.getElementById('enrollmentsChart')?.getContext('2d');
                if (!ctx) return;
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: response.data.map(d => d.month),
                        datasets: [{
                            label: 'عدد المسجلين',
                            data: response.data.map(d => d.count),
                            backgroundColor: chartColors.primary,
                            borderColor: chartColors.primary_border,
                            borderWidth: 2,
                            fill: true,
                            tension: 0.3
                        }]
                    },
                    options: defaultChartOptions
                });
            }
        });

    // 2. Course Popularity Chart (Bar)
    fetch('<?php echo $managerBaseUrl; ?>/api/ai_analytics_handler.php?action=course_popularity')
        .then(res => res.json())
        .then(response => {
            if (response.success) {
                const ctx = document.getElementById('popularityChart')?.getContext('2d');
                if (!ctx) return;
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: response.data.map(d => d.title),
                        datasets: [{
                            label: 'عدد الطلاب',
                            data: response.data.map(d => d.student_count),
                            backgroundColor: chartColors.secondary,
                            borderColor: chartColors.secondary_border,
                            borderWidth: 1
                        }]
                    },
                    options: { ...defaultChartOptions, indexAxis: 'y' }
                });
            }
        });

    // 3. Student Performance Chart (Doughnut)
    fetch('<?php echo $managerBaseUrl; ?>/api/ai_analytics_handler.php?action=student_performance')
        .then(res => res.json())
        .then(response => {
            if (response.success) {
                const ctx = document.getElementById('performanceChart')?.getContext('2d');
                if (!ctx) return;
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: response.data.map(d => d.grade_range),
                        datasets: [{
                            label: 'عدد الطلاب',
                            data: response.data.map(d => d.count),
                            backgroundColor: [
                                chartColors.success,
                                chartColors.primary,
                                chartColors.secondary,
                                chartColors.danger,
                                'rgba(100, 116, 139, 0.6)'
                            ],
                            borderWidth: 0
                        }]
                    },
                    options: { ...defaultChartOptions, plugins: { legend: { display: true, position: 'bottom' } } }
                });
            }
        });
    
    // 4. AI Dropout Analysis
    fetch('<?php echo $managerBaseUrl; ?>/api/ai_analytics_handler.php?action=ai_dropout_analysis')
        .then(res => res.json())
        .then(response => {
            if (response.success) {
                document.getElementById('ai-report-title').textContent = response.report_title;
                // Convert markdown-like bolding and lists to HTML
                let reportHtml = response.insight.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                reportHtml = reportHtml.replace(/^- (.*)/gm, '<li class="ml-4">$1</li>');
                reportHtml = `<ul>${reportHtml}</ul>`.replace(/<\/ul>\n<ul>/g, ''); // Fix for multiple lists
                document.getElementById('ai-report-body').innerHTML = reportHtml;
            } else {
                document.getElementById('ai-report-body').innerHTML = '<p>فشل في تحميل التحليل الذكي.</p>';
            }
        });
}
</script>
