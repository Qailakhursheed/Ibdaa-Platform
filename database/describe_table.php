<?php
require_once __DIR__ . '/db.php';

$result = $conn->query("DESCRIBE notifications");

if ($result) {
    echo "Schema for table 'notifications':\n";
    echo "----------------------------------\n";
    while ($row = $result->fetch_assoc()) {
        echo str_pad($row['Field'], 20) . " | " . str_pad($row['Type'], 20) . " | " . str_pad($row['Null'], 5) . " | " . $row['Key'] . "\n";
    }
} else {
    echo "Error describing table: " . $conn->error;
}

$conn->close();

