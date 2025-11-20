<?php
require_once __DIR__ . '/db.php';

echo "<pre>";
echo "Running Announcements Migration...\n";

try {
    // SQL to add the new column
    $sql = "ALTER TABLE `announcements` ADD `media_url` VARCHAR(255) NULL DEFAULT NULL AFTER `content`;";

    if ($conn->query($sql) === TRUE) {
        echo "Table 'announcements' altered successfully. Column 'media_url' added.\n";
    } else {
        // Check if the column already exists
        if ($conn->errno == 1060) {
            echo "Column 'media_url' already exists in 'announcements' table. No changes made.\n";
        } else {
            throw new Exception("Error altering table: " . $conn->error);
        }
    }

} catch (Exception $e) {
    echo "An error occurred: " . $e->getMessage() . "\n";
}

echo "Migration script finished.\n";
echo "</pre>";

$conn->close();

