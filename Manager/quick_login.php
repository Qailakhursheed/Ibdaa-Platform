<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔐 Quick Login - RBAC Testing</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-card {
            transition: all 0.3s ease;
        }
        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
        .role-badge {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            display: inline-block;
        }
        .role-manager { background: #dbeafe; color: #1e40af; }
        .role-technical { background: #fef3c7; color: #92400e; }
        .role-trainer { background: #d1fae5; color: #065f46; }
        .role-student { background: #e0e7ff; color: #3730a3; }
    </style>
</head>
<body class="p-6">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-2xl shadow-2xl p-8 mb-6">
            <div class="text-center">
                <div class="mb-4">
                    <i data-lucide="shield-check" class="w-16 h-16 text-purple-600 mx-auto"></i>
                </div>
                <h1 class="text-4xl font-bold text-gray-800 mb-2">
                    🔐 Quick Login - RBAC Testing
                </h1>
                <p class="text-gray-600 text-lg">
                    اختبار سريع لتسجيل الدخول - كلمة المرور الموحدة: <code class="bg-purple-100 text-purple-800 px-3 py-1 rounded font-mono">Test@123</code>
                </p>
            </div>
        </div>

        <!-- Login Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <?php
            $accounts = [
                [
                    'role' => 'manager',
                    'title' => 'المدير',
                    'email' => 'admin_manager@ibdaa.local',
                    'password' => 'Test@123',
                    'icon' => 'shield-check',
                    'color' => 'blue',
                    'pages' => 16,
                    'gradient' => 'from-blue-500 to-blue-600'
                ],
                [
                    'role' => 'technical',
                    'title' => 'المشرف الفني',
                    'email' => 'admin_tech@ibdaa.local',
                    'password' => 'Test@123',
                    'icon' => 'wrench',
                    'color' => 'amber',
                    'pages' => 12,
                    'gradient' => 'from-amber-500 to-amber-600'
                ],
                [
                    'role' => 'trainer',
                    'title' => 'المدرب',
                    'email' => 'albaheth@gamil.com',
                    'password' => 'Test@123',
                    'icon' => 'user-check',
                    'color' => 'green',
                    'pages' => 7,
                    'gradient' => 'from-green-500 to-green-600'
                ],
                [
                    'role' => 'student',
                    'title' => 'الطالب',
                    'email' => 'student1762618553716@ibdaa.edu.ye',
                    'password' => 'Test@123',
                    'icon' => 'graduation-cap',
                    'color' => 'indigo',
                    'pages' => '1 (واجهة منفصلة)',
                    'gradient' => 'from-indigo-500 to-indigo-600'
                ]
            ];

            foreach ($accounts as $account):
            ?>
            <div class="login-card bg-white rounded-xl shadow-lg overflow-hidden">
                <!-- Card Header -->
                <div class="bg-gradient-to-r <?php echo $account['gradient']; ?> p-6 text-white">
                    <div class="text-center mb-3">
                        <i data-lucide="<?php echo $account['icon']; ?>" class="w-12 h-12 mx-auto mb-2"></i>
                        <h2 class="text-2xl font-bold"><?php echo $account['title']; ?></h2>
                        <span class="role-badge role-<?php echo $account['role']; ?> mt-2">
                            <?php echo $account['role']; ?>
                        </span>
                    </div>
                    <div class="text-center text-sm opacity-90">
                        <?php echo $account['pages']; ?> صفحة
                    </div>
                </div>

                <!-- Card Body -->
                <div class="p-6">
                    <!-- Account Info -->
                    <div class="mb-4 space-y-2">
                        <div class="flex items-center gap-2 text-sm">
                            <i data-lucide="mail" class="w-4 h-4 text-gray-400"></i>
                            <input type="text" 
                                   value="<?php echo $account['email']; ?>" 
                                   readonly
                                   onclick="this.select()"
                                   class="flex-1 bg-gray-50 border border-gray-200 rounded px-2 py-1 text-xs cursor-pointer hover:bg-gray-100">
                        </div>
                        <div class="flex items-center gap-2 text-sm">
                            <i data-lucide="lock" class="w-4 h-4 text-gray-400"></i>
                            <input type="text" 
                                   value="<?php echo $account['password']; ?>" 
                                   readonly
                                   onclick="this.select()"
                                   class="flex-1 bg-gray-50 border border-gray-200 rounded px-2 py-1 text-xs cursor-pointer hover:bg-gray-100">
                        </div>
                    </div>

                    <!-- Login Button -->
                    <form action="login.php" method="POST" class="mb-3">
                        <input type="hidden" name="email" value="<?php echo $account['email']; ?>">
                        <input type="hidden" name="password" value="<?php echo $account['password']; ?>">
                        <button type="submit" 
                                class="w-full bg-gradient-to-r <?php echo $account['gradient']; ?> text-white font-bold py-3 px-4 rounded-lg 
                                       transition duration-300 transform hover:scale-105 shadow-lg
                                       flex items-center justify-center gap-2">
                            <i data-lucide="log-in" class="w-5 h-5"></i>
                            <span>تسجيل الدخول</span>
                        </button>
                    </form>

                    <!-- Copy Button -->
                    <button onclick="copyCredentials('<?php echo $account['email']; ?>', '<?php echo $account['password']; ?>')"
                            class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2 px-4 rounded-lg 
                                   transition duration-200 flex items-center justify-center gap-2 text-sm">
                        <i data-lucide="copy" class="w-4 h-4"></i>
                        <span>نسخ البيانات</span>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Instructions -->
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <h3 class="text-2xl font-bold text-gray-800 mb-4 flex items-center gap-3">
                <i data-lucide="info" class="w-8 h-8 text-blue-600"></i>
                تعليمات الاستخدام
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Method 1 -->
                <div class="bg-blue-50 border-2 border-blue-200 rounded-xl p-6">
                    <h4 class="font-bold text-lg text-blue-800 mb-3 flex items-center gap-2">
                        <span class="text-2xl">1️⃣</span>
                        <span>طريقة سريعة (زر واحد)</span>
                    </h4>
                    <ol class="space-y-2 text-gray-700">
                        <li class="flex items-start gap-2">
                            <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm">1</span>
                            <span>اضغط زر "تسجيل الدخول" تحت الدور المطلوب</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm">2</span>
                            <span>سيتم تسجيل الدخول تلقائياً</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm">3</span>
                            <span>تحقق من عدد روابط Sidebar</span>
                        </li>
                    </ol>
                </div>

                <!-- Method 2 -->
                <div class="bg-green-50 border-2 border-green-200 rounded-xl p-6">
                    <h4 class="font-bold text-lg text-green-800 mb-3 flex items-center gap-2">
                        <span class="text-2xl">2️⃣</span>
                        <span>طريقة النسخ</span>
                    </h4>
                    <ol class="space-y-2 text-gray-700">
                        <li class="flex items-start gap-2">
                            <span class="flex-shrink-0 w-6 h-6 bg-green-500 text-white rounded-full flex items-center justify-center text-sm">1</span>
                            <span>اضغط "نسخ البيانات"</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="flex-shrink-0 w-6 h-6 bg-green-500 text-white rounded-full flex items-center justify-center text-sm">2</span>
                            <span>افتح <a href="login.php" class="text-green-600 underline">login.php</a></span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="flex-shrink-0 w-6 h-6 bg-green-500 text-white rounded-full flex items-center justify-center text-sm">3</span>
                            <span>الصق البيانات المنسوخة</span>
                        </li>
                    </ol>
                </div>
            </div>

            <!-- Expected Results -->
            <div class="mt-6 bg-purple-50 border-2 border-purple-200 rounded-xl p-6">
                <h4 class="font-bold text-lg text-purple-800 mb-3 flex items-center gap-2">
                    <i data-lucide="check-circle" class="w-6 h-6"></i>
                    <span>النتائج المتوقعة</span>
                </h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                    <div class="bg-white rounded-lg p-4 border-2 border-blue-200">
                        <div class="text-3xl font-bold text-blue-600">16</div>
                        <div class="text-sm text-gray-600">Manager</div>
                    </div>
                    <div class="bg-white rounded-lg p-4 border-2 border-amber-200">
                        <div class="text-3xl font-bold text-amber-600">12</div>
                        <div class="text-sm text-gray-600">Technical</div>
                    </div>
                    <div class="bg-white rounded-lg p-4 border-2 border-green-200">
                        <div class="text-3xl font-bold text-green-600">7</div>
                        <div class="text-sm text-gray-600">Trainer</div>
                    </div>
                    <div class="bg-white rounded-lg p-4 border-2 border-indigo-200">
                        <div class="text-3xl font-bold text-indigo-600">UI</div>
                        <div class="text-sm text-gray-600">Student</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="mt-6 flex flex-wrap gap-4 justify-center">
            <a href="test_rbac.php" 
               class="inline-flex items-center gap-2 bg-white text-purple-600 font-bold py-3 px-6 
                      rounded-xl shadow-lg hover:bg-purple-50 transition duration-300">
                <i data-lucide="flask-conical" class="w-5 h-5"></i>
                <span>RBAC Testing Dashboard</span>
            </a>
            <a href="test_sidebar_counter.html" 
               class="inline-flex items-center gap-2 bg-white text-blue-600 font-bold py-3 px-6 
                      rounded-xl shadow-lg hover:bg-blue-50 transition duration-300">
                <i data-lucide="calculator" class="w-5 h-5"></i>
                <span>Sidebar Counter</span>
            </a>
            <a href="dashboard.php" 
               class="inline-flex items-center gap-2 bg-white text-green-600 font-bold py-3 px-6 
                      rounded-xl shadow-lg hover:bg-green-50 transition duration-300">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                <span>Dashboard</span>
            </a>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="fixed top-6 left-1/2 transform -translate-x-1/2 bg-green-600 text-white px-6 py-3 rounded-lg shadow-xl hidden transition-all">
        <div class="flex items-center gap-2">
            <i data-lucide="check-circle" class="w-5 h-5"></i>
            <span id="toastMessage"></span>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function copyCredentials(email, password) {
            const text = `Email: ${email}\nPassword: ${password}`;
            navigator.clipboard.writeText(text).then(() => {
                showToast('تم نسخ البيانات بنجاح! ✅');
            });
        }

        function showToast(message) {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toastMessage');
            toastMessage.textContent = message;
            toast.classList.remove('hidden');
            setTimeout(() => {
                toast.classList.add('hidden');
            }, 3000);
        }

        // Auto-scroll to top
        window.scrollTo(0, 0);
    </script>
</body>
</html>
