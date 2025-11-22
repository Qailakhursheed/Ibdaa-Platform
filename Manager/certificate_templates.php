<?php
session_start();
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../includes/session_security.php';

SessionSecurity::startSecureSession();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['manager', 'technical'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة قوالب الشهادات - منصة إبداع</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; background-color: #f8fafc; }
    </style>
</head>
<body class="text-slate-800">

<div class="flex h-screen">
    <!-- Sidebar -->
    <aside class="w-64 bg-white border-l border-slate-200 hidden md:flex flex-col">
        <div class="p-6 border-b border-slate-200">
            <h1 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="award" class="text-blue-600"></i>
                إدارة الشهادات
            </h1>
        </div>
        <nav class="flex-1 p-4 space-y-1">
            <a href="dashboard_router.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 hover:bg-slate-50">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                <span>لوحة التحكم</span>
            </a>
            <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-blue-50 text-blue-600 font-medium">
                <i data-lucide="file-check" class="w-5 h-5"></i>
                <span>قوالب الشهادات</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-screen overflow-hidden">
        <!-- Header -->
        <header class="bg-white border-b border-slate-200 p-6 flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">قوالب الشهادات</h2>
                <p class="text-slate-500 mt-1">إدارة وتصميم قوالب الشهادات المعتمدة</p>
            </div>
            <a href="certificate_designer.php" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium flex items-center gap-2">
                <i data-lucide="plus" class="w-4 h-4"></i>
                تصميم قالب جديد
            </a>
        </header>

        <!-- Content -->
        <div class="flex-1 bg-slate-50 p-8 overflow-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="templatesGrid">
                <!-- Templates will be loaded here -->
                <div class="col-span-full text-center py-12">
                    <i data-lucide="loader-2" class="w-8 h-8 animate-spin mx-auto text-blue-600 mb-4"></i>
                    <p class="text-slate-500">جاري تحميل القوالب...</p>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    lucide.createIcons();

    async function loadTemplates() {
        try {
            const response = await fetch('api/manage_certificate_templates.php?action=getAll');
            const data = await response.json();
            
            const grid = document.getElementById('templatesGrid');
            grid.innerHTML = '';

            if (data.success && data.templates.length > 0) {
                data.templates.forEach(template => {
                    const card = document.createElement('div');
                    card.className = 'bg-white rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition overflow-hidden group';
                    card.innerHTML = `
                        <div class="h-48 bg-slate-100 flex items-center justify-center relative">
                            <i data-lucide="file-check" class="w-12 h-12 text-slate-300"></i>
                            <div class="absolute inset-0 bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition gap-2">
                                <a href="certificate_designer.php?id=${template.id}" class="p-2 bg-white rounded-full hover:bg-blue-50 text-blue-600 transition" title="تعديل">
                                    <i data-lucide="edit-2" class="w-5 h-5"></i>
                                </a>
                                <button onclick="deleteTemplate(${template.id})" class="p-2 bg-white rounded-full hover:bg-red-50 text-red-600 transition" title="حذف">
                                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                                </button>
                            </div>
                        </div>
                        <div class="p-4">
                            <h3 class="font-bold text-slate-800 mb-1">${template.name}</h3>
                            <p class="text-xs text-slate-500">آخر تحديث: ${new Date(template.updated_at).toLocaleDateString('ar-EG')}</p>
                        </div>
                    `;
                    grid.appendChild(card);
                });
                lucide.createIcons();
            } else {
                grid.innerHTML = `
                    <div class="col-span-full text-center py-12">
                        <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="file-x" class="w-8 h-8 text-slate-400"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-800 mb-2">لا توجد قوالب</h3>
                        <p class="text-slate-500 mb-6">لم يتم إنشاء أي قوالب شهادات بعد</p>
                        <a href="certificate_designer.php" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            إنشاء أول قالب
                        </a>
                    </div>
                `;
                lucide.createIcons();
            }
        } catch (error) {
            console.error(error);
            document.getElementById('templatesGrid').innerHTML = `
                <div class="col-span-full text-center py-12 text-red-500">
                    فشل تحميل القوالب. يرجى المحاولة مرة أخرى.
                </div>
            `;
        }
    }

    async function deleteTemplate(id) {
        if (!confirm('هل أنت متأكد من حذف هذا القالب؟ لا يمكن التراجع عن هذا الإجراء.')) return;

        try {
            const response = await fetch('api/manage_certificate_templates.php?action=delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            const data = await response.json();
            
            if (data.success) {
                loadTemplates();
            } else {
                alert('فشل الحذف: ' + data.message);
            }
        } catch (error) {
            console.error(error);
            alert('حدث خطأ أثناء الحذف');
        }
    }

    // Load on start
    loadTemplates();
</script>
</body>
</html>
