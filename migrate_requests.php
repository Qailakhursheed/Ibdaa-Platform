<?php
require_once __DIR__ . '/database/db.php';

// Create registration_requests table if not exists
$sql = "CREATE TABLE IF NOT EXISTS registration_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    dob DATE,
    gender ENUM('male', 'female') DEFAULT NULL,
    governorate VARCHAR(100),
    district VARCHAR(100),
    course_id INT,
    id_file_path VARCHAR(255),
    photo_path VARCHAR(255),
    notes TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    reviewed_by INT DEFAULT NULL,
    reviewed_at DATETIME DEFAULT NULL,
    rejection_reason TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "Table registration_requests created/checked successfully.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

// Check if columns exist and add if not (simple check)
$columns = [
    'id_file_path' => 'VARCHAR(255)',
    'photo_path' => 'VARCHAR(255)',
    'notes' => 'TEXT'
];

foreach ($columns as $col => $type) {
    try {
        $conn->query("SELECT $col FROM registration_requests LIMIT 1");
    } catch (Exception $e) {
        // Column likely doesn't exist
        $conn->query("ALTER TABLE registration_requests ADD COLUMN $col $type");
        echo "Added column $col\n";
    }
}

echo "Migration complete.\n";
