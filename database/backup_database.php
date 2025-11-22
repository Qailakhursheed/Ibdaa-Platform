<?php
/**
 * Backup Database
 * نسخ احتياطي لقاعدة البيانات
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
    'success' => false,
    'message' => ''
];

try {
    $dbname = 'ibdaa_taiz';
    $timestamp = date('Y-m-d_H-i-s');
    $filename = "backup_{$dbname}_{$timestamp}.sql";
    $filepath = __DIR__ . "/backups/{$filename}";
    
    // Create backups directory if not exists
    if (!is_dir(__DIR__ . '/backups')) {
        mkdir(__DIR__ . '/backups', 0755, true);
    }
    
    // Get all tables
    $tables = [];
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }
    
    // Start building SQL
    $sql = "-- Ibdaa Platform Database Backup\n";
    $sql .= "-- Date: " . date('Y-m-d H:i:s') . "\n";
    $sql .= "-- Database: {$dbname}\n\n";
    $sql .= "SET NAMES utf8mb4;\n";
    $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
    
    foreach ($tables as $table) {
        // Table structure
        $result = $conn->query("SHOW CREATE TABLE `{$table}`");
        $row = $result->fetch_assoc();
        $sql .= "\n-- Table: {$table}\n";
        $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
        $sql .= $row['Create Table'] . ";\n\n";
        
        // Table data
        $result = $conn->query("SELECT * FROM `{$table}`");
        if ($result->num_rows > 0) {
            $sql .= "-- Data for table: {$table}\n";
            while ($row = $result->fetch_assoc()) {
                $values = array_map(function($val) use ($conn) {
                    return $val === null ? 'NULL' : "'" . $conn->real_escape_string($val) . "'";
                }, array_values($row));
                $sql .= "INSERT INTO `{$table}` VALUES (" . implode(', ', $values) . ");\n";
            }
            $sql .= "\n";
        }
    }
    
    $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
    
    // Write to file
    file_put_contents($filepath, $sql);
    
    $response['success'] = true;
    $response['message'] = 'Backup created successfully';
    $response['filename'] = $filename;
    $response['path'] = $filepath;
    $response['size'] = human_filesize(filesize($filepath));
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

function human_filesize($bytes, $decimals = 2) {
    $size = ['B', 'KB', 'MB', 'GB', 'TB'];
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . @$size[$factor];
}

// Ensure clean output
ob_end_clean();
ob_start();

// Send JSON response
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

// Flush and exit cleanly
ob_end_flush();
exit;
