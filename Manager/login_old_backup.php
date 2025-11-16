<?php
// DEBUG MODE - Remove after testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

$error = '';
$debug = []; // Debug info
$debug[] = "Script started at " . date('Y-m-d H:i:s');
$debug[] = "Request Method: " . $_SERVER['REQUEST_METHOD'];
$debug[] = "POST data count: " . count($_POST);

if (!empty($_POST)) {
    $debug[] = "POST keys: " . implode(', ', array_keys($_POST));
}

// Try to load database
try {
    require_once __DIR__ . '/../platform/db.php';
    $debug[] = "✅ Database connected successfully";
    if (!isset($conn)) {
        $debug[] = "⚠️ WARNING: \$conn is not set!";
        require_once __DIR__ . '/../database/db.php';
        $debug[] = "Tried alternative path: database/db.php";
    }
} catch (Exception $e) {
    $debug[] = "❌ Database connection error: " . $e->getMessage();
    $error = 'خطأ في الاتصال بقاعدة البيانات.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $debug[] = "📨 POST request received";
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $debug[] = "Email: $email";
    $debug[] = "Password length: " . strlen($password);

    if (empty($email) || empty($password)) {
        $error = 'الرجاء إدخال البريد الإلكتروني وكلمة المرور.';
        $debug[] = "ERROR: Empty email or password";
    } else {
        $debug[] = "Checking password_hash column...";
        // detect if password_hash column exists
        $resPwd = $conn->query("SHOW COLUMNS FROM users LIKE 'password_hash'");
        $hasHash = ($resPwd && $resPwd->num_rows > 0);
        $debug[] = "Has password_hash column: " . ($hasHash ? 'YES' : 'NO');

        if ($hasHash) {
            $stmt = $conn->prepare("SELECT id, full_name, email, password_hash, role, verified, photo FROM users WHERE email = ? LIMIT 1");
        } else {
            $stmt = $conn->prepare("SELECT id, full_name, email, password, role, verified, photo FROM users WHERE email = ? LIMIT 1");
        }

        if ($stmt) {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $res = $stmt->get_result();
            $debug[] = "Query executed. Rows found: " . ($res ? $res->num_rows : 0);
            
            if ($res && $res->num_rows === 1) {
                $user = $res->fetch_assoc();
                $debug[] = "User found: " . $user['full_name'] . " (Role: " . $user['role'] . ")";
                
                $ok = false;
                if ($hasHash && !empty($user['password_hash'])) {
                    $debug[] = "Verifying password with password_verify()...";
                    if (password_verify($password, $user['password_hash'])) {
                        $ok = true;
                        $debug[] = "✅ Password verified successfully!";
                    } else {
                        $debug[] = "❌ Password verification FAILED";
                    }
                } else {
                    $debug[] = "Using legacy plain password check...";
                    // legacy plain password
                    if ($password === $user['password']) {
                        $ok = true;
                        $debug[] = "✅ Plain password matched!";
                    } else {
                        $debug[] = "❌ Plain password did not match";
                    }
                }

                if ($ok) {
                    $debug[] = "✅ Login SUCCESS! Setting session and redirecting...";
                    // login success
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['full_name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['user_photo'] = $user['photo'] ?? null;

                    $debug[] = "Session set. Redirecting to dashboard.php...";
                    
                    // All roles now use unified dashboard.php
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error = 'بيانات الدخول غير صحيحة.';
                }
            } else {
                $error = 'لا يوجد مستخدم بهذا البريد.';
            }
        } else {
            $error = 'خطأ داخلي في السيرفر.';
        }
    }
}
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>تسجيل دخول - بوابة الإدارة</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
  <style>body{font-family:'Cairo',sans-serif}</style>
</head>
<body class="min-h-screen bg-sky-50 flex items-center justify-center p-6">
  <div class="max-w-md w-full bg-white rounded-2xl shadow-xl p-8">
    <div class="text-center mb-6">
      <img src="../platform/photos/Sh.jpg" alt="شعار" class="w-20 h-20 mx-auto rounded-full border-4 border-sky-400 mb-4">
      <h1 class="text-2xl font-bold">تسجيل دخول المنصة</h1>
      <p class="text-sm text-gray-500">قم بتسجيل الدخول للوصول إلى لوحة التحكم المناسبة لدورك</p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="mb-4 text-center text-red-700 bg-red-50 p-3 rounded">
        <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="" class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">البريد الإلكتروني</label>
        <input name="email" type="text" required 
               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
               placeholder="admin_manager@ibdaa.local"
               class="mt-1 block w-full rounded px-3 py-2 border border-gray-300" />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">كلمة المرور</label>
        <input name="password" type="password" required 
               placeholder="Test@123"
               class="mt-1 block w-full rounded px-3 py-2 border border-gray-300" />
      </div>
      <div class="pt-3">
        <button type="submit" name="login_submit" value="1" class="w-full bg-sky-600 hover:bg-sky-700 text-white py-2 rounded-lg font-bold">
          تسجيل الدخول
        </button>
      </div>
    </form>

    <div class="mt-4 text-center text-sm text-gray-500">
      <p>يمكن للطلاب التسجيل عبر <a href="../platform/signup.php" class="text-sky-600 underline">صفحة التسجيل</a></p>
    </div>
    
    <?php if (!empty($debug)): ?>
    <!-- DEBUG INFO - Remove after testing -->
    <div class="mt-6 p-4 bg-gray-900 text-white rounded-lg text-xs font-mono">
      <div class="font-bold mb-2 text-yellow-400">🔍 DEBUG LOG:</div>
      <?php foreach ($debug as $msg): ?>
        <div class="py-1 border-b border-gray-700"><?php echo htmlspecialchars($msg); ?></div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</body>
</html>
