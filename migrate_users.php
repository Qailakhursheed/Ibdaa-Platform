<?php
require_once __DIR__ . '/database/db.php';

// Check users table columns
$columns = [
    'account_status' => "ENUM('active', 'pending', 'suspended') DEFAULT 'active'",
    'payment_complete' => "TINYINT(1) DEFAULT 1" // Default 1 for existing users to not break them
];

foreach ($columns as $col => $def) {
    try {
        $conn->query("SELECT $col FROM users LIMIT 1");
    } catch (Exception $e) {
        $conn->query("ALTER TABLE users ADD COLUMN $col $def");
        echo "Added column $col to users table.\n";
    }
}

echo "Users table migration complete.\n";
