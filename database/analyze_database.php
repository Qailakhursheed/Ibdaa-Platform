<?php
/**
 * Database Structure Analyzer
 * تحليل هيكل قاعدة البيانات الحالية
 */

// Clean output buffer to prevent HTML errors
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable display errors for JSON output
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';

// Clear any output buffer that might contain HTML
ob_clean();

$response = [
    'database_name' => 'ibdaa_taiz',
    'connection_status' => false,
    'tables' => [],
    'table_details' => [],
    'issues' => []
];

try {
    // 1. التحقق من الاتصال
    if ($conn->ping()) {
        $response['connection_status'] = true;
        
        // 2. جلب جميع الجداول
        $result = $conn->query("SHOW TABLES");
        while ($row = $result->fetch_array()) {
            $tableName = $row[0];
            $response['tables'][] = $tableName;
            
            // 3. جلب تفاصيل كل جدول
            $countResult = $conn->query("SELECT COUNT(*) as count FROM `$tableName`");
            $count = $countResult->fetch_assoc()['count'];
            
            $structureResult = $conn->query("SHOW COLUMNS FROM `$tableName`");
            $columns = [];
            while ($col = $structureResult->fetch_assoc()) {
                $columns[] = [
                    'Field' => $col['Field'],
                    'Type' => $col['Type'],
                    'Null' => $col['Null'],
                    'Key' => $col['Key'],
                    'Default' => $col['Default'],
                    'Extra' => $col['Extra']
                ];
            }
            
            $response['table_details'][$tableName] = [
                'row_count' => $count,
                'columns' => $columns,
                'column_count' => count($columns)
            ];
        }
        
        // 4. فحص الجداول المطلوبة
        $required_tables = [
            'users',
            'courses',
            'enrollments',
            'payments',
            'attendance',
            'grades',
            'notifications',
            'certificates',
            'announcements',
            'registration_requests',
            'messages',
            'settings'
        ];
        
        foreach ($required_tables as $table) {
            if (!in_array($table, $response['tables'])) {
                $response['issues'][] = "Missing table: $table";
            }
        }
        
        // 5. فحص جدول users
        if (in_array('users', $response['tables'])) {
            $result = $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
            $response['users_by_role'] = [];
            while ($row = $result->fetch_assoc()) {
                $response['users_by_role'][$row['role']] = $row['count'];
            }
        }
        
    } else {
        $response['connection_status'] = false;
        $response['issues'][] = 'Database connection failed';
    }
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

// Ensure clean output
ob_end_clean();
ob_start();

// Send JSON response
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// Flush and exit cleanly
ob_end_flush();
exit;
