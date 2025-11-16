<?php
// Test Database Connection
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Database Test</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f5f5;} .success{color:green;} .error{color:red;} .box{background:white;padding:20px;margin:10px 0;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);}</style>";
echo "</head><body>";

echo "<h1>🔍 فحص قاعدة البيانات</h1>";

// Test 1: Database Connection
echo "<div class='box'><h2>1️⃣ الاتصال بقاعدة البيانات</h2>";
try {
    require_once __DIR__ . '/../database/db.php';
    echo "<p class='success'>✅ نجح الاتصال بقاعدة البيانات</p>";
    echo "<p>الخادم: localhost</p>";
    echo "<p>قاعدة البيانات: ibdaa_platform</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ فشل الاتصال: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}
echo "</div>";

// Test 2: Check Tables
echo "<div class='box'><h2>2️⃣ الجداول الموجودة</h2>";
$result = $conn->query("SHOW TABLES");
if ($result) {
    $tables = [];
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }
    echo "<p class='success'>✅ عدد الجداول: " . count($tables) . "</p>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
} else {
    echo "<p class='error'>❌ فشل عرض الجداول</p>";
}
echo "</div>";

// Test 3: Check Users
echo "<div class='box'><h2>3️⃣ المستخدمون</h2>";
$result = $conn->query("SELECT COUNT(*) as count FROM users");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<p class='success'>✅ عدد المستخدمين: " . $row['count'] . "</p>";
} else {
    echo "<p class='error'>❌ فشل عد المستخدمين</p>";
}
echo "</div>";

// Test 4: Check Courses
echo "<div class='box'><h2>4️⃣ الدورات</h2>";
$result = $conn->query("SELECT COUNT(*) as count FROM courses");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<p class='success'>✅ عدد الدورات: " . $row['count'] . "</p>";
} else {
    echo "<p class='error'>❌ فشل عد الدورات</p>";
}
echo "</div>";

// Test 5: Check Session
echo "<div class='box'><h2>5️⃣ الجلسة (Session)</h2>";
session_start();
if (isset($_SESSION['user_id'])) {
    echo "<p class='success'>✅ مسجل دخول: " . htmlspecialchars($_SESSION['user_name'] ?? 'غير معروف') . "</p>";
    echo "<p>الدور: " . htmlspecialchars($_SESSION['user_role'] ?? 'غير محدد') . "</p>";
} else {
    echo "<p class='error'>⚠️ لم يتم تسجيل الدخول</p>";
}
echo "</div>";

echo "<div class='box'>";
echo "<a href='login.php' style='display:inline-block;background:#0ea5e9;color:white;padding:10px 20px;text-decoration:none;border-radius:8px;margin-right:10px;'>تسجيل الدخول</a>";
echo "<a href='dashboard.php' style='display:inline-block;background:#10b981;color:white;padding:10px 20px;text-decoration:none;border-radius:8px;'>لوحة التحكم</a>";
echo "</div>";

echo "</body></html>";
?>
