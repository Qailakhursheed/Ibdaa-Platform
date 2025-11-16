<?php
// Visit this file once via browser: http://localhost/Ibdaa-Taiz/Manager/create_admin.php
// After creating accounts, delete this file.
require_once __DIR__ . '/../platform/db.php';

function col_exists($conn, $col) {
    $r = $conn->query("SHOW COLUMNS FROM users LIKE '" . $conn->real_escape_string($col) . "'");
    return ($r && $r->num_rows > 0);
}

header('Content-Type: text/html; charset=utf-8');
echo "<!doctype html><html lang=\"ar\" dir=\"rtl\"><head><meta charset=\"utf-8\"><meta name=\"viewport\" content=\"width=device-width,initial-scale=1\"><title>إنشاء حسابات الإدارة</title>";
echo "<style>body{font-family:'Cairo',sans-serif;padding:20px;line-height:1.7} .ok{color:green;font-weight:700} .warn{color:orange} .err{color:red}</style></head><body>";
echo "<h1>إنشاء حسابات المدير والمشرف الفني</h1>";

// credentials (change if you wish)
$manager_email = 'admin_manager@ibdaa.local';
$manager_pass_plain = 'Manager@2025';
$manager_name = 'المدير العام';
$manager_role = 'manager';

$tech_email = 'admin_tech@ibdaa.local';
$tech_pass_plain = 'Technical@2025';
$tech_name = 'المشرف الفني';
$tech_role = 'technical';

$hasHash = col_exists($conn, 'password_hash');

function create_user_if_missing($conn, $email, $plain, $name, $role, $hasHash) {
    // check by email
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    if (!$stmt) return ['ok'=>false,'msg'=>'prepare_failed_select'];
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) return ['ok'=>true,'exists'=>true];

    // build insert depending on password column
    if ($hasHash) {
        $pw_hash = password_hash($plain, PASSWORD_DEFAULT);
        // prepare insert - protect against missing columns by checking common ones
        $cols = ['email','full_name','password_hash','role','created_at'];
        $place = implode(',', array_fill(0, count($cols), '?'));
        $sql = "INSERT INTO users (" . implode(',', $cols) . ") VALUES ($place)";
        $stmt2 = $conn->prepare($sql);
        if (!$stmt2) return ['ok'=>false,'msg'=>'prepare_failed_insert'];
        $created_at = date('Y-m-d H:i:s');
        $stmt2->bind_param('sssss', $email, $name, $pw_hash, $role, $created_at);
        if ($stmt2->execute()) return ['ok'=>true,'exists'=>false];
        return ['ok'=>false,'msg'=>$stmt2->error];
    } else {
        // legacy password column
        $pw_hash = $plain; // store plain? better to hash into `password` even if legacy
        if (col_exists($conn, 'password')) {
            $pw_hash = password_hash($plain, PASSWORD_DEFAULT);
            $cols = ['email','full_name','password','role','created_at'];
            $place = implode(',', array_fill(0, count($cols), '?'));
            $sql = "INSERT INTO users (" . implode(',', $cols) . ") VALUES ($place)";
            $stmt2 = $conn->prepare($sql);
            if (!$stmt2) return ['ok'=>false,'msg'=>'prepare_failed_insert2'];
            $created_at = date('Y-m-d H:i:s');
            $stmt2->bind_param('sssss', $email, $name, $pw_hash, $role, $created_at);
            if ($stmt2->execute()) return ['ok'=>true,'exists'=>false];
            return ['ok'=>false,'msg'=>$stmt2->error];
        } else {
            return ['ok'=>false,'msg'=>'no_password_column'];
        }
    }
}

// create manager
$r = create_user_if_missing($conn, $manager_email, $manager_pass_plain, $manager_name, $manager_role, $hasHash);
if (isset($r['ok']) && $r['ok'] && empty($r['exists'])) {
    echo "<p class=\"ok\">✅ تم إنشاء حساب المدير العام ($manager_email)</p>";
} elseif (isset($r['exists']) && $r['exists']) {
    echo "<p class=\"warn\">- حساب المدير العام موجود مسبقاً ($manager_email)</p>";
} else {
    echo "<p class=\"err\">خطأ عند إنشاء المدير: " . htmlspecialchars($r['msg'] ?? 'unknown') . "</p>";
}

// create technical
$r2 = create_user_if_missing($conn, $tech_email, $tech_pass_plain, $tech_name, $tech_role, $hasHash);
if (isset($r2['ok']) && $r2['ok'] && empty($r2['exists'])) {
    echo "<p class=\"ok\">✅ تم إنشاء حساب المشرف الفني ($tech_email)</p>";
} elseif (isset($r2['exists']) && $r2['exists']) {
    echo "<p class=\"warn\">- حساب المشرف الفني موجود مسبقاً ($tech_email)</p>";
} else {
    echo "<p class=\"err\">خطأ عند إنشاء المشرف الفني: " . htmlspecialchars($r2['msg'] ?? 'unknown') . "</p>";
}

