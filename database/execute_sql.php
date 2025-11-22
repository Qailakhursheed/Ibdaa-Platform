<?php
/**
 * Execute SQL Script
 * تنفيذ سكريبت SQL
 */

// Clean output buffer to prevent HTML errors
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable display errors for JSON output
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';

// Clear any output buffer that might contain HTML
ob_clean();

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);
$sql = $input['sql'] ?? '';

$response = [
    'success' => false,
    'executed' => 0,
    'messages' => [],
    'errors' => []
];

if (empty($sql)) {
    $response['message'] = 'No SQL provided';
    echo json_encode($response);
    exit;
}

try {
    // Disable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    
    // Split SQL into statements
    $statements = array_filter(
        array_map('trim', preg_split('/;[\r\n]+/', $sql)),
        function($stmt) {
            return !empty($stmt) && 
                   !preg_match('/^--/', $stmt) && 
                   !preg_match('/^\/\*/', $stmt);
        }
    );
    
    $executed = 0;
    $failed = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement)) continue;
        
        try {
            if ($conn->query($statement)) {
                $executed++;
                
                // Log specific actions
                if (preg_match('/CREATE TABLE.*`(\w+)`/i', $statement, $matches)) {
                    $response['messages'][] = "✅ Created table: {$matches[1]}";
                } elseif (preg_match('/DROP TABLE.*`(\w+)`/i', $statement, $matches)) {
                    $response['messages'][] = "🗑️ Dropped table: {$matches[1]}";
                } elseif (preg_match('/INSERT INTO.*`(\w+)`/i', $statement, $matches)) {
                    $response['messages'][] = "📝 Inserted data into: {$matches[1]}";
                }
            } else {
                $failed++;
                $error = $conn->error;
                
                // Skip certain errors
                if (strpos($error, 'already exists') === false && 
                    strpos($error, 'Duplicate entry') === false) {
                    $response['errors'][] = "Error: " . $error . " | Statement: " . substr($statement, 0, 100);
                }
            }
        } catch (Exception $e) {
            $failed++;
            $response['errors'][] = "Exception: " . $e->getMessage();
        }
    }
    
    // Re-enable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    
    $response['success'] = ($executed > 0);
    $response['executed'] = $executed;
    $response['failed'] = $failed;
    $response['message'] = "Executed $executed statements, $failed failed";
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    $response['errors'][] = $e->getTraceAsString();
}

// Ensure clean output
ob_end_clean();
ob_start();

// Send JSON response
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

// Flush and exit cleanly
ob_end_flush();
exit;
