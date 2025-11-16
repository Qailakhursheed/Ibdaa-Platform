<?php
// Test password verification
$stored_hash = '$2y$10$84Y6N/6Axtd1dN0YqzAByO/2V4GEoEi/kHatrV0BPSc.6mJ31LSrO';
$test_password = 'Test@123';

echo "Testing password verification:\n";
echo "Password: " . $test_password . "\n";
echo "Hash: " . $stored_hash . "\n";
echo "Hash Length: " . strlen($stored_hash) . "\n";
echo "\n";

if (password_verify($test_password, $stored_hash)) {
    echo "✅ SUCCESS: Password verification PASSED!\n";
} else {
    echo "❌ FAILED: Password verification FAILED!\n";
}

echo "\n";
echo "Now you can login with:\n";
echo "Email: admin_manager@ibdaa.local\n";
echo "Password: Test@123\n";
?>