echo "<hr><h2>بيانات الدخول (احفظها)</h2>";
echo "<h3>المدير العام:</h3><ul><li><strong>البريد الإلكتروني:</strong> $manager_email</li><li><strong>كلمة المرور:</strong> $manager_pass_plain</li></ul>";
echo "<h3>المشرف الفني:</h3><ul><li><strong>البريد الإلكتروني:</strong> $tech_email</li><li><strong>كلمة المرور:</strong> $tech_pass_plain</li></ul>";

echo "<p style=\"color:red;font-weight:700\">هام جداً: احذف هذا الملف الآن بعد إنشاء الحسابات.</p>";
echo "</body></html>";

?>
<?php
/**
 * Create test users: manager, technical, trainer, student
 * Usage: place this file in Manager/ and visit once via browser:
 * http://localhost/Ibdaa-Taiz/Manager/create_admin.php
 * IMPORTANT: delete this file after use.
 */
require_once __DIR__ . '/../platform/db.php';

header('Content-Type: text/html; charset=utf-8');
echo "<meta charset=\"utf-8\"><title>Create test users</title><style>body{font-family:Arial,\n sans-serif;padding:20px}</style>";

$testUsers = [
    ['full_name' => 'Admin Test', 'email' => 'admin@local.test', 'password' => 'AdminPass123!', 'role' => 'manager'],
    ['full_name' => 'Tech Test', 'email' => 'tech@local.test', 'password' => 'TechPass123!', 'role' => 'technical'],
    ['full_name' => 'Trainer Test', 'email' => 'trainer@local.test', 'password' => 'TrainerPass123!', 'role' => 'trainer'],
    ['full_name' => 'Student Test', 'email' => 'student@local.test', 'password' => 'StudentPass123!', 'role' => 'student'],
];

// detect schema columns
$resPwd = $conn->query("SHOW COLUMNS FROM users LIKE 'password_hash'");
$hasPasswordHash = ($resPwd && $resPwd->num_rows > 0);
$resPhoto = $conn->query("SHOW COLUMNS FROM users LIKE 'photo_path'");
$hasPhotoPath = ($resPhoto && $resPhoto->num_rows > 0);

echo "<h2>إنشاء حسابات اختبار</h2>";
echo "<p>سيتم إنشاء الحسابات التالية (حذف الملف بعد الانتهاء ضروري):</p>";
echo "<ul>";
foreach ($testUsers as $u) {
    echo "<li>" . htmlspecialchars($u['email']) . " — كلمة المرور: <strong>" . htmlspecialchars($u['password']) . "</strong></li>";
}
echo "</ul>";

echo "<hr><h3>نتيجة الإنشاء</h3>";
foreach ($testUsers as $u) {
    // check exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param('s', $u['email']);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        echo "<p>" . htmlspecialchars($u['email']) . " — <em>موجود مسبقاً</em></p>";
        continue;
    }
    $passwordHash = password_hash($u['password'], PASSWORD_BCRYPT);

    // build insert according to schema
    $cols = ['full_name', 'email'];
    $place = ['?', '?'];
    $types = 'ss';
    $vals = [&$u['full_name'], &$u['email']];

    if ($hasPasswordHash) {
        $cols[] = 'password_hash'; $place[] = '?'; $types .= 's'; $vals[] = &$passwordHash;
    } else {
        $cols[] = 'password'; $place[] = '?'; $types .= 's'; $vals[] = &$passwordHash;
    }

    $cols[] = 'role'; $place[] = '?'; $types .= 's'; $vals[] = &$u['role'];
    $cols[] = 'verified'; $place[] = '1';

    $cols_sql = implode(', ', $cols);
    $place_sql = implode(', ', $place);
    $sql = "INSERT INTO users ({$cols_sql}) VALUES ({$place_sql})";
    $ins = $conn->prepare($sql);
    if ($ins === false) {
        echo "<p style='color:red'>خطأ في تجهيز الاستعلام: " . htmlspecialchars($conn->error) . "</p>";
        continue;
    }
    // bind params dynamically (exclude the trailing literal 1 for verified)
    // compute bind types and values length
    $bindTypes = $types;
    // count placeholders that are '?' (not the literal '1')
    $paramCount = substr_count($place_sql, '?');
    $bindArgs = [];
    $bindArgs[] = $bindTypes;
    $i = 0;
    foreach ($vals as $v) {
        if ($i >= $paramCount) break;
        $bindArgs[] = &$vals[$i];
        $i++;
    }
    // call bind_param
    call_user_func_array([$ins, 'bind_param'], $bindArgs);
    $ok = $ins->execute();
    if ($ok) {
        echo "<p style='color:green'>تم إنشاء: " . htmlspecialchars($u['email']) . "</p>";
    } else {
        echo "<p style='color:red'>فشل عند إنشاء " . htmlspecialchars($u['email']) . ": " . htmlspecialchars($ins->error) . "</p>";
    }
}

echo "<hr><p>بعد الانتهاء: احذف الملف <code>Manager/create_admin.php</code> لمنع إساءة الاستخدام.</p>";

?>
