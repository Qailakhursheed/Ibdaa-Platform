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
    <title>مصمم الشهادات الذكي - منصة إبداع</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; background-color: #f8fafc; }
        .canvas-container {
            position: relative;
            width: 800px; /* A4 Landscape ratio approx */
            height: 566px;
            background-color: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin: 0 auto;
            overflow: hidden;
            background-size: cover;
            background-position: center;
        }
        .draggable-element {
            position: absolute;
            cursor: move;
            border: 1px dashed transparent;
            padding: 4px;
            white-space: nowrap;
        }
        .draggable-element:hover, .draggable-element.selected {
            border-color: #3b82f6;
            background-color: rgba(59, 130, 246, 0.1);
        }
    </style>
</head>
<body class="text-slate-800">

<div class="flex h-screen">
    <!-- Sidebar -->
    <aside class="w-64 bg-white border-l border-slate-200 hidden md:flex flex-col">
        <div class="p-6 border-b border-slate-200">
            <h1 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="award" class="text-blue-600"></i>
                مصمم الشهادات
            </h1>
        </div>
        
        <div class="p-4 flex-1 overflow-y-auto">
            <!-- Tools -->
            <div class="mb-6">
                <h3 class="text-sm font-semibold text-slate-500 mb-3 uppercase">أدوات التصميم</h3>
                <div class="space-y-2">
                    <button onclick="addElement('student_name')" class="w-full flex items-center gap-2 px-3 py-2 bg-slate-50 hover:bg-slate-100 rounded-lg text-sm transition">
                        <i data-lucide="user" class="w-4 h-4"></i> اسم الطالب
                    </button>
                    <button onclick="addElement('course_name')" class="w-full flex items-center gap-2 px-3 py-2 bg-slate-50 hover:bg-slate-100 rounded-lg text-sm transition">
                        <i data-lucide="book" class="w-4 h-4"></i> اسم الدورة
                    </button>
                    <button onclick="addElement('completion_date')" class="w-full flex items-center gap-2 px-3 py-2 bg-slate-50 hover:bg-slate-100 rounded-lg text-sm transition">
                        <i data-lucide="calendar" class="w-4 h-4"></i> تاريخ الإتمام
                    </button>
                    <button onclick="addElement('certificate_id')" class="w-full flex items-center gap-2 px-3 py-2 bg-slate-50 hover:bg-slate-100 rounded-lg text-sm transition">
                        <i data-lucide="hash" class="w-4 h-4"></i> رقم الشهادة
                    </button>
                    <button onclick="addElement('custom_text')" class="w-full flex items-center gap-2 px-3 py-2 bg-slate-50 hover:bg-slate-100 rounded-lg text-sm transition">
                        <i data-lucide="type" class="w-4 h-4"></i> نص مخصص
                    </button>
                </div>
            </div>

            <!-- AI Generator -->
            <div class="mb-6">
                <h3 class="text-sm font-semibold text-slate-500 mb-3 uppercase flex items-center gap-2">
                    <i data-lucide="sparkles" class="w-4 h-4 text-purple-500"></i>
                    توليد الخلفية (AI)
                </h3>
                <div class="space-y-3">
                    <textarea id="aiPrompt" rows="3" class="w-full p-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent" placeholder="وصف الخلفية (مثلاً: إطار ذهبي فاخر مع خلفية زرقاء داكنة)"></textarea>
                    <button onclick="generateBackground()" id="generateBtn" class="w-full py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition flex justify-center items-center gap-2">
                        <i data-lucide="wand-2" class="w-4 h-4"></i> توليد الخلفية
                    </button>
                </div>
            </div>

            <!-- Properties -->
            <div id="propertiesPanel" class="hidden">
                <h3 class="text-sm font-semibold text-slate-500 mb-3 uppercase">الخصائص</h3>
                <div class="space-y-3">
                    <div>
                        <label class="text-xs text-slate-500 block mb-1">حجم الخط</label>
                        <input type="number" id="fontSize" class="w-full p-2 border rounded text-sm" onchange="updateElementStyle('fontSize', this.value + 'px')">
                    </div>
                    <div>
                        <label class="text-xs text-slate-500 block mb-1">اللون</label>
                        <input type="color" id="color" class="w-full h-8 p-0 border rounded cursor-pointer" onchange="updateElementStyle('color', this.value)">
                    </div>
                    <div>
                        <label class="text-xs text-slate-500 block mb-1">الخط</label>
                        <select id="fontFamily" class="w-full p-2 border rounded text-sm" onchange="updateElementStyle('fontFamily', this.value)">
                            <option value="Cairo">Cairo</option>
                            <option value="Arial">Arial</option>
                            <option value="Times New Roman">Times New Roman</option>
                        </select>
                    </div>
                    <button onclick="deleteSelectedElement()" class="w-full py-2 bg-red-50 text-red-600 hover:bg-red-100 rounded-lg text-sm mt-2">
                        حذف العنصر
                    </button>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-screen overflow-hidden">
        <!-- Header -->
        <header class="bg-white border-b border-slate-200 p-4 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <input type="text" id="templateName" placeholder="اسم القالب (مثلاً: شهادة إتمام دورة 2025)" class="text-lg font-bold text-slate-800 border-none focus:ring-0 bg-transparent w-96">
            </div>
            <div class="flex items-center gap-3">
                <button onclick="saveTemplate()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium flex items-center gap-2">
                    <i data-lucide="save" class="w-4 h-4"></i> حفظ القالب
                </button>
                <a href="dashboard_router.php" class="px-4 py-2 text-slate-600 hover:bg-slate-100 rounded-lg font-medium">
                    إغلاق
                </a>
            </div>
        </header>

        <!-- Canvas Area -->
        <div class="flex-1 bg-slate-100 p-8 overflow-auto flex items-center justify-center">
            <div id="certificateCanvas" class="canvas-container">
                <!-- Elements will be added here -->
                <div class="absolute inset-0 flex items-center justify-center text-slate-400 pointer-events-none" id="emptyState">
                    <div class="text-center">
                        <i data-lucide="image" class="w-12 h-12 mx-auto mb-2 opacity-50"></i>
                        <p>ابدأ بتوليد خلفية أو إضافة عناصر</p>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    lucide.createIcons();

    let selectedElement = null;
    let currentBackground = '';

    // Element Templates
    const elementTemplates = {
        'student_name': { text: '[اسم الطالب]', style: { fontSize: '24px', fontWeight: 'bold', color: '#000000', top: '250px', left: '300px' } },
        'course_name': { text: '[اسم الدورة]', style: { fontSize: '18px', color: '#333333', top: '300px', left: '300px' } },
        'completion_date': { text: '[التاريخ]', style: { fontSize: '14px', color: '#666666', top: '400px', left: '100px' } },
        'certificate_id': { text: 'ID: [CODE]', style: { fontSize: '12px', color: '#999999', top: '500px', left: '50px' } },
        'custom_text': { text: 'نص جديد', style: { fontSize: '16px', color: '#000000', top: '100px', left: '100px' } }
    };

    function addElement(type) {
        const template = elementTemplates[type];
        const el = document.createElement('div');
        el.className = 'draggable-element';
        el.innerText = template.text;
        el.dataset.type = type;
        
        // Apply styles
        Object.assign(el.style, template.style);
        
        // Make draggable
        makeDraggable(el);
        
        // Selection handler
        el.addEventListener('mousedown', (e) => {
            e.stopPropagation();
            selectElement(el);
        });

        document.getElementById('certificateCanvas').appendChild(el);
        document.getElementById('emptyState').style.display = 'none';
        selectElement(el);
    }

    function makeDraggable(el) {
        let isDragging = false;
        let startX, startY, initialLeft, initialTop;

        el.addEventListener('mousedown', (e) => {
            isDragging = true;
            startX = e.clientX;
            startY = e.clientY;
            initialLeft = parseInt(el.style.left || 0);
            initialTop = parseInt(el.style.top || 0);
            el.style.cursor = 'grabbing';
        });

        window.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            const dx = e.clientX - startX;
            const dy = e.clientY - startY;
            el.style.left = `${initialLeft + dx}px`;
            el.style.top = `${initialTop + dy}px`;
        });

        window.addEventListener('mouseup', () => {
            isDragging = false;
            el.style.cursor = 'move';
        });
    }

    function selectElement(el) {
        if (selectedElement) selectedElement.classList.remove('selected');
        selectedElement = el;
        el.classList.add('selected');
        
        // Show properties
        document.getElementById('propertiesPanel').classList.remove('hidden');
        
        // Update inputs
        document.getElementById('fontSize').value = parseInt(el.style.fontSize);
        // Convert rgb to hex for color input if needed (simplified here)
    }

    function updateElementStyle(prop, value) {
        if (selectedElement) {
            selectedElement.style[prop] = value;
        }
    }

    function deleteSelectedElement() {
        if (selectedElement) {
            selectedElement.remove();
            selectedElement = null;
            document.getElementById('propertiesPanel').classList.add('hidden');
        }
    }

    // AI Generation
    async function generateBackground() {
        const prompt = document.getElementById('aiPrompt').value;
        if (!prompt) return alert('الرجاء إدخال وصف للخلفية');

        const btn = document.getElementById('generateBtn');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> جاري التوليد...';
        btn.disabled = true;
        lucide.createIcons();

        try {
            const response = await fetch('api/ai_image_generator.php?action=generate', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    prompt: prompt,
                    type: 'certificate',
                    size: '1024x1024' // Or landscape ratio if supported
                })
            });

            const data = await response.json();
            if (data.success) {
                const canvas = document.getElementById('certificateCanvas');
                currentBackground = data.url;
                canvas.style.backgroundImage = `url('../${data.url}')`;
                document.getElementById('emptyState').style.display = 'none';
            } else {
                alert('فشل التوليد: ' + data.message);
            }
        } catch (error) {
            console.error(error);
            alert('حدث خطأ أثناء الاتصال بالخادم');
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
            lucide.createIcons();
        }
    }

    // Load Template if ID exists
    const urlParams = new URLSearchParams(window.location.search);
    const templateId = urlParams.get('id');

    if (templateId) {
        loadTemplate(templateId);
    }

    async function loadTemplate(id) {
        try {
            const response = await fetch(`api/manage_certificate_templates.php?action=get&id=${id}`);
            const data = await response.json();
            
            if (data.success) {
                const template = data.template;
                document.getElementById('templateName').value = template.name;
                
                const content = JSON.parse(template.template_json);
                
                // Set background
                if (content.backgroundImage) {
                    currentBackground = content.backgroundImage;
                    document.getElementById('certificateCanvas').style.backgroundImage = `url('../${content.backgroundImage}')`;
                    document.getElementById('emptyState').style.display = 'none';
                }

                // Add elements
                if (content.elements) {
                    content.elements.forEach(item => {
                        const el = document.createElement('div');
                        el.className = 'draggable-element';
                        el.innerText = item.html;
                        el.setAttribute('style', item.style);
                        
                        // Re-attach events
                        makeDraggable(el);
                        el.addEventListener('mousedown', (e) => {
                            e.stopPropagation();
                            selectElement(el);
                        });

                        document.getElementById('certificateCanvas').appendChild(el);
                    });
                    if (content.elements.length > 0) {
                        document.getElementById('emptyState').style.display = 'none';
                    }
                }
            } else {
                alert('فشل تحميل القالب: ' + data.message);
            }
        } catch (error) {
            console.error(error);
            alert('حدث خطأ أثناء تحميل القالب');
        }
    }

    // Save Template
    async function saveTemplate() {
        const name = document.getElementById('templateName').value;
        if (!name) return alert('الرجاء إدخال اسم القالب');

        const elements = [];
        document.querySelectorAll('.draggable-element').forEach(el => {
            elements.push({
                html: el.innerText,
                style: el.getAttribute('style')
            });
        });

        const templateData = {
            backgroundImage: currentBackground,
            backgroundColor: '#ffffff', // Fallback
            elements: elements
        };

        const payload = {
            name: name,
            template_json: JSON.stringify(templateData)
        };

        if (templateId) {
            payload.id = templateId;
        }

        try {
            const response = await fetch('api/manage_certificate_templates.php?action=save', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const data = await response.json();
            if (data.success) {
                alert('تم حفظ القالب بنجاح!');
                window.location.href = 'certificate_templates.php';
            } else {
                alert('فشل الحفظ: ' + data.message);
            }
        } catch (error) {
            console.error(error);
            alert('حدث خطأ أثناء الحفظ');
        }
    }

    // Deselect on background click
    document.getElementById('certificateCanvas').addEventListener('mousedown', (e) => {
        if (e.target === e.currentTarget) {
            if (selectedElement) selectedElement.classList.remove('selected');
            selectedElement = null;
            document.getElementById('propertiesPanel').classList.add('hidden');
        }
    });

</script>
</body>
</html>
