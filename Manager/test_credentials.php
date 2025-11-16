<?php
/**
 * Test Login Credentials
 * اختبار بيانات الدخول
 */

require_once __DIR__ . '/../database/db.php';

$testCredentials = [
    [
        'email' => 'manager@ibdaa.edu.ye',
        'password' => 'Ibdaa@Manager2024',
        'role' => 'Manager'
    ],
    [
        'email' => 'technical@ibdaa.edu.ye',
        'password' => 'Ibdaa@Tech2024',
        'role' => 'Technical'
    ],
    [
        'email' => 'trainer@ibdaa.edu.ye',
        'password' => 'Ibdaa@Trainer2024',
        'role' => 'Trainer'
    ],
    [
        'email' => 'student@ibdaa.edu.ye',
        'password' => 'Ibdaa@Student2024',
        'role' => 'Student'
    ]
];

echo "====================================\n";
echo "   Testing Login Credentials\n";
echo "====================================\n\n";

foreach ($testCredentials as $cred) {
    echo "Testing {$cred['role']}...\n";
    echo "  Email: {$cred['email']}\n";
    
    $stmt = $conn->prepare("SELECT id, full_name, email, password_hash, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $cred['email']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        echo "  ✓ User found: {$user['full_name']}\n";
        echo "  Role in DB: {$user['role']}\n";
        
        if (password_verify($cred['password'], $user['password_hash'])) {
            echo "  ✓ Password verified successfully!\n";
        } else {
            echo "  ✗ Password verification FAILED!\n";
        }
    } else {
        echo "  ✗ User NOT found in database!\n";
    }
    
    echo "\n";
}

echo "====================================\n";
echo "Test Complete!\n";
echo "====================================\n";
?>
