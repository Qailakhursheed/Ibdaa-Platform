<?php
/**
 * Password Hash Generator for Initial Users
 * توليد كلمات المرور المشفرة للمستخدمين الأوليين
 */

// كلمات المرور الأولية
$passwords = [
    'manager'  => 'Ibdaa@Manager2024',
    'technical' => 'Ibdaa@Tech2024',
    'trainer'   => 'Ibdaa@Trainer2024',
    'student'   => 'Ibdaa@Student2024'
];

echo "====================================\n";
echo "   Password Hash Generator\n";
echo "====================================\n\n";

foreach ($passwords as $role => $password) {
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    
    echo strtoupper($role) . ":\n";
    echo "  Plain: $password\n";
    echo "  Hash:  $hash\n";
    echo "  Verify: " . (password_verify($password, $hash) ? '✓ Valid' : '✗ Invalid') . "\n";
    echo "\n";
}

echo "====================================\n";
echo "Copy the hashes above to initial_users.sql\n";
echo "====================================\n";
?>
