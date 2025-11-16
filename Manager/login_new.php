<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../database/db.php';

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'الرجاء إدخال البريد الإلكتروني وكلمة المرور.';
    } else {
        $stmt = $conn->prepare("SELECT id, full_name, email, password_hash, role, photo FROM users WHERE email = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $res = $stmt->get_result();
            
            if ($res && $res->num_rows === 1) {
                $user = $res->fetch_assoc();
                
                if (password_verify($password, $user['password_hash'])) {
                    // Login success
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['full_name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['role'] = $user['role']; // Fallback
                    $_SESSION['user_photo'] = $user['photo'] ?? null;
                    
                    $success = true;
                    
                    // Redirect to dashboard
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error = 'بيانات الدخول غير صحيحة.';
                }
            } else {
                $error = 'لا يوجد مستخدم بهذا البريد.';
            }
            $stmt->close();
        } else {
            $error = 'خطأ داخلي في السيرفر.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - منصة إبداع</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-sky-100 to-indigo-100 flex items-center justify-center p-6">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-2xl p-8">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="w-24 h-24 mx-auto mb-4 bg-gradient-to-br from-sky-500 to-indigo-600 rounded-full flex items-center justify-center">
                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">تسجيل دخول المنصة</h1>
            <p class="text-gray-600">قم بتسجيل الدخول للوصول إلى لوحة التحكم</p>
        </div>

        <!-- Error Message -->
        <?php if (!empty($error)): ?>
        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-lg">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" action="" class="space-y-6">
            <div>
                <label for="email" class="block text-sm font-bold text-gray-700 mb-2">
                    البريد الإلكتروني
                </label>
                <input 
                    type="text" 
                    id="email" 
                    name="email" 
                    required
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                    placeholder="admin_manager@ibdaa.local"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent transition"
                >
            </div>

            <div>
                <label for="password" class="block text-sm font-bold text-gray-700 mb-2">
                    كلمة المرور
                </label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required
                    placeholder="••••••••"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent transition"
                >
            </div>

            <button 
                type="submit" 
                class="w-full bg-gradient-to-r from-sky-500 to-indigo-600 hover:from-sky-600 hover:to-indigo-700 text-white font-bold py-3 px-6 rounded-lg shadow-lg transform transition hover:scale-105 focus:outline-none focus:ring-4 focus:ring-sky-300"
            >
                تسجيل الدخول
            </button>
        </form>

        <!-- Quick Test Accounts -->
        <div class="mt-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
            <p class="text-xs font-bold text-gray-700 mb-2">🔐 حسابات الاختبار:</p>
            <div class="space-y-1 text-xs text-gray-600">
                <div>📧 Email: <code class="bg-white px-2 py-1 rounded">admin_manager@ibdaa.local</code></div>
                <div>🔑 Password: <code class="bg-white px-2 py-1 rounded">Test@123</code></div>
            </div>
        </div>

        <!-- Student Signup Link -->
        <div class="mt-6 text-center text-sm text-gray-600">
            <p>
                للطلاب: يمكنكم التسجيل عبر 
                <a href="../platform/signup.php" class="text-sky-600 hover:text-sky-800 font-bold underline">
                    صفحة التسجيل
                </a>
            </p>
        </div>

        <!-- Quick Links -->
        <div class="mt-6 flex gap-2 justify-center">
            <a href="test_login_form.php" class="text-xs text-gray-500 hover:text-sky-600">Test Form</a>
            <span class="text-gray-300">|</span>
            <a href="quick_login.php" class="text-xs text-gray-500 hover:text-sky-600">Quick Login</a>
            <span class="text-gray-300">|</span>
            <a href="test_rbac.php" class="text-xs text-gray-500 hover:text-sky-600">RBAC Test</a>
        </div>
    </div>
</body>
</html>
