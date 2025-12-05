<?php
/**
 * AI Charts Generator - Generate interactive charts using AI
 * مولد الرسوم البيانية الذكي
 */

require_once __DIR__ . '/../../includes/env_loader.php';
EnvLoader::load(__DIR__ . '/../../../.env');

$geminiApiKey = EnvLoader::get('GEMINI_API_KEY', '');

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'generate_chart':
            $query = $_POST['query'] ?? '';
            $chartType = $_POST['chart_type'] ?? 'auto';
            
            echo json_encode(generateAIChart($conn, $query, $chartType, $geminiApiKey));
            exit;
            
        case 'get_suggested_charts':
            echo json_encode(getSuggestedCharts($conn));
            exit;
            
        case 'export_chart':
            $chartData = json_decode($_POST['chart_data'], true);
            $format = $_POST['format'] ?? 'png';
            
            echo json_encode(exportChart($chartData, $format));
            exit;
    }
}

/**
 * Generate AI Chart based on natural language query
 */
function generateAIChart($conn, $query, $chartType, $apiKey) {
    try {
        // Use Gemini AI to interpret the query and generate chart configuration
        $geminiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $apiKey;
        
        $prompt = "You are a data visualization expert. Based on this query: '$query', generate a chart configuration. ";
        $prompt .= "Return ONLY valid JSON with structure: {\"title\": \"chart title\", \"type\": \"bar|line|pie|doughnut|radar\", ";
        $prompt .= "\"labels\": [\"label1\", \"label2\"], \"datasets\": [{\"label\": \"dataset name\", \"data\": [10, 20, 30], \"backgroundColor\": \"#color\"}], ";
        $prompt .= "\"insights\": \"key insights from the data\"}. ";
        $prompt .= "If the query mentions specific data from a platform database, use realistic sample data.";
        
        $data = [
            'contents' => [[
                'parts' => [[
                    'text' => $prompt
                ]]
            ]]
        ];
        
        $ch = curl_init($geminiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            $aiText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
            
            // Extract JSON from response
            preg_match('/\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\}/s', $aiText, $matches);
            $chartConfig = json_decode($matches[0] ?? '{}', true);
            
            // Fallback to actual database data if query is about platform statistics
            if (strpos(strtolower($query), 'طلاب') !== false || strpos(strtolower($query), 'students') !== false) {
                $chartConfig = getStudentsChart($conn);
            } elseif (strpos(strtolower($query), 'دورات') !== false || strpos(strtolower($query), 'courses') !== false) {
                $chartConfig = getCoursesChart($conn);
            } elseif (strpos(strtolower($query), 'إيرادات') !== false || strpos(strtolower($query), 'revenue') !== false) {
                $chartConfig = getRevenueChart($conn);
            }
            
            return [
                'success' => true,
                'chart' => $chartConfig,
                'query' => $query,
                'generated_at' => date('Y-m-d H:i:s')
            ];
        } else {
            return ['success' => false, 'message' => 'فشل الاتصال بـ Gemini AI'];
        }
        
    } catch (Exception $e) {
        error_log("AI Chart Generation Error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Get students enrollment chart
 */
function getStudentsChart($conn) {
    $months = [];
    $counts = [];
    
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $months[] = date('M Y', strtotime("-$i months"));
        
        $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student' AND DATE_FORMAT(created_at, '%Y-%m') = '$month'");
        $counts[] = $result ? (int)$result->fetch_assoc()['count'] : 0;
    }
    
    return [
        'title' => 'التسجيلات الشهرية للطلاب',
        'type' => 'line',
        'labels' => $months,
        'datasets' => [[
            'label' => 'عدد الطلاب الجدد',
            'data' => $counts,
            'borderColor' => '#0ea5e9',
            'backgroundColor' => 'rgba(14, 165, 233, 0.1)',
            'tension' => 0.4
        ]],
        'insights' => 'نمو مستمر في أعداد الطلاب المسجلين'
    ];
}

/**
 * Get courses distribution chart
 */
function getCoursesChart($conn) {
    $result = $conn->query("
        SELECT c.course_name, COUNT(e.enrollment_id) as students_count
        FROM courses c
        LEFT JOIN enrollments e ON c.course_id = e.course_id
        WHERE c.status = 'active'
        GROUP BY c.course_id
        ORDER BY students_count DESC
        LIMIT 10
    ");
    
    $labels = [];
    $data = [];
    $colors = ['#0ea5e9', '#8b5cf6', '#10b981', '#f59e0b', '#ef4444', '#ec4899', '#6366f1', '#14b8a6', '#f97316', '#84cc16'];
    
    if ($result) {
        $i = 0;
        while ($row = $result->fetch_assoc()) {
            $labels[] = $row['course_name'];
            $data[] = (int)$row['students_count'];
            $i++;
        }
    }
    
    return [
        'title' => 'توزيع الطلاب على الدورات',
        'type' => 'bar',
        'labels' => $labels,
        'datasets' => [[
            'label' => 'عدد الطلاب',
            'data' => $data,
            'backgroundColor' => array_slice($colors, 0, count($data))
        ]],
        'insights' => 'أكثر الدورات شعبية في المنصة'
    ];
}

/**
 * Get revenue chart
 */
function getRevenueChart($conn) {
    $months = [];
    $revenue = [];
    
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $months[] = date('M Y', strtotime("-$i months"));
        
        $result = $conn->query("
            SELECT COUNT(*) * 500 as total 
            FROM enrollments 
            WHERE payment_status = 'paid' 
            AND DATE_FORMAT(created_at, '%Y-%m') = '$month'
        ");
        $revenue[] = $result ? (float)$result->fetch_assoc()['total'] : 0;
    }
    
    return [
        'title' => 'الإيرادات الشهرية',
        'type' => 'bar',
        'labels' => $months,
        'datasets' => [[
            'label' => 'الإيرادات (ريال)',
            'data' => $revenue,
            'backgroundColor' => '#10b981'
        ]],
        'insights' => 'تحليل الأداء المالي للمنصة'
    ];
}

/**
 * Get suggested charts based on platform data
 */
function getSuggestedCharts($conn) {
    return [
        'success' => true,
        'suggestions' => [
            ['query' => 'عدد الطلاب المسجلين شهرياً', 'icon' => 'trending-up', 'type' => 'line'],
            ['query' => 'توزيع الطلاب على الدورات', 'icon' => 'pie-chart', 'type' => 'bar'],
            ['query' => 'الإيرادات الشهرية للمنصة', 'icon' => 'dollar-sign', 'type' => 'bar'],
            ['query' => 'معدلات الحضور حسب الدورة', 'icon' => 'calendar-check', 'type' => 'radar'],
            ['query' => 'أداء المدربين (عدد الطلاب)', 'icon' => 'users', 'type' => 'doughnut'],
            ['query' => 'معدلات النجاح والرسوب', 'icon' => 'award', 'type' => 'pie']
        ]
    ];
}

/**
 * Export chart (placeholder)
 */
function exportChart($chartData, $format) {
    return [
        'success' => true,
        'message' => 'سيتم تصدير الرسم البياني قريباً',
        'format' => $format
    ];
}
?>

<!-- Page Header -->
<div class="mb-8">
    <h2 class="text-3xl font-bold text-slate-800 mb-2">الرسوم البيانية الذكية</h2>
    <p class="text-slate-600">إنشاء رسوم بيانية تفاعلية باستخدام الذكاء الاصطناعي</p>
</div>

<!-- AI Chart Generator Interface -->
<div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
    <!-- Left Panel: Controls -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sticky top-24">
            <h3 class="text-lg font-bold text-slate-800 mb-6">إعدادات الرسم</h3>
            
            <!-- AI Query Input -->
            <div class="mb-6">
                <label class="block text-sm font-semibold text-slate-700 mb-2">
                    <i data-lucide="message-circle" class="w-4 h-4 inline"></i>
                    اسأل الذكاء الاصطناعي
                </label>
                <textarea id="chartQuery" rows="4" 
                          class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-sky-500 resize-none text-sm"
                          placeholder="مثال: اعرض عدد الطلاب المسجلين في آخر 6 أشهر"></textarea>
            </div>
            
            <!-- Chart Type -->
            <div class="mb-6">
                <label class="block text-sm font-semibold text-slate-700 mb-2">
                    <i data-lucide="bar-chart-2" class="w-4 h-4 inline"></i>
                    نوع الرسم
                </label>
                <select id="chartType" class="w-full px-4 py-2 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-sky-500 text-sm">
                    <option value="auto">تلقائي (AI)</option>
                    <option value="line">خطي (Line)</option>
                    <option value="bar">أعمدة (Bar)</option>
                    <option value="pie">دائري (Pie)</option>
                    <option value="doughnut">دائري مجوف (Doughnut)</option>
                    <option value="radar">راداري (Radar)</option>
                </select>
            </div>
            
            <!-- Generate Button -->
            <button onclick="generateChart()" 
                    class="w-full px-6 py-3 bg-gradient-to-r from-sky-600 to-purple-600 text-white rounded-xl hover:from-sky-700 hover:to-purple-700 transition font-bold shadow-lg">
                <i data-lucide="sparkles" class="w-5 h-5 inline mr-2"></i>
                إنشاء الرسم
            </button>
            
            <!-- Suggested Queries -->
            <div class="mt-6 pt-6 border-t border-slate-200">
                <h4 class="text-sm font-semibold text-slate-700 mb-3">أمثلة سريعة:</h4>
                <div id="suggestedCharts" class="space-y-2">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>
        </div>
    </div>
    
    <!-- Right Panel: Chart Display -->
    <div class="lg:col-span-3">
        <!-- Chart Container -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 mb-8">
            <div id="chartDisplay" class="text-center py-16">
                <div class="w-24 h-24 bg-gradient-to-br from-sky-100 to-purple-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i data-lucide="bar-chart-3" class="w-12 h-12 text-sky-600"></i>
                </div>
                <h3 class="text-2xl font-bold text-slate-800 mb-2">ابدأ بإنشاء رسم بياني</h3>
                <p class="text-slate-600">اطرح سؤالاً أو اختر من الأمثلة السريعة</p>
            </div>
            
            <!-- Generated Chart -->
            <div id="chartContainer" class="hidden">
                <div class="flex items-center justify-between mb-6">
                    <h3 id="chartTitle" class="text-2xl font-bold text-slate-800"></h3>
                    <div class="flex gap-2">
                        <button onclick="exportChart('png')" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 rounded-lg transition text-sm font-semibold">
                            <i data-lucide="download" class="w-4 h-4 inline mr-1"></i>
                            PNG
                        </button>
                        <button onclick="exportChart('pdf')" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 rounded-lg transition text-sm font-semibold">
                            <i data-lucide="file-text" class="w-4 h-4 inline mr-1"></i>
                            PDF
                        </button>
                        <button onclick="exportChart('excel')" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 rounded-lg transition text-sm font-semibold">
                            <i data-lucide="table" class="w-4 h-4 inline mr-1"></i>
                            Excel
                        </button>
                    </div>
                </div>
                <canvas id="aiChart" class="w-full" style="max-height: 500px;"></canvas>
                <div id="chartInsights" class="mt-6 p-4 bg-sky-50 border-r-4 border-sky-500 rounded-lg">
                    <!-- Insights will be injected here -->
                </div>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-gradient-to-br from-sky-500 to-sky-700 text-white rounded-2xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 rounded-xl bg-white bg-opacity-20">
                        <i data-lucide="users" class="w-6 h-6"></i>
                    </div>
                    <i data-lucide="trending-up" class="w-5 h-5 opacity-70"></i>
                </div>
                <?php
                $totalStudents = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'")->fetch_assoc()['count'];
                ?>
                <h3 class="text-3xl font-bold mb-2"><?php echo $totalStudents; ?></h3>
                <p class="text-sky-100 text-sm">إجمالي الطلاب</p>
            </div>
            
            <div class="bg-gradient-to-br from-purple-500 to-purple-700 text-white rounded-2xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 rounded-xl bg-white bg-opacity-20">
                        <i data-lucide="book-open" class="w-6 h-6"></i>
                    </div>
                    <i data-lucide="activity" class="w-5 h-5 opacity-70"></i>
                </div>
                <?php
                $activeCourses = $conn->query("SELECT COUNT(*) as count FROM courses WHERE status = 'active'")->fetch_assoc()['count'];
                ?>
                <h3 class="text-3xl font-bold mb-2"><?php echo $activeCourses; ?></h3>
                <p class="text-purple-100 text-sm">دورات نشطة</p>
            </div>
            
            <div class="bg-gradient-to-br from-emerald-500 to-emerald-700 text-white rounded-2xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 rounded-xl bg-white bg-opacity-20">
                        <i data-lucide="award" class="w-6 h-6"></i>
                    </div>
                    <i data-lucide="check-circle" class="w-5 h-5 opacity-70"></i>
                </div>
                <?php
                $certificates = $conn->query("SELECT COUNT(*) as count FROM enrollments WHERE certificate_issued = 1")->fetch_assoc()['count'];
                ?>
                <h3 class="text-3xl font-bold mb-2"><?php echo $certificates; ?></h3>
                <p class="text-emerald-100 text-sm">شهادات صادرة</p>
            </div>
        </div>
    </div>
</div>

<!-- Load Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
let currentChart = null;
let currentChartData = null;

// Load suggested charts
function loadSuggestedCharts() {
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get_suggested_charts'
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const container = document.getElementById('suggestedCharts');
            container.innerHTML = data.suggestions.map(s => `
                <button onclick="setQuery('${s.query}')" 
                        class="w-full text-right px-3 py-2 text-sm bg-slate-50 hover:bg-slate-100 rounded-lg transition flex items-center gap-2">
                    <i data-lucide="${s.icon}" class="w-4 h-4 text-sky-600"></i>
                    <span class="flex-1">${s.query}</span>
                </button>
            `).join('');
            lucide.createIcons();
        }
    });
}

