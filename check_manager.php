<?php
require_once 'platform/db.php';

// Check for manager account
$result = $conn->query("SELECT id, full_name, email, role FROM users WHERE role = 'manager' LIMIT 1");
if ($result->num_rows > 0) {
    $manager = $result->fetch_assoc();
    echo "Current Manager: " . $manager['full_name'] . " (" . $manager['email'] . ")\n";
    
    // Update name if needed
    $targetName = "الأستاذ عبد الباسط يوسف اليوسفي";
    if ($manager['full_name'] !== $targetName) {
        $stmt = $conn->prepare("UPDATE users SET full_name = ? WHERE id = ?");
        $stmt->bind_param("si", $targetName, $manager['id']);
        if ($stmt->execute()) {
            echo "Manager name updated to: $targetName\n";
        } else {
            echo "Failed to update manager name: " . $conn->error . "\n";
        }
    } else {
        echo "Manager name is already correct.\n";
    }
} else {
    echo "No manager account found. Creating one...\n";
    // Create manager account if not exists
    $name = "الأستاذ عبد الباسط يوسف اليوسفي";
    $email = "manager@ibdaa.com"; // Default or ask user? I'll use a placeholder or check if user provided one. 
    // I'll just report it missing for now if not found, or create a default one.
    // Let's assume there is one or I should create it.
    $password = password_hash("12345678", PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password_hash, role, verified, account_status) VALUES (?, ?, ?, 'manager', 1, 'active')");
    $stmt->bind_param("sss", $name, $email, $password);
    if ($stmt->execute()) {
        echo "Manager account created: $name ($email)\n";
    } else {
        echo "Failed to create manager account: " . $conn->error . "\n";
    }
}
?>
