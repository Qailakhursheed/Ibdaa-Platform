<?php
/**
 * AI-Powered Hybrid Charts Page
 * صفحة الرسوم البيانية الهجينة المعززة بالذكاء الاصطناعي
 */
?>
<div class="bg-white rounded-2xl shadow p-8">
    <div class="flex items-center gap-4 mb-6">
        <div class="p-3 rounded-full bg-gradient-to-br from-emerald-500 to-green-500 text-white shadow-lg">
            <i data-lucide="bar-chart-3" class="w-8 h-8"></i>
        </div>
        <div>
            <h3 class="text-2xl font-bold text-slate-800">الرسوم البيانية الهجينة (AI)</h3>
            <p class="text-slate-600">إنشاء رسوم بيانية تفاعلية وذكية من أي بيانات</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Data Input -->
        <div class="lg:col-span-1 space-y-6">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">لصق البيانات هنا (CSV, JSON, etc.)</label>
                <textarea id="dataInput" rows="10" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500" placeholder="e.g., month,sales
Jan,1000
Feb,1500..."></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">أو ارفع ملف</label>
                <input type="file" id="fileInput" class="text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">اقتراح نوع الرسم البياني</label>
                <select id="chartType" class="w-full px-4 py-2 border border-slate-300 rounded-lg">
                    <option value="auto">تلقائي (AI)</option>
                    <option value="bar">أعمدة</option>
                    <option value="line">خطي</option>
                    <option value="pie">دائري</option>
                    <option value="scatter">مبعثر</option>
                </select>
            </div>
            <button id="generateChartBtn" class="w-full px-4 py-3 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 font-semibold">
                إنشاء الرسم البياني
            </button>
        </div>

        <!-- Chart Preview -->
        <div class="lg:col-span-2">
            <div id="chartContainer" class="w-full h-96 border-2 border-slate-200 rounded-lg p-4 flex items-center justify-center">
                <p class="text-slate-500">ستظهر معاينة الرسم البياني هنا</p>
            </div>
            <div id="aiInsights" class="mt-4 bg-sky-50 p-4 rounded-lg border border-sky-200 hidden">
                <h4 class="font-bold text-sky-800 mb-2">رؤى الذكاء الاصطناعي</h4>
                <p id="aiInsightText" class="text-sm text-sky-700"></p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const dataInput = document.getElementById('dataInput');
    const fileInput = document.getElementById('fileInput');
    const chartTypeSelector = document.getElementById('chartType');
    const generateBtn = document.getElementById('generateChartBtn');
    const chartContainer = document.getElementById('chartContainer');
    const aiInsightsContainer = document.getElementById('aiInsights');
    const aiInsightText = document.getElementById('aiInsightText');
    let currentChart = null;

    fileInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (event) => {
                dataInput.value = event.target.result;
            };
            reader.readAsText(file);
        }
    });

    generateBtn.addEventListener('click', () => {
        const data = dataInput.value.trim();
        if (!data) {
            showToast('الرجاء إدخال بيانات أولاً', 'error');
            return;
        }

        // Simulate AI processing
        showToast('يقوم الذكاء الاصطناعي بتحليل البيانات...', 'info');
        
        // In a real app, you'd send the data to a backend AI service.
        // Here, we'll simulate the analysis.
        setTimeout(() => {
            try {
                const { parsedData, headers } = parseData(data);
                let chartType = chartTypeSelector.value;
                let insight = '';

                if (chartType === 'auto') {
                    // AI-based chart type suggestion
                    if (headers.length === 2 && typeof parsedData[0][1] === 'number') {
                        if (parsedData.length < 15) {
                            chartType = 'bar';
                            insight = 'تم اختيار رسم بياني بالأعمدة لأنه الأنسب لمقارنة القيم القليلة.';
                        } else {
                            chartType = 'line';
                            insight = 'تم اختيار رسم بياني خطي لأنه يوضح الاتجاهات عبر عدد كبير من نقاط البيانات.';
                        }
                    } else {
                        chartType = 'pie';
                        insight = 'تم اختيار رسم بياني دائري لتوضيح توزيع الفئات.';
                    }
                }
                
                renderChart(chartType, parsedData, headers);
                
                if (insight) {
                    aiInsightText.textContent = insight;
                    aiInsightsContainer.classList.remove('hidden');
                } else {
                    aiInsightsContainer.classList.add('hidden');
                }

            } catch (error) {
                showToast('فشل في تحليل البيانات. تأكد من صحة التنسيق.', 'error');
                console.error(error);
            }
        }, 1500);
    });

    function parseData(data) {
        const lines = data.split('\n').filter(line => line);
        const delimiter = data.includes(',') ? ',' : '\t'; // Simple delimiter detection
        const headers = lines[0].split(delimiter);
        const parsedData = lines.slice(1).map(line => {
            const values = line.split(delimiter);
            return values.map(val => isNaN(val) ? val : parseFloat(val));
        });
        return { parsedData, headers };
    }

    function renderChart(type, data, headers) {
        if (currentChart) {
            currentChart.destroy();
        }
        chartContainer.innerHTML = '<canvas id="aiChart"></canvas>';
        const ctx = document.getElementById('aiChart').getContext('2d');

        const labels = data.map(row => row[0]);
        const chartData = data.map(row => row[1]);

        currentChart = new Chart(ctx, {
            type: type,
            data: {
                labels: labels,
                datasets: [{
                    label: headers[1],
                    data: chartData,
                    backgroundColor: type === 'pie' 
                        ? ['#34d399', '#60a5fa', '#f87171', '#fbbf24', '#a78bfa']
                        : '#34d399',
                    borderColor: '#10b981',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: `${headers[1]} by ${headers[0]}`
                    }
                }
            }
        });
    }
});
</script>