function setQuery(query) {
    document.getElementById('chartQuery').value = query;
}

function generateChart() {
    const query = document.getElementById('chartQuery').value.trim();
    const chartType = document.getElementById('chartType').value;
    
    if (!query) {
        alert('⚠️ الرجاء كتابة سؤال أو اختيار مثال');
        return;
    }
    
    // Show loading
    document.getElementById('chartDisplay').innerHTML = `
        <div class="py-16">
            <div class="animate-spin w-16 h-16 border-4 border-sky-600 border-t-transparent rounded-full mx-auto mb-6"></div>
            <h3 class="text-2xl font-bold text-slate-800 mb-2">جاري تحليل البيانات...</h3>
            <p class="text-slate-600">الذكاء الاصطناعي يعمل على إنشاء الرسم البياني</p>
        </div>
    `;
    document.getElementById('chartContainer').classList.add('hidden');
    
    // Generate chart
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=generate_chart&query=${encodeURIComponent(query)}&chart_type=${chartType}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            displayChart(data.chart);
            currentChartData = data;
        } else {
            alert('❌ خطأ: ' + data.message);
        }
    })
    .catch(err => {
        alert('❌ خطأ في الاتصال');
        console.error(err);
    });
}

function displayChart(chartConfig) {
    document.getElementById('chartDisplay').classList.add('hidden');
    document.getElementById('chartContainer').classList.remove('hidden');
    document.getElementById('chartTitle').textContent = chartConfig.title;
    
    // Destroy existing chart
    if (currentChart) {
        currentChart.destroy();
    }
    
    // Create new chart
    const ctx = document.getElementById('aiChart').getContext('2d');
    currentChart = new Chart(ctx, {
        type: chartConfig.type,
        data: {
            labels: chartConfig.labels,
            datasets: chartConfig.datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        font: {
                            family: 'system-ui',
                            size: 12
                        }
                    }
                }
            },
            scales: chartConfig.type !== 'pie' && chartConfig.type !== 'doughnut' ? {
                y: {
                    beginAtZero: true
                }
            } : {}
        }
    });
    
    // Display insights
    document.getElementById('chartInsights').innerHTML = `
        <div class="flex items-start gap-3">
            <i data-lucide="lightbulb" class="w-5 h-5 text-sky-600 mt-1"></i>
            <div>
                <h4 class="font-bold text-slate-800 mb-1">رؤى ذكية:</h4>
                <p class="text-slate-700 text-sm">${chartConfig.insights}</p>
            </div>
        </div>
    `;
    
    lucide.createIcons();
}

function exportChart(format) {
    if (!currentChartData) {
        alert('⚠️ لا يوجد رسم بياني للتصدير');
        return;
    }
    
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=export_chart&chart_data=${encodeURIComponent(JSON.stringify(currentChartData))}&format=${format}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // For now, download as image from canvas
            if (format === 'png') {
                const link = document.createElement('a');
                link.download = 'chart.png';
                link.href = document.getElementById('aiChart').toDataURL();
                link.click();
            } else {
                alert(`✅ سيتم تصدير الرسم البياني بصيغة ${format.toUpperCase()} قريباً`);
            }
        }
    });
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadSuggestedCharts();
    lucide.createIcons();
});
</script>
