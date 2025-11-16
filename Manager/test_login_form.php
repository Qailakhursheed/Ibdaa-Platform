<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>Test Login Form</title>
    <style>
        body { font-family: Arial; padding: 50px; background: #f0f0f0; }
        .container { max-width: 400px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        button { width: 100%; padding: 15px; background: #0066cc; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; }
        button:hover { background: #0052a3; }
        .debug { margin-top: 20px; padding: 15px; background: #000; color: #0f0; font-family: monospace; font-size: 12px; border-radius: 5px; }
        .debug div { margin: 5px 0; }
        .success { color: #0f0; }
        .error { color: #f00; }
        .info { color: #0ff; }
    </style>
</head>
<body>
    <div class="container">
        <h2>🔐 Test Login Form</h2>
        
        <?php
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        $debug = [];
        $debug[] = "⏰ Time: " . date('Y-m-d H:i:s');
        $debug[] = "📋 Method: " . $_SERVER['REQUEST_METHOD'];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $debug[] = "✅ POST RECEIVED!";
            $debug[] = "📧 Email: " . ($_POST['email'] ?? 'NOT SET');
            $debug[] = "🔑 Password: " . (isset($_POST['password']) ? '[' . strlen($_POST['password']) . ' chars]' : 'NOT SET');
            $debug[] = "📦 POST data: " . print_r($_POST, true);
            
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if ($email === 'admin_manager@ibdaa.local' && $password === 'Test@123') {
                $debug[] = "🎉 SUCCESS! Login credentials are correct!";
                echo '<div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                    ✅ تسجيل الدخول نجح! البيانات صحيحة!
                </div>';
            } else {
                $debug[] = "❌ FAILED! Wrong credentials";
                echo '<div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                    ❌ البيانات غير صحيحة
                </div>';
            }
        } else {
            $debug[] = "⏳ Waiting for form submission...";
        }
        ?>
        
        <form method="POST" action="">
            <div>
                <label>البريد الإلكتروني:</label>
                <input type="text" 
                       name="email" 
                       value="admin_manager@ibdaa.local" 
                       required>
            </div>
            
            <div>
                <label>كلمة المرور:</label>
                <input type="password" 
                       name="password" 
                       value="Test@123" 
                       required>
            </div>
            
            <button type="submit">تسجيل الدخول</button>
        </form>
        
        <div class="debug">
            <strong style="color: yellow;">🔍 DEBUG LOG:</strong><br>
            <?php foreach ($debug as $msg): ?>
                <div><?php echo htmlspecialchars($msg); ?></div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
