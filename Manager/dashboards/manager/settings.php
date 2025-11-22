<?php
/**
 * Manager - Settings Page
 * صفحة الإعدادات
 */

// معالجة حفظ الإعدادات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_settings') {
        // هنا يمكن حفظ الإعدادات في ملف أو قاعدة بيانات
        $platform_name = $_POST['platform_name'] ?? '';
        $platform_email = $_POST['platform_email'] ?? '';
        $platform_phone = $_POST['platform_phone'] ?? '';
        
        // حفظ مؤقت في session
        $_SESSION['settings_updated'] = true;
        header('Location: ?page=settings&msg=updated');
        exit;
    }
}

// الإعدادات الافتراضية
$settings = [
    'platform_name' => 'منصة إبداع للتدريب',
    'platform_email' => 'info@ibdaa-taiz.com',
    'platform_phone' => '+967 777 123 456',
    'enable_notifications' => true,
    'enable_chat' => true,
    'enable_certificates' => true,
    'default_currency' => 'YER',
    'timezone' => 'Asia/Aden'
];
?>

<!-- Success Message -->
<?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
<div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg mb-6">
    تم حفظ الإعدادات بنجاح
</div>
<?php endif; ?>

<!-- Settings Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Settings Form -->
    <div class="lg:col-span-2">
        <form method="POST" class="bg-white rounded-2xl shadow-sm border overflow-hidden">
            <input type="hidden" name="action" value="update_settings">
            
            <div class="p-6 border-b bg-gradient-to-r from-sky-50 to-white">
                <h3 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                    <i data-lucide="settings" class="w-6 h-6 text-sky-600"></i>
                    إعدادات المنصة
                </h3>
                <p class="text-sm text-slate-600 mt-1">تخصيص إعدادات النظام والمنصة</p>
            </div>

            <div class="p-6 space-y-6">
                <!-- Platform Information -->
                <div class="space-y-4">
                    <h4 class="font-bold text-slate-800 flex items-center gap-2">
                        <i data-lucide="info" class="w-5 h-5 text-sky-600"></i>
                        معلومات المنصة
                    </h4>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">اسم المنصة</label>
                        <input type="text" name="platform_name" value="<?php echo htmlspecialchars($settings['platform_name']); ?>" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">البريد الإلكتروني</label>
                        <input type="email" name="platform_email" value="<?php echo htmlspecialchars($settings['platform_email']); ?>" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">رقم الهاتف</label>
                        <input type="tel" name="platform_phone" value="<?php echo htmlspecialchars($settings['platform_phone']); ?>" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                    </div>
                </div>

                <!-- System Settings -->
                <div class="space-y-4 pt-6 border-t">
                    <h4 class="font-bold text-slate-800 flex items-center gap-2">
                        <i data-lucide="sliders" class="w-5 h-5 text-sky-600"></i>
                        إعدادات النظام
                    </h4>

                    <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg">
                        <div>
                            <p class="font-medium text-slate-800">تفعيل الإشعارات</p>
                            <p class="text-sm text-slate-600">إرسال إشعارات للمستخدمين</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="enable_notifications" class="sr-only peer" <?php echo $settings['enable_notifications'] ? 'checked' : ''; ?>>
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-sky-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-sky-600"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg">
                        <div>
                            <p class="font-medium text-slate-800">تفعيل الدردشة</p>
                            <p class="text-sm text-slate-600">نظام المراسلة الداخلي</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="enable_chat" class="sr-only peer" <?php echo $settings['enable_chat'] ? 'checked' : ''; ?>>
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-sky-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-sky-600"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg">
                        <div>
                            <p class="font-medium text-slate-800">تفعيل الشهادات</p>
                            <p class="text-sm text-slate-600">إصدار شهادات للخريجين</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="enable_certificates" class="sr-only peer" <?php echo $settings['enable_certificates'] ? 'checked' : ''; ?>>
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-sky-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-sky-600"></div>
                        </label>
                    </div>
                </div>

                <!-- Regional Settings -->
                <div class="space-y-4 pt-6 border-t">
                    <h4 class="font-bold text-slate-800 flex items-center gap-2">
                        <i data-lucide="globe" class="w-5 h-5 text-sky-600"></i>
                        الإعدادات الإقليمية
                    </h4>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">العملة الافتراضية</label>
                        <select name="default_currency" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                            <option value="YER" <?php echo $settings['default_currency'] === 'YER' ? 'selected' : ''; ?>>ريال يمني (YER)</option>
                            <option value="SAR">ريال سعودي (SAR)</option>
                            <option value="USD">دولار أمريكي (USD)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">المنطقة الزمنية</label>
                        <select name="timezone" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                            <option value="Asia/Aden" <?php echo $settings['timezone'] === 'Asia/Aden' ? 'selected' : ''; ?>>عدن (GMT+3)</option>
                            <option value="Asia/Riyadh">الرياض (GMT+3)</option>
                            <option value="Asia/Dubai">دبي (GMT+4)</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="p-6 border-t bg-slate-50 flex gap-3">
                <button type="submit" class="px-6 py-2 bg-sky-600 text-white rounded-lg hover:bg-sky-700 transition">
                    <i data-lucide="save" class="w-4 h-4 inline"></i>
                    حفظ التغييرات
                </button>
                <button type="button" onclick="location.reload()" class="px-6 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 transition">
                    إلغاء
                </button>
            </div>
        </form>
    </div>

    <!-- Quick Actions Sidebar -->
    <div class="space-y-6">
        <!-- System Info -->
        <div class="bg-white rounded-2xl shadow-sm border p-6">
            <h4 class="font-bold text-slate-800 mb-4 flex items-center gap-2">
                <i data-lucide="server" class="w-5 h-5 text-emerald-600"></i>
                معلومات النظام
            </h4>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-600">إصدار PHP:</span>
                    <span class="font-medium"><?php echo phpversion(); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-600">النظام:</span>
                    <span class="font-medium"><?php echo PHP_OS; ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-600">حالة قاعدة البيانات:</span>
                    <span class="text-emerald-600 font-medium">متصل</span>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="bg-white rounded-2xl shadow-sm border p-6">
            <h4 class="font-bold text-slate-800 mb-4 flex items-center gap-2">
                <i data-lucide="zap" class="w-5 h-5 text-amber-600"></i>
                إجراءات سريعة
            </h4>
            <div class="space-y-2">
                <a href="?page=dashboard" class="block p-3 rounded-lg bg-slate-50 hover:bg-slate-100 transition text-sm">
                    <i data-lucide="home" class="w-4 h-4 inline"></i>
                    العودة للوحة التحكم
                </a>
                <a href="?page=users" class="block p-3 rounded-lg bg-slate-50 hover:bg-slate-100 transition text-sm">
                    <i data-lucide="users" class="w-4 h-4 inline"></i>
                    إدارة المستخدمين
                </a>
                <a href="../logout.php" class="block p-3 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 transition text-sm">
                    <i data-lucide="log-out" class="w-4 h-4 inline"></i>
                    تسجيل الخروج
                </a>
            </div>
        </div>

        <!-- Backup -->
        <div class="bg-gradient-to-br from-violet-50 to-white rounded-2xl shadow-sm border border-violet-200 p-6">
            <h4 class="font-bold text-slate-800 mb-3 flex items-center gap-2">
                <i data-lucide="database" class="w-5 h-5 text-violet-600"></i>
                النسخ الاحتياطي
            </h4>
            <p class="text-sm text-slate-600 mb-4">آخر نسخة احتياطية: لم يتم</p>
            <button onclick="alert('سيتم إضافة وظيفة النسخ الاحتياطي قريباً')" class="w-full px-4 py-2 bg-violet-600 text-white rounded-lg hover:bg-violet-700 transition text-sm">
                <i data-lucide="download" class="w-4 h-4 inline"></i>
                إنشاء نسخة احتياطية
            </button>
        </div>
    </div>
</div>

<script>
if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}
</script>
