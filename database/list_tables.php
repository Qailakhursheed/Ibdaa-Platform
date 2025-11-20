<?php
require_once __DIR__ . '/db.php';

$result = $conn->query("SHOW TABLES");

if ($result) {
    echo "Tables in database '{$dbname}':\n";
    while ($row = $result->fetch_row()) {
        echo "- " . $row[0] . "\n";
    }
} else {
    echo "Error listing tables: " . $conn->error;
}

$conn->close();

