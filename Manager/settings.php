<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$role = $_SESSION['user_role'] ?? 'student';
$isManager = ($role === 'manager');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الإعدادات - منصة إبداع</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; }
        .tab-active { border-bottom: 2px solid #2563eb; color: #2563eb; font-weight: 600; }
        .tab-inactive { color: #6b7280; }
        .tab-inactive:hover { color: #374151; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">
    <div class="min-h-screen flex flex-col">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200 px-6 py-4 flex justify-between items-center sticky top-0 z-10">
            <div class="flex items-center gap-4">
                <a href="dashboard.php" class="text-gray-500 hover:text-blue-600 transition-colors">
                    <i data-lucide="arrow-right" class="w-6 h-6"></i>
                </a>
                <h1 class="text-2xl font-bold text-gray-800">الإعدادات</h1>
            </div>
            <div class="flex items-center gap-3">
                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold">
                    <?php echo htmlspecialchars($role); ?>
                </span>
            </div>
        </header>

        <main class="flex-1 container mx-auto px-4 py-8 max-w-5xl">
            
            <!-- Tabs -->
            <div class="flex border-b border-gray-200 mb-8 overflow-x-auto">
                <button onclick="switchTab('profile')" id="tab-profile" class="tab-active px-6 py-3 flex items-center gap-2 transition-colors whitespace-nowrap">
                    <i data-lucide="user" class="w-5 h-5"></i>
                    <span>الملف الشخصي</span>
                </button>
                <button onclick="switchTab('security')" id="tab-security" class="tab-inactive px-6 py-3 flex items-center gap-2 transition-colors whitespace-nowrap">
                    <i data-lucide="lock" class="w-5 h-5"></i>
                    <span>الأمان وكلمة المرور</span>
                </button>
                <?php if ($isManager): ?>
                <button onclick="switchTab('platform')" id="tab-platform" class="tab-inactive px-6 py-3 flex items-center gap-2 transition-colors whitespace-nowrap">
                    <i data-lucide="settings" class="w-5 h-5"></i>
                    <span>إعدادات المنصة</span>
                </button>
                <?php endif; ?>
            </div>

            <!-- Content Areas -->
            
            <!-- 1. Profile Settings -->
            <div id="content-profile" class="space-y-6 animate-fade-in">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-xl font-bold mb-6 flex items-center gap-2">
                        <i data-lucide="image" class="w-5 h-5 text-blue-600"></i>
                        الصورة الشخصية
                    </h2>
                    <div class="flex items-center gap-6">
                        <div class="relative group">
                            <img id="current-avatar" src="../assets/images/default-avatar.png" alt="Avatar" class="w-24 h-24 rounded-full object-cover border-4 border-gray-100 shadow-md">
                            <div class="absolute inset-0 bg-black bg-opacity-40 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer" onclick="document.getElementById('avatar-upload').click()">
                                <i data-lucide="camera" class="w-8 h-8 text-white"></i>
                            </div>
                        </div>
                        <div>
                            <input type="file" id="avatar-upload" class="hidden" accept="image/*" onchange="uploadAvatar(this)">
                            <button onclick="document.getElementById('avatar-upload').click()" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors text-sm font-semibold">
                                تغيير الصورة
                            </button>
                            <p class="text-xs text-gray-500 mt-2">JPG, PNG or GIF. Max size 5MB.</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-xl font-bold mb-6 flex items-center gap-2">
                        <i data-lucide="user-check" class="w-5 h-5 text-blue-600"></i>
                        المعلومات الأساسية
                    </h2>
                    <form id="profile-form" onsubmit="updateProfile(event)" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">الاسم الكامل</label>
                                <input type="text" name="full_name" id="full_name" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">البريد الإلكتروني</label>
                                <input type="email" name="email" id="email" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">رقم الهاتف</label>
                                <input type="tel" name="phone" id="phone" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">نبذة تعريفية</label>
                            <textarea name="bio" id="bio" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                        </div>
                        <div class="flex justify-end pt-4">
                            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors font-semibold shadow-lg flex items-center gap-2">
                                <i data-lucide="save" class="w-4 h-4"></i>
                                حفظ التغييرات
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 2. Security Settings -->
            <div id="content-security" class="hidden space-y-6 animate-fade-in">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-xl font-bold mb-6 flex items-center gap-2">
                        <i data-lucide="key" class="w-5 h-5 text-blue-600"></i>
                        تغيير كلمة المرور
                    </h2>
                    <form id="password-form" onsubmit="changePassword(event)" class="max-w-md space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">كلمة المرور الحالية</label>
                            <input type="password" name="current_password" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">كلمة المرور الجديدة</label>
                            <input type="password" name="new_password" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">تأكيد كلمة المرور الجديدة</label>
                            <input type="password" name="confirm_password" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                        </div>
                        <div class="pt-4">
                            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors font-semibold shadow-lg flex items-center gap-2">
                                <i data-lucide="shield-check" class="w-4 h-4"></i>
                                تحديث كلمة المرور
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 3. Platform Settings (Manager Only) -->
            <?php if ($isManager): ?>
            <div id="content-platform" class="hidden space-y-6 animate-fade-in">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-xl font-bold mb-6 flex items-center gap-2">
                        <i data-lucide="globe" class="w-5 h-5 text-blue-600"></i>
                        إعدادات المنصة العامة
                    </h2>
                    <form id="platform-form" onsubmit="updatePlatformSettings(event)" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">اسم المنصة</label>
                                <input type="text" name="site_name" id="site_name" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">وصف المنصة</label>
                                <input type="text" name="site_desc" id="site_desc" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">بريد التواصل</label>
                                <input type="email" name="contact_email" id="contact_email" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">هاتف التواصل</label>
                                <input type="text" name="contact_phone" id="contact_phone" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                            </div>
                        </div>

                        <div class="border-t border-gray-100 pt-6">
                            <h3 class="text-lg font-semibold mb-4 text-gray-800">إعدادات البريد (SMTP)</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">SMTP Host</label>
                                    <input type="text" name="smtp_host" id="smtp_host" class="w-full border border-gray-300 rounded-lg px-4 py-2" dir="ltr">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">SMTP Port</label>
                                    <input type="text" name="smtp_port" id="smtp_port" class="w-full border border-gray-300 rounded-lg px-4 py-2" dir="ltr">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">SMTP User</label>
                                    <input type="text" name="smtp_user" id="smtp_user" class="w-full border border-gray-300 rounded-lg px-4 py-2" dir="ltr">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">SMTP Password</label>
                                    <input type="password" name="smtp_pass" id="smtp_pass" class="w-full border border-gray-300 rounded-lg px-4 py-2" dir="ltr" placeholder="********">
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-gray-100 pt-6">
                            <div class="flex items-center gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="maintenance_mode" id="maintenance_mode" value="1" class="w-5 h-5 text-blue-600 rounded">
                                    <span class="text-gray-700 font-medium">وضع الصيانة</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="allow_registration" id="allow_registration" value="1" class="w-5 h-5 text-blue-600 rounded">
                                    <span class="text-gray-700 font-medium">السماح بالتسجيل</span>
                                </label>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4">
                            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors font-semibold shadow-lg flex items-center gap-2">
                                <i data-lucide="save" class="w-4 h-4"></i>
                                حفظ إعدادات المنصة
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

        </main>
    </div>

    <script>
        lucide.createIcons();

        // Tab Switching Logic
        function switchTab(tabName) {
            // Hide all content
            ['profile', 'security', 'platform'].forEach(t => {
                const content = document.getElementById(`content-${t}`);
                const tab = document.getElementById(`tab-${t}`);
                if (content) content.classList.add('hidden');
                if (tab) {
                    tab.classList.remove('tab-active');
                    tab.classList.add('tab-inactive');
                }
            });

            // Show selected
            const selectedContent = document.getElementById(`content-${tabName}`);
            const selectedTab = document.getElementById(`tab-${tabName}`);
            
            if (selectedContent) selectedContent.classList.remove('hidden');
            if (selectedTab) {
                selectedTab.classList.remove('tab-inactive');
                selectedTab.classList.add('tab-active');
            }
        }

        // Load Data
        document.addEventListener('DOMContentLoaded', () => {
            loadProfile();
            <?php if ($isManager): ?>
            loadPlatformSettings();
            <?php endif; ?>
        });

        async function loadProfile() {
            try {
                const res = await fetch('api/settings_api.php?action=get_profile');
                const data = await res.json();
                if (data.success) {
                    const p = data.profile;
                    document.getElementById('full_name').value = p.full_name || '';
                    document.getElementById('email').value = p.email || '';
                    document.getElementById('phone').value = p.phone || '';
                    document.getElementById('bio').value = p.bio || '';
                    if (p.profile_picture) {
                        document.getElementById('current-avatar').src = '../' + p.profile_picture;
                    }
                }
            } catch (e) { console.error(e); }
        }

        async function updateProfile(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('action', 'update_profile');
            
            try {
                const res = await fetch('api/settings_api.php', { method: 'POST', body: formData });
                const data = await res.json();
                alert(data.message);
            } catch (e) { alert('Error updating profile'); }
        }

        async function changePassword(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('action', 'change_password');
            
            try {
                const res = await fetch('api/settings_api.php', { method: 'POST', body: formData });
                const data = await res.json();
                alert(data.message);
                if (data.success) e.target.reset();
            } catch (e) { alert('Error changing password'); }
        }

        async function uploadAvatar(input) {
            if (!input.files || !input.files[0]) return;
            
            const formData = new FormData();
            formData.append('action', 'upload_avatar');
            formData.append('avatar', input.files[0]);

            try {
                const res = await fetch('api/settings_api.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    document.getElementById('current-avatar').src = '../' + data.url;
                    alert('تم تحديث الصورة بنجاح');
                } else {
                    alert(data.message);
                }
            } catch (e) { alert('Error uploading avatar'); }
        }

        <?php if ($isManager): ?>
        async function loadPlatformSettings() {
            try {
                const res = await fetch('api/settings_api.php?action=get_platform_settings');
                const data = await res.json();
                if (data.success) {
                    const s = data.settings;
                    document.getElementById('site_name').value = s.site_name || '';
                    document.getElementById('site_desc').value = s.site_desc || '';
                    document.getElementById('contact_email').value = s.contact_email || '';
                    document.getElementById('contact_phone').value = s.contact_phone || '';
                    document.getElementById('smtp_host').value = s.smtp_host || '';
                    document.getElementById('smtp_port').value = s.smtp_port || '';
                    document.getElementById('smtp_user').value = s.smtp_user || '';
                    
                    if (s.maintenance_mode == '1') document.getElementById('maintenance_mode').checked = true;
                    if (s.allow_registration == '1') document.getElementById('allow_registration').checked = true;
                }
            } catch (e) { console.error(e); }
        }

        async function updatePlatformSettings(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('action', 'update_platform_settings');
            
            // Handle checkboxes manually as they might not be sent if unchecked
            if (!formData.has('maintenance_mode')) formData.append('maintenance_mode', '0');
            if (!formData.has('allow_registration')) formData.append('allow_registration', '0');

            try {
                const res = await fetch('api/settings_api.php', { method: 'POST', body: formData });
                const data = await res.json();
                alert(data.message);
            } catch (e) { alert('Error updating settings'); }
        }
        <?php endif; ?>
    </script>
</body>
</html>
