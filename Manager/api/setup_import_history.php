<?php
require_once __DIR__ . '/../../database/db.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS import_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        file_name VARCHAR(255) NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        import_type VARCHAR(50) NOT NULL,
        imported_by INT NOT NULL,
        imported_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        records_count INT DEFAULT 0,
        status VARCHAR(50) DEFAULT 'success',
        file_path VARCHAR(255) NOT NULL,
        INDEX (imported_by),
        INDEX (import_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    if ($conn->query($sql) === TRUE) {
        echo "Table 'import_history' created successfully or already exists.";
    } else {
        echo "Error creating table: " . $conn->error;
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>