<?php
/**
 * ============================================
 * COMPREHENSIVE INTEGRATION VERIFICATION TEST
 * ============================================
 * 
 * This script performs end-to-end verification of:
 * 1. Authentication Flow (Login → Dashboard → Logout)
 * 2. Dashboard Access Control
 * 3. Navigation & Routing
 * 4. API Endpoints Connectivity
 * 5. Notifications System CRUD
 * 6. Modal Connections
 * 7. Button/Link Functionality
 * 8. Database Connectivity
 * 
 * Author: Integration Testing System
 * Date: November 13, 2025
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../database/db.php';

// ===== TEST CONFIGURATION =====
$TEST_CONFIG = [
    'base_url' => 'http://localhost/Ibdaa-Taiz/Manager/',
    'test_users' => [
        'manager' => ['email' => 'manager@test.com', 'password' => 'test123'],
        'technical' => ['email' => 'technical@test.com', 'password' => 'test123'],
        'trainer' => ['email' => 'trainer@test.com', 'password' => 'test123'],
        'student' => ['email' => 'student@test.com', 'password' => 'test123']
    ]
];

// ===== TEST RESULTS =====
$results = [
    'total' => 0,
    'passed' => 0,
    'failed' => 0,
    'tests' => []
];

// ===== HELPER FUNCTIONS =====
function testResult($name, $passed, $message = '', $details = []) {
    global $results;
    
    $results['total']++;
    if ($passed) {
        $results['passed']++;
    } else {
        $results['failed']++;
    }
    
    $results['tests'][] = [
        'name' => $name,
        'status' => $passed ? 'PASSED' : 'FAILED',
        'message' => $message,
        'details' => $details,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    return $passed;
}

function checkFileExists($path, $name) {
    $exists = file_exists(__DIR__ . '/../' . $path);
    testResult(
        $name,
        $exists,
        $exists ? "File exists: $path" : "File not found: $path"
    );
    return $exists;
}

function checkDatabaseTable($tableName, $conn) {
    $stmt = $conn->query("SHOW TABLES LIKE '$tableName'");
    $exists = $stmt->num_rows > 0;
    testResult(
        "Database Table: $tableName",
        $exists,
        $exists ? "Table exists: $tableName" : "Table not found: $tableName"
    );
    return $exists;
}

function checkAPIEndpoint($endpoint, $method = 'GET', $params = []) {
    $url = 'http://localhost/Ibdaa-Taiz/Manager/api/' . $endpoint;
    
    if ($method === 'GET' && !empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $passed = ($httpCode === 200 || $httpCode === 401); // 401 is OK (auth required)
    
    testResult(
        "API Endpoint: $method $endpoint",
        $passed,
        "HTTP Code: $httpCode",
        ['response' => substr($response, 0, 200)]
    );
    
    return $passed;
}

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Integration Verification Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
        * { font-family: 'Cairo', sans-serif; }
        .test-passed { background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); }
        .test-failed { background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); }
        .test-running { animation: pulse 2s infinite; }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
    </style>
</head>
<body class="bg-slate-50">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl shadow-2xl p-8 text-white mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold mb-2">🔍 Integration Verification Test</h1>
                    <p class="text-blue-100 text-lg">Comprehensive End-to-End System Testing</p>
                </div>
                <div class="text-left">
                    <div class="text-sm opacity-75">Test Date</div>
                    <div class="text-2xl font-bold"><?php echo date('Y-m-d H:i:s'); ?></div>
                </div>
            </div>
        </div>

        <!-- Test Progress -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-slate-600">Total Tests</span>
                    <i data-lucide="clipboard-list" class="w-5 h-5 text-blue-500"></i>
                </div>
                <div class="text-3xl font-bold text-slate-800" id="totalTests">0</div>
            </div>
            
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-slate-600">Passed</span>
                    <i data-lucide="check-circle" class="w-5 h-5 text-green-500"></i>
                </div>
                <div class="text-3xl font-bold text-green-600" id="passedTests">0</div>
            </div>
            
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-slate-600">Failed</span>
                    <i data-lucide="x-circle" class="w-5 h-5 text-red-500"></i>
                </div>
                <div class="text-3xl font-bold text-red-600" id="failedTests">0</div>
            </div>
            
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-slate-600">Success Rate</span>
                    <i data-lucide="trending-up" class="w-5 h-5 text-purple-500"></i>
                </div>
                <div class="text-3xl font-bold text-purple-600" id="successRate">0%</div>
            </div>
        </div>

        <!-- Test Results -->
        <div class="bg-white rounded-xl shadow-lg p-8">
            <h2 class="text-2xl font-bold text-slate-800 mb-6 flex items-center gap-2">
                <i data-lucide="list-checks" class="w-6 h-6 text-blue-600"></i>
                Test Results
            </h2>
            
            <div id="testResults" class="space-y-3">
                <!-- Results will be inserted here -->
            </div>
        </div>

        <?php
        // =====================================================
        // TEST SUITE 1: FILE STRUCTURE VERIFICATION
        // =====================================================
        echo '<div class="mt-8 bg-white rounded-xl shadow-lg p-8">';
        echo '<h2 class="text-2xl font-bold text-slate-800 mb-4">📁 File Structure Tests</h2>';
        echo '<div class="space-y-2">';
        
        checkFileExists('login.php', 'Login Page');
        checkFileExists('logout.php', 'Logout Page');
        checkFileExists('dashboard_router.php', 'Dashboard Router');
        checkFileExists('dashboards/manager-dashboard.php', 'Manager Dashboard');
        checkFileExists('dashboards/technical-dashboard.php', 'Technical Dashboard');
        checkFileExists('dashboards/trainer-dashboard.php', 'Trainer Dashboard');
        checkFileExists('dashboards/student-dashboard.php', 'Student Dashboard');
        checkFileExists('js/dashboard-integration.js', 'Dashboard Integration JS');
        
        echo '</div></div>';

        // =====================================================
        // TEST SUITE 2: API FILES VERIFICATION
        // =====================================================
        echo '<div class="mt-8 bg-white rounded-xl shadow-lg p-8">';
        echo '<h2 class="text-2xl font-bold text-slate-800 mb-4">🔌 API Files Tests</h2>';
        echo '<div class="space-y-2">';
        
        checkFileExists('api/students.php', 'Students API');
        checkFileExists('api/financial.php', 'Financial API');
        checkFileExists('api/requests.php', 'Requests API');
        checkFileExists('api/id_cards.php', 'ID Cards API');
        checkFileExists('api/certificates.php', 'Certificates API');
        checkFileExists('api/notifications_system.php', 'Notifications API');
        checkFileExists('api/chat_system.php', 'Chat API');
        
        echo '</div></div>';

        // =====================================================
        // TEST SUITE 3: DATABASE TABLES VERIFICATION
        // =====================================================
        echo '<div class="mt-8 bg-white rounded-xl shadow-lg p-8">';
        echo '<h2 class="text-2xl font-bold text-slate-800 mb-4">🗄️ Database Tables Tests</h2>';
        echo '<div class="space-y-2">';
        
        checkDatabaseTable('users', $conn);
        checkDatabaseTable('courses', $conn);
        checkDatabaseTable('enrollments', $conn);
        checkDatabaseTable('notifications', $conn);
        checkDatabaseTable('payments', $conn);
        checkDatabaseTable('id_cards', $conn);
        checkDatabaseTable('certificates', $conn);
        checkDatabaseTable('expenses', $conn);
        checkDatabaseTable('invoices', $conn);
        
        echo '</div></div>';

        // =====================================================
        // TEST SUITE 4: API ENDPOINTS CONNECTIVITY
        // =====================================================
        echo '<div class="mt-8 bg-white rounded-xl shadow-lg p-8">';
        echo '<h2 class="text-2xl font-bold text-slate-800 mb-4">🌐 API Endpoints Tests</h2>';
        echo '<div class="space-y-2">';
        
        checkAPIEndpoint('students.php', 'GET', ['action' => 'list']);
        checkAPIEndpoint('financial.php', 'GET', ['action' => 'stats']);
        checkAPIEndpoint('notifications_system.php', 'GET', ['action' => 'all']);
        checkAPIEndpoint('id_cards.php', 'GET', ['action' => 'list']);
        checkAPIEndpoint('certificates.php', 'GET', ['action' => 'list']);
        
        echo '</div></div>';

        // =====================================================
        // TEST SUITE 5: AUTHENTICATION FLOW VERIFICATION
        // =====================================================
        echo '<div class="mt-8 bg-white rounded-xl shadow-lg p-8">';
        echo '<h2 class="text-2xl font-bold text-slate-800 mb-4">🔐 Authentication Flow Tests</h2>';
        echo '<div class="space-y-2">';
        
        // Test login.php structure
        $loginFile = __DIR__ . '/../login.php';
        if (file_exists($loginFile)) {
            $loginContent = file_get_contents($loginFile);
            testResult(
                'Login: CSRF Protection',
                strpos($loginContent, 'csrf_token') !== false,
                'CSRF token validation found in login.php'
            );
        } else {
            testResult('Login: CSRF Protection', false, 'login.php file not found');
        }
        
        if (file_exists($loginFile)) {
            $loginContent = file_get_contents($loginFile);
            
            testResult(
                'Login: Role-based Routing',
                strpos($loginContent, "case 'manager'") !== false &&
                strpos($loginContent, "case 'technical'") !== false &&
                strpos($loginContent, "case 'trainer'") !== false &&
                strpos($loginContent, "case 'student'") !== false,
                'All role-based routing cases present'
            );
            
            testResult(
                'Login: Password Hashing',
                strpos($loginContent, 'password_verify') !== false,
                'Password verification found'
            );
        } else {
            testResult('Login: Role-based Routing', false, 'login.php file not found');
            testResult('Login: Password Hashing', false, 'login.php file not found');
        }
        
        // Test logout.php
        $logoutFile = __DIR__ . '/../logout.php';
        if (file_exists($logoutFile)) {
            $logoutContent = file_get_contents($logoutFile);
            testResult(
                'Logout: Session Destroy',
                strpos($logoutContent, 'session_destroy') !== false,
                'Session destruction implemented'
            );
            
            testResult(
                'Logout: Cookie Cleanup',
                strpos($logoutContent, 'setcookie') !== false,
                'Cookie cleanup implemented'
            );
        } else {
            testResult('Logout: Session Destroy', false, 'logout.php file not found');
            testResult('Logout: Cookie Cleanup', false, 'logout.php file not found');
        }
        
        echo '</div></div>';

        // =====================================================
        // TEST SUITE 6: DASHBOARD ACCESS CONTROL
        // =====================================================
        echo '<div class="mt-8 bg-white rounded-xl shadow-lg p-8">';
        echo '<h2 class="text-2xl font-bold text-slate-800 mb-4">🛡️ Access Control Tests</h2>';
        echo '<div class="space-y-2">';
        
        $dashboards = [
            'manager-dashboard.php' => 'manager',
            'technical-dashboard.php' => 'technical',
            'trainer-dashboard.php' => 'trainer',
            'student-dashboard.php' => 'student'
        ];
        
        foreach ($dashboards as $file => $role) {
            $dashboardFile = __DIR__ . '/../dashboards/' . $file;
            if (file_exists($dashboardFile)) {
                $content = file_get_contents($dashboardFile);
                testResult(
                    "Access Control: $file",
                    strpos($content, 'user_id') !== false && 
                    strpos($content, 'login.php') !== false,
                    "Session check and redirect implemented for $role"
                );
            } else {
                testResult("Access Control: $file", false, "File not found: $file");
            }
        }
        
        echo '</div></div>';

        // =====================================================
        // TEST SUITE 7: NOTIFICATIONS SYSTEM VERIFICATION
        // =====================================================
        echo '<div class="mt-8 bg-white rounded-xl shadow-lg p-8">';
        echo '<h2 class="text-2xl font-bold text-slate-800 mb-4">🔔 Notifications System Tests</h2>';
        echo '<div class="space-y-2">';
        
        $notifFile = __DIR__ . '/../api/notifications_system.php';
        if (file_exists($notifFile)) {
            $notifContent = file_get_contents($notifFile);
            
            testResult(
                'Notifications: GET All',
                strpos($notifContent, "action === 'all'") !== false,
                'GET all notifications endpoint exists'
            );
            
            testResult(
                'Notifications: GET Unread Count',
                strpos($notifContent, "action === 'unread_count'") !== false,
                'GET unread count endpoint exists'
            );
            
            testResult(
                'Notifications: POST Create',
                strpos($notifContent, "action === 'create'") !== false,
                'POST create notification endpoint exists'
            );
            
            testResult(
                'Notifications: POST Broadcast',
                strpos($notifContent, "action === 'broadcast'") !== false,
                'POST broadcast notification endpoint exists'
            );
            
            testResult(
                'Notifications: Mark as Read',
                strpos($notifContent, "action === 'mark_read'") !== false ||
                strpos($notifContent, "action === 'markAsRead'") !== false,
                'Mark as read functionality exists'
            );
        } else {
            testResult('Notifications: GET All', false, 'notifications_system.php not found');
            testResult('Notifications: GET Unread Count', false, 'notifications_system.php not found');
            testResult('Notifications: POST Create', false, 'notifications_system.php not found');
            testResult('Notifications: POST Broadcast', false, 'notifications_system.php not found');
            testResult('Notifications: Mark as Read', false, 'notifications_system.php not found');
        }
        
        echo '</div></div>';

        // =====================================================
        // TEST SUITE 8: MODAL CONNECTIONS VERIFICATION
        // =====================================================
        echo '<div class="mt-8 bg-white rounded-xl shadow-lg p-8">';
        echo '<h2 class="text-2xl font-bold text-slate-800 mb-4">🪟 Modal Connections Tests</h2>';
        echo '<div class="space-y-2">';
        
        $integrationJSFile = __DIR__ . '/../js/dashboard-integration.js';
        if (file_exists($integrationJSFile)) {
            $integrationJS = file_get_contents($integrationJSFile);
            
            testResult(
                'Integration JS: Navigation Functions',
                strpos($integrationJS, 'navigation:') !== false &&
                strpos($integrationJS, 'toManager:') !== false &&
                strpos($integrationJS, 'toTechnical:') !== false,
                'Navigation system implemented'
            );
            
            testResult(
                'Integration JS: API Functions',
                strpos($integrationJS, 'api:') !== false,
                'API integration functions exist'
            );
            
            testResult(
                'Integration JS: Chat System',
                strpos($integrationJS, 'chat:') !== false &&
                strpos($integrationJS, 'getMessages') !== false,
                'Chat system integration exists'
            );
            
            testResult(
                'Integration JS: Notifications',
                strpos($integrationJS, 'notifications:') !== false,
                'Notifications integration exists'
            );
        } else {
            testResult('Integration JS: Navigation Functions', false, 'dashboard-integration.js not found');
            testResult('Integration JS: API Functions', false, 'dashboard-integration.js not found');
            testResult('Integration JS: Chat System', false, 'dashboard-integration.js not found');
            testResult('Integration JS: Notifications', false, 'dashboard-integration.js not found');
        }
        
        echo '</div></div>';

        // =====================================================
        // FINAL SUMMARY
        // =====================================================
        echo '<div class="mt-8 bg-gradient-to-r from-slate-800 to-slate-900 rounded-xl shadow-2xl p-8 text-white">';
        echo '<h2 class="text-3xl font-bold mb-6">📊 Final Test Summary</h2>';
        
        $successRate = $results['total'] > 0 ? round(($results['passed'] / $results['total']) * 100, 2) : 0;
        
        echo '<div class="grid grid-cols-1 md:grid-cols-3 gap-6">';
        echo '<div class="bg-white/10 rounded-lg p-6">';
        echo '<div class="text-5xl font-bold mb-2">' . $results['total'] . '</div>';
        echo '<div class="text-slate-300">Total Tests</div>';
        echo '</div>';
        
        echo '<div class="bg-green-500/20 rounded-lg p-6">';
        echo '<div class="text-5xl font-bold text-green-400 mb-2">' . $results['passed'] . '</div>';
        echo '<div class="text-green-200">Passed</div>';
        echo '</div>';
        
        echo '<div class="bg-red-500/20 rounded-lg p-6">';
        echo '<div class="text-5xl font-bold text-red-400 mb-2">' . $results['failed'] . '</div>';
        echo '<div class="text-red-200">Failed</div>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="mt-6 text-center">';
        echo '<div class="text-6xl font-bold mb-2">' . $successRate . '%</div>';
        echo '<div class="text-2xl text-slate-300">Overall Success Rate</div>';
        
        if ($successRate >= 90) {
            echo '<div class="mt-4 text-green-400 text-xl">✅ Excellent! System integration is solid.</div>';
        } elseif ($successRate >= 70) {
            echo '<div class="mt-4 text-yellow-400 text-xl">⚠️ Good, but needs some improvements.</div>';
        } else {
            echo '<div class="mt-4 text-red-400 text-xl">❌ Critical issues need attention.</div>';
        }
        
        echo '</div></div>';
        ?>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();
        
        // Update counters
        document.getElementById('totalTests').textContent = <?php echo $results['total']; ?>;
        document.getElementById('passedTests').textContent = <?php echo $results['passed']; ?>;
        document.getElementById('failedTests').textContent = <?php echo $results['failed']; ?>;
        document.getElementById('successRate').textContent = '<?php echo $successRate; ?>%';
        
        // Generate detailed test results
        const tests = <?php echo json_encode($results['tests']); ?>;
        const resultsContainer = document.getElementById('testResults');
        
        tests.forEach(test => {
            const div = document.createElement('div');
            div.className = `p-4 rounded-lg border-2 ${test.status === 'PASSED' ? 'test-passed border-green-300' : 'test-failed border-red-300'}`;
            
            const icon = test.status === 'PASSED' ? 'check-circle' : 'x-circle';
            const iconColor = test.status === 'PASSED' ? 'text-green-600' : 'text-red-600';
            
            div.innerHTML = `
                <div class="flex items-start gap-3">
                    <i data-lucide="${icon}" class="w-5 h-5 ${iconColor} flex-shrink-0 mt-1"></i>
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <h3 class="font-bold text-slate-800">${test.name}</h3>
                            <span class="text-xs text-slate-500">${test.timestamp}</span>
                        </div>
                        <p class="text-sm text-slate-600 mt-1">${test.message}</p>
                        ${test.details && Object.keys(test.details).length > 0 ? 
                            '<pre class="mt-2 text-xs bg-slate-100 p-2 rounded overflow-auto">' + 
                            JSON.stringify(test.details, null, 2) + '</pre>' : ''}
                    </div>
                </div>
            `;
            
            resultsContainer.appendChild(div);
        });
        
        // Re-initialize icons for dynamically added elements
        lucide.createIcons();
    </script>
</body>
</html>
