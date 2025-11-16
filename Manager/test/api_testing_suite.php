<?php
/**
 * API Testing Suite
 * مجموعة اختبار شاملة لجميع الـ APIs
 * 
 * يختبر:
 * - الاتصال بقاعدة البيانات
 * - صلاحيات المستخدمين
 * - عمل جميع الـ APIs
 * - التكامل مع لوحة التحكم
 */

session_start();
require_once __DIR__ . '/../includes/db_connect.php';

// Set test user (Technical Supervisor)
$_SESSION['user_id'] = 1; // Change to actual technical supervisor ID
$_SESSION['role'] = 'technical';
$_SESSION['full_name'] = 'Technical Supervisor Test';

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Testing Suite</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 30px;
        }
        .test-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .test-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
        }
        .test-section h3 {
            color: #667eea;
            margin-bottom: 20px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .test-result {
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
            font-family: monospace;
        }
        .test-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .test-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        .test-info {
            background: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }
        .test-warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }
        .stats-card {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            margin-bottom: 10px;
        }
        .stats-card h2 {
            margin: 0;
            font-size: 2.5rem;
        }
        .stats-card p {
            margin: 5px 0 0 0;
        }
        .btn-test {
            margin: 5px;
        }
        pre {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="test-container">
        <div class="text-center mb-4">
            <h1><i class="fas fa-vial"></i> API Testing Suite</h1>
            <p class="text-muted">اختبار شامل لجميع الـ APIs الخاصة بالمشرف الفني</p>
        </div>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <h2 id="total-tests">0</h2>
                    <p>إجمالي الاختبارات</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card bg-success">
                    <h2 id="passed-tests">0</h2>
                    <p>نجح</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card bg-danger">
                    <h2 id="failed-tests">0</h2>
                    <p>فشل</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card bg-warning">
                    <h2 id="warnings">0</h2>
                    <p>تحذيرات</p>
                </div>
            </div>
        </div>

        <!-- Control Buttons -->
        <div class="text-center mb-4">
            <button class="btn btn-primary btn-lg" onclick="runAllTests()">
                <i class="fas fa-play"></i> تشغيل جميع الاختبارات
            </button>
            <button class="btn btn-secondary btn-lg" onclick="clearResults()">
                <i class="fas fa-eraser"></i> مسح النتائج
            </button>
        </div>

        <!-- Test Sections -->
        
        <!-- 1. Database Connection -->
        <div class="test-section">
            <h3><i class="fas fa-database"></i> 1. اختبار الاتصال بقاعدة البيانات</h3>
            <button class="btn btn-sm btn-primary btn-test" onclick="testDatabaseConnection()">اختبر الآن</button>
            <div id="db-results"></div>
        </div>

        <!-- 2. Students API -->
        <div class="test-section">
            <h3><i class="fas fa-user-graduate"></i> 2. Students API</h3>
            <button class="btn btn-sm btn-primary btn-test" onclick="testStudentsAPI()">اختبر الآن</button>
            <div id="students-results"></div>
        </div>

        <!-- 3. Financial API -->
        <div class="test-section">
            <h3><i class="fas fa-dollar-sign"></i> 3. Financial API</h3>
            <button class="btn btn-sm btn-primary btn-test" onclick="testFinancialAPI()">اختبر الآن</button>
            <div id="financial-results"></div>
        </div>

        <!-- 4. Requests API -->
        <div class="test-section">
            <h3><i class="fas fa-clipboard-list"></i> 4. Requests API</h3>
            <button class="btn btn-sm btn-primary btn-test" onclick="testRequestsAPI()">اختبر الآن</button>
            <div id="requests-results"></div>
        </div>

        <!-- 5. ID Cards API -->
        <div class="test-section">
            <h3><i class="fas fa-id-card"></i> 5. ID Cards API</h3>
            <button class="btn btn-sm btn-primary btn-test" onclick="testIDCardsAPI()">اختبر الآن</button>
            <div id="idcards-results"></div>
        </div>

        <!-- 6. Certificates API -->
        <div class="test-section">
            <h3><i class="fas fa-certificate"></i> 6. Certificates API</h3>
            <button class="btn btn-sm btn-primary btn-test" onclick="testCertificatesAPI()">اختبر الآن</button>
            <div id="certificates-results"></div>
        </div>

        <!-- 7. Permissions Test -->
        <div class="test-section">
            <h3><i class="fas fa-shield-alt"></i> 7. اختبار الصلاحيات</h3>
            <button class="btn btn-sm btn-primary btn-test" onclick="testPermissions()">اختبر الآن</button>
            <div id="permissions-results"></div>
        </div>

        <!-- 8. Integration Test -->
        <div class="test-section">
            <h3><i class="fas fa-link"></i> 8. اختبار التكامل</h3>
            <button class="btn btn-sm btn-primary btn-test" onclick="testIntegration()">اختبر الآن</button>
            <div id="integration-results"></div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let totalTests = 0;
    let passedTests = 0;
    let failedTests = 0;
    let warnings = 0;

    function updateStats() {
        document.getElementById('total-tests').textContent = totalTests;
        document.getElementById('passed-tests').textContent = passedTests;
        document.getElementById('failed-tests').textContent = failedTests;
        document.getElementById('warnings').textContent = warnings;
    }

    function logResult(containerId, message, type = 'info') {
        const container = document.getElementById(containerId);
        const div = document.createElement('div');
        div.className = `test-result test-${type}`;
        div.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : type === 'warning' ? 'exclamation-triangle' : 'info'}-circle"></i> ${message}`;
        container.appendChild(div);
        
        totalTests++;
        if (type === 'success') passedTests++;
        if (type === 'error') failedTests++;
        if (type === 'warning') warnings++;
        updateStats();
    }

    function clearResults() {
        const containers = ['db-results', 'students-results', 'financial-results', 'requests-results', 'idcards-results', 'certificates-results', 'permissions-results', 'integration-results'];
        containers.forEach(id => {
            document.getElementById(id).innerHTML = '';
        });
        totalTests = 0;
        passedTests = 0;
        failedTests = 0;
        warnings = 0;
        updateStats();
    }

    async function testDatabaseConnection() {
        const container = 'db-results';
        document.getElementById(container).innerHTML = '<div class="spinner-border text-primary" role="status"></div>';
        
        try {
            <?php
            // Test database connection
            if ($conn && $conn->ping()) {
                echo "logResult(container, 'الاتصال بقاعدة البيانات نشط ✓', 'success');";
                
                // Test tables
                $tables = ['users', 'courses', 'payments', 'expenses', 'invoices', 'id_cards', 'certificates', 'enrollments', 'notifications'];
                foreach ($tables as $table) {
                    $result = $conn->query("SHOW TABLES LIKE '$table'");
                    if ($result && $result->num_rows > 0) {
                        echo "logResult(container, 'جدول $table موجود ✓', 'success');";
                    } else {
                        echo "logResult(container, 'جدول $table غير موجود ✗', 'error');";
                    }
                }
                
                // Test key columns
                $columns = [
                    'users' => ['id', 'role', 'full_name', 'email'],
                    'payments' => ['id', 'student_id', 'amount', 'status'],
                    'certificates' => ['id', 'student_id', 'certificate_number', 'verification_code'],
                    'id_cards' => ['id', 'user_id', 'card_number', 'status']
                ];
                
                foreach ($columns as $table => $cols) {
                    $result = $conn->query("SHOW TABLES LIKE '$table'");
                    if ($result && $result->num_rows > 0) {
                        $columnResult = $conn->query("DESCRIBE $table");
                        $existingColumns = [];
                        while ($row = $columnResult->fetch_assoc()) {
                            $existingColumns[] = $row['Field'];
                        }
                        
                        foreach ($cols as $col) {
                            if (in_array($col, $existingColumns)) {
                                echo "logResult(container, 'عمود $table.$col موجود ✓', 'success');";
                            } else {
                                echo "logResult(container, 'عمود $table.$col غير موجود ✗', 'warning');";
                            }
                        }
                    }
                }
                
            } else {
                echo "logResult(container, 'فشل الاتصال بقاعدة البيانات ✗', 'error');";
            }
            ?>
        } catch (error) {
            logResult(container, 'خطأ في الاختبار: ' + error.message, 'error');
        }
    }

    async function testStudentsAPI() {
        const container = 'students-results';
        document.getElementById(container).innerHTML = '<div class="spinner-border text-primary" role="status"></div>';
        
        try {
            // Test list endpoint
            const response = await fetch('../api/students.php?action=list');
            if (response.ok) {
                const data = await response.json();
                logResult(container, `Students API - List: نجح (${data.count || 0} طالب) ✓`, 'success');
            } else {
                logResult(container, 'Students API - List: فشل ✗', 'error');
            }
            
            // Test statistics endpoint
            const statsResponse = await fetch('../api/students.php?action=statistics');
            if (statsResponse.ok) {
                const statsData = await statsResponse.json();
                logResult(container, 'Students API - Statistics: نجح ✓', 'success');
                logResult(container, `إحصائيات: ${JSON.stringify(statsData.data)}`, 'info');
            } else {
                logResult(container, 'Students API - Statistics: فشل ✗', 'error');
            }
        } catch (error) {
            logResult(container, 'خطأ في اختبار Students API: ' + error.message, 'error');
        }
    }

    async function testFinancialAPI() {
        const container = 'financial-results';
        document.getElementById(container).innerHTML = '<div class="spinner-border text-primary" role="status"></div>';
        
        try {
            // Test payments list
            const paymentsResponse = await fetch('../api/financial.php?action=list_payments');
            if (paymentsResponse.ok) {
                const data = await paymentsResponse.json();
                logResult(container, `Financial API - Payments: نجح (${data.count || 0} مدفوعات) ✓`, 'success');
            } else {
                logResult(container, 'Financial API - Payments: فشل ✗', 'error');
            }
            
            // Test expenses list
            const expensesResponse = await fetch('../api/financial.php?action=list_expenses');
            if (expensesResponse.ok) {
                const data = await expensesResponse.json();
                logResult(container, `Financial API - Expenses: نجح (${data.count || 0} مصروفات) ✓`, 'success');
            } else {
                logResult(container, 'Financial API - Expenses: فشل ✗', 'error');
            }
            
            // Test statistics
            const statsResponse = await fetch('../api/financial.php?action=statistics');
            if (statsResponse.ok) {
                const statsData = await statsResponse.json();
                logResult(container, 'Financial API - Statistics: نجح ✓', 'success');
                logResult(container, `إحصائيات: ${JSON.stringify(statsData.data)}`, 'info');
            } else {
                logResult(container, 'Financial API - Statistics: فشل ✗', 'error');
            }
        } catch (error) {
            logResult(container, 'خطأ في اختبار Financial API: ' + error.message, 'error');
        }
    }

    async function testRequestsAPI() {
        const container = 'requests-results';
        document.getElementById(container).innerHTML = '<div class="spinner-border text-primary" role="status"></div>';
        
        try {
            // Test list endpoint
            const response = await fetch('../api/requests.php?action=list');
            if (response.ok) {
                const data = await response.json();
                logResult(container, `Requests API - List: نجح (${data.count || 0} طلبات) ✓`, 'success');
            } else {
                logResult(container, 'Requests API - List: فشل ✗', 'error');
            }
            
            // Test statistics
            const statsResponse = await fetch('../api/requests.php?action=statistics');
            if (statsResponse.ok) {
                const statsData = await statsResponse.json();
                logResult(container, 'Requests API - Statistics: نجح ✓', 'success');
                logResult(container, `إحصائيات: ${JSON.stringify(statsData.data)}`, 'info');
            } else {
                logResult(container, 'Requests API - Statistics: فشل ✗', 'error');
            }
        } catch (error) {
            logResult(container, 'خطأ في اختبار Requests API: ' + error.message, 'error');
        }
    }

    async function testIDCardsAPI() {
        const container = 'idcards-results';
        document.getElementById(container).innerHTML = '<div class="spinner-border text-primary" role="status"></div>';
        
        try {
            // Test list endpoint
            const response = await fetch('../api/id_cards.php?action=list');
            if (response.ok) {
                const data = await response.json();
                logResult(container, `ID Cards API - List: نجح (${data.count || 0} بطاقات) ✓`, 'success');
            } else {
                logResult(container, 'ID Cards API - List: فشل ✗', 'error');
            }
            
            // Test statistics
            const statsResponse = await fetch('../api/id_cards.php?action=statistics');
            if (statsResponse.ok) {
                const statsData = await statsResponse.json();
                logResult(container, 'ID Cards API - Statistics: نجح ✓', 'success');
                logResult(container, `إحصائيات: ${JSON.stringify(statsData.data)}`, 'info');
            } else {
                logResult(container, 'ID Cards API - Statistics: فشل ✗', 'error');
            }
        } catch (error) {
            logResult(container, 'خطأ في اختبار ID Cards API: ' + error.message, 'error');
        }
    }

    async function testCertificatesAPI() {
        const container = 'certificates-results';
        document.getElementById(container).innerHTML = '<div class="spinner-border text-primary" role="status"></div>';
        
        try {
            // Test list endpoint
            const response = await fetch('../api/certificates.php?action=list');
            if (response.ok) {
                const data = await response.json();
                logResult(container, `Certificates API - List: نجح (${data.count || 0} شهادات) ✓`, 'success');
            } else {
                logResult(container, 'Certificates API - List: فشل ✗', 'error');
            }
            
            // Test statistics
            const statsResponse = await fetch('../api/certificates.php?action=statistics');
            if (statsResponse.ok) {
                const statsData = await statsResponse.json();
                logResult(container, 'Certificates API - Statistics: نجح ✓', 'success');
                logResult(container, `إحصائيات: ${JSON.stringify(statsData.data)}`, 'info');
            } else {
                logResult(container, 'Certificates API - Statistics: فشل ✗', 'error');
            }
        } catch (error) {
            logResult(container, 'خطأ في اختبار Certificates API: ' + error.message, 'error');
        }
    }

    async function testPermissions() {
        const container = 'permissions-results';
        document.getElementById(container).innerHTML = '<div class="spinner-border text-primary" role="status"></div>';
        
        <?php
        // Check user permissions
        $userId = $_SESSION['user_id'];
        $userRole = $_SESSION['role'];
        
        echo "logResult(container, 'المستخدم الحالي: {$_SESSION['full_name']}', 'info');";
        echo "logResult(container, 'الدور: {$userRole}', 'info');";
        
        if (in_array($userRole, ['manager', 'technical'])) {
            echo "logResult(container, 'صلاحية الوصول للـ APIs: ممنوحة ✓', 'success');";
        } else {
            echo "logResult(container, 'صلاحية الوصول للـ APIs: غير ممنوحة ✗', 'error');";
        }
        
        // Check database permissions
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if ($user) {
            $permissions = [
                'can_manage_students' => 'إدارة الطلاب',
                'can_manage_finance' => 'إدارة المالية',
                'can_manage_requests' => 'إدارة الطلبات',
                'can_manage_id_cards' => 'إدارة البطاقات',
                'can_manage_certificates' => 'إدارة الشهادات'
            ];
            
            foreach ($permissions as $perm => $label) {
                if (isset($user[$perm]) && $user[$perm] == 1) {
                    echo "logResult(container, 'صلاحية {$label}: ممنوحة ✓', 'success');";
                } else {
                    echo "logResult(container, 'صلاحية {$label}: غير ممنوحة (سيتم استخدام الدور)', 'warning');";
                }
            }
        }
        ?>
    }

    async function testIntegration() {
        const container = 'integration-results';
        document.getElementById(container).innerHTML = '<div class="spinner-border text-primary" role="status"></div>';
        
        logResult(container, 'اختبار صفحات لوحة التحكم...', 'info');
        
        const pages = [
            { name: 'Students Page', url: '../dashboards/technical/students.php' },
            { name: 'Finance Page', url: '../dashboards/technical/finance.php' },
            { name: 'Requests Page', url: '../dashboards/technical/requests.php' },
            { name: 'ID Cards Page', url: '../dashboards/technical/id_cards.php' },
            { name: 'Certificates Page', url: '../dashboards/technical/certificates.php' }
        ];
        
        for (const page of pages) {
            try {
                const response = await fetch(page.url);
                if (response.ok) {
                    logResult(container, `${page.name}: متاحة ✓`, 'success');
                } else {
                    logResult(container, `${page.name}: خطأ ${response.status} ✗`, 'error');
                }
            } catch (error) {
                logResult(container, `${page.name}: غير متاحة ✗`, 'error');
            }
        }
    }

    async function runAllTests() {
        clearResults();
        await testDatabaseConnection();
        await new Promise(resolve => setTimeout(resolve, 500));
        await testStudentsAPI();
        await new Promise(resolve => setTimeout(resolve, 500));
        await testFinancialAPI();
        await new Promise(resolve => setTimeout(resolve, 500));
        await testRequestsAPI();
        await new Promise(resolve => setTimeout(resolve, 500));
        await testIDCardsAPI();
        await new Promise(resolve => setTimeout(resolve, 500));
        await testCertificatesAPI();
        await new Promise(resolve => setTimeout(resolve, 500));
        await testPermissions();
        await new Promise(resolve => setTimeout(resolve, 500));
        await testIntegration();
        
        // Show summary
        setTimeout(() => {
            const percentage = totalTests > 0 ? Math.round((passedTests / totalTests) * 100) : 0;
            alert(`اكتمل الاختبار!\n\nالنتيجة: ${percentage}%\nإجمالي: ${totalTests}\nنجح: ${passedTests}\nفشل: ${failedTests}\nتحذيرات: ${warnings}`);
        }, 1000);
    }

    // Initialize
    updateStats();
</script>

</body>
</html>
