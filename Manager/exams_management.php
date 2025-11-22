<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['manager', 'technical', 'trainer'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الاختبارات - منصة إبداع</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; }
        .fade-in { animation: fadeIn 0.3s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">
    <div class="min-h-screen flex flex-col">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200 px-6 py-4 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <a href="dashboard_router.php" class="text-gray-500 hover:text-blue-600 transition-colors">
                    <i data-lucide="arrow-right" class="w-6 h-6"></i>
                </a>
                <h1 class="text-2xl font-bold text-gray-800">إدارة الاختبارات الذكية</h1>
            </div>
            <button onclick="showCreateModal()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2">
                <i data-lucide="plus" class="w-5 h-5"></i>
                <span>اختبار جديد</span>
            </button>
        </header>

        <!-- Main Content -->
        <main class="flex-1 container mx-auto px-4 py-8">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-500 text-sm">إجمالي الاختبارات</p>
                            <h3 class="text-3xl font-bold text-gray-800 mt-2" id="total-exams">0</h3>
                        </div>
                        <div class="p-3 bg-blue-50 text-blue-600 rounded-lg">
                            <i data-lucide="file-text" class="w-6 h-6"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-500 text-sm">الاختبارات المنشورة</p>
                            <h3 class="text-3xl font-bold text-green-600 mt-2" id="published-exams">0</h3>
                        </div>
                        <div class="p-3 bg-green-50 text-green-600 rounded-lg">
                            <i data-lucide="check-circle" class="w-6 h-6"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-500 text-sm">محاولات الطلاب</p>
                            <h3 class="text-3xl font-bold text-purple-600 mt-2" id="total-attempts">0</h3>
                        </div>
                        <div class="p-3 bg-purple-50 text-purple-600 rounded-lg">
                            <i data-lucide="users" class="w-6 h-6"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Exams List -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-xl font-bold">قائمة الاختبارات</h2>
                    <div class="flex gap-2">
                        <input type="text" placeholder="بحث..." class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:border-blue-500">
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-right">
                        <thead class="bg-gray-50 text-gray-600 text-sm">
                            <tr>
                                <th class="px-6 py-4 font-semibold">العنوان</th>
                                <th class="px-6 py-4 font-semibold">الدورة</th>
                                <th class="px-6 py-4 font-semibold">الحالة</th>
                                <th class="px-6 py-4 font-semibold">المدة</th>
                                <th class="px-6 py-4 font-semibold">المحاولات</th>
                                <th class="px-6 py-4 font-semibold">تاريخ الإنشاء</th>
                                <th class="px-6 py-4 font-semibold">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody id="exams-table-body" class="divide-y divide-gray-100">
                            <!-- Rows will be populated by JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Create/Edit Modal -->
    <div id="exam-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto m-4 shadow-2xl fade-in">
            <div class="p-6 border-b border-gray-200 flex justify-between items-center sticky top-0 bg-white z-10">
                <h2 class="text-2xl font-bold text-gray-800">إنشاء اختبار جديد</h2>
                <button onclick="closeModal()" class="text-gray-500 hover:text-red-500">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            
            <div class="p-6 space-y-6">
                <!-- Basic Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">عنوان الاختبار</label>
                        <input type="text" id="exam-title" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">الدورة التدريبية</label>
                        <select id="exam-course" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <!-- Courses loaded via JS -->
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">المدة (دقيقة)</label>
                        <input type="number" id="exam-duration" value="60" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">درجة النجاح</label>
                        <input type="number" id="exam-passing" value="50" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <!-- AI Generation Section -->
                <div class="bg-gradient-to-r from-purple-50 to-blue-50 p-6 rounded-xl border border-purple-100">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-2 bg-purple-600 text-white rounded-lg">
                            <i data-lucide="sparkles" class="w-5 h-5"></i>
                        </div>
                        <h3 class="text-lg font-bold text-purple-900">توليد الأسئلة بالذكاء الاصطناعي</h3>
                    </div>
                    <div class="flex gap-4 items-end">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700 mb-2">موضوع الأسئلة</label>
                            <input type="text" id="ai-topic" placeholder="مثال: أساسيات البرمجة بلغة PHP" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                        </div>
                        <div class="w-32">
                            <label class="block text-sm font-medium text-gray-700 mb-2">العدد</label>
                            <input type="number" id="ai-count" value="5" min="1" max="20" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                        </div>
                        <button onclick="generateAIQuestions()" class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition-colors h-[42px] flex items-center gap-2">
                            <i data-lucide="zap" class="w-4 h-4"></i>
                            <span>توليد</span>
                        </button>
                    </div>
                </div>

                <!-- Questions List -->
                <div>
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold text-gray-800">الأسئلة</h3>
                        <button onclick="addQuestion()" class="text-blue-600 hover:text-blue-700 text-sm font-semibold flex items-center gap-1">
                            <i data-lucide="plus-circle" class="w-4 h-4"></i>
                            <span>إضافة سؤال يدوياً</span>
                        </button>
                    </div>
                    <div id="questions-list" class="space-y-4">
                        <!-- Questions will be added here -->
                    </div>
                </div>
            </div>

            <div class="p-6 border-t border-gray-200 bg-gray-50 flex justify-end gap-3 sticky bottom-0">
                <button onclick="closeModal()" class="px-6 py-2 text-gray-600 hover:bg-gray-200 rounded-lg transition-colors">إلغاء</button>
                <button onclick="saveExam()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors shadow-lg">حفظ الاختبار</button>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
        let questions = [];

        // Load initial data
        document.addEventListener('DOMContentLoaded', () => {
            loadExams();
            loadCourses();
        });

        async function loadExams() {
            try {
                const response = await fetch('api/exams_system.php?action=list');
                const data = await response.json();
                
                if (data.success) {
                    renderExams(data.exams);
                    updateStats(data.exams);
                }
            } catch (error) {
                console.error('Error loading exams:', error);
            }
        }

        async function loadCourses() {
            // Assuming there's an API for courses, otherwise we'd need to add one.
            // For now, I'll mock it or try to fetch from a known endpoint if exists.
            // I'll use a placeholder for now.
            const select = document.getElementById('exam-course');
            select.innerHTML = '<option value="1">دورة تجريبية (1)</option>'; 
            // TODO: Connect to real courses API
        }

        function renderExams(exams) {
            const tbody = document.getElementById('exams-table-body');
            tbody.innerHTML = exams.map(exam => `
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 font-medium text-gray-900">${exam.title}</td>
                    <td class="px-6 py-4 text-gray-600">${exam.course_title || '-'}</td>
                    <td class="px-6 py-4">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold ${getStatusClass(exam.status)}">
                            ${getStatusLabel(exam.status)}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-gray-600">${exam.duration_minutes} دقيقة</td>
                    <td class="px-6 py-4 text-gray-600">${exam.attempts_count}</td>
                    <td class="px-6 py-4 text-gray-500 text-sm">${new Date(exam.created_at).toLocaleDateString('ar-EG')}</td>
                    <td class="px-6 py-4 flex gap-2 justify-end">
                        <button onclick="publishExam(${exam.exam_id})" class="p-2 text-green-600 hover:bg-green-50 rounded-lg" title="نشر">
                            <i data-lucide="upload-cloud" class="w-4 h-4"></i>
                        </button>
                        <button onclick="deleteExam(${exam.exam_id})" class="p-2 text-red-600 hover:bg-red-50 rounded-lg" title="حذف">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
            lucide.createIcons();
        }

        function getStatusClass(status) {
            return {
                'draft': 'bg-gray-100 text-gray-600',
                'published': 'bg-green-100 text-green-600',
                'closed': 'bg-red-100 text-red-600'
            }[status] || 'bg-gray-100';
        }

        function getStatusLabel(status) {
            return {
                'draft': 'مسودة',
                'published': 'منشور',
                'closed': 'مغلق'
            }[status] || status;
        }

        function updateStats(exams) {
            document.getElementById('total-exams').textContent = exams.length;
            document.getElementById('published-exams').textContent = exams.filter(e => e.status === 'published').length;
            document.getElementById('total-attempts').textContent = exams.reduce((acc, curr) => acc + parseInt(curr.attempts_count || 0), 0);
        }

        // Modal Functions
        function showCreateModal() {
            document.getElementById('exam-modal').classList.remove('hidden');
            document.getElementById('exam-modal').classList.add('flex');
            questions = [];
            renderQuestions();
        }

        function closeModal() {
            document.getElementById('exam-modal').classList.add('hidden');
            document.getElementById('exam-modal').classList.remove('flex');
        }

        // Question Management
        function addQuestion(data = null) {
            const q = data || {
                id: Date.now(),
                text: '',
                type: 'mcq',
                options: ['', '', '', ''],
                correct: '',
                marks: 1
            };
            questions.push(q);
            renderQuestions();
        }

        function removeQuestion(index) {
            questions.splice(index, 1);
            renderQuestions();
        }

        function renderQuestions() {
            const container = document.getElementById('questions-list');
            container.innerHTML = questions.map((q, index) => `
                <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm relative group">
                    <button onclick="removeQuestion(${index})" class="absolute top-4 left-4 text-gray-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity">
                        <i data-lucide="trash" class="w-5 h-5"></i>
                    </button>
                    
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 mb-4">
                        <div class="md:col-span-8">
                            <input type="text" value="${q.text}" onchange="updateQuestion(${index}, 'text', this.value)" placeholder="نص السؤال" class="w-full border-b border-gray-200 focus:border-blue-500 focus:outline-none py-2 font-semibold text-gray-800">
                        </div>
                        <div class="md:col-span-2">
                            <select onchange="updateQuestion(${index}, 'type', this.value)" class="w-full bg-gray-50 border-none rounded-lg text-sm">
                                <option value="mcq" ${q.type === 'mcq' ? 'selected' : ''}>اختيارات</option>
                                <option value="true_false" ${q.type === 'true_false' ? 'selected' : ''}>صح/خطأ</option>
                                <option value="short_answer" ${q.type === 'short_answer' ? 'selected' : ''}>إجابة قصيرة</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <input type="number" value="${q.marks}" onchange="updateQuestion(${index}, 'marks', this.value)" class="w-full bg-gray-50 border-none rounded-lg text-sm text-center" placeholder="الدرجة">
                        </div>
                    </div>

                    ${renderOptions(q, index)}
                </div>
            `).join('');
            lucide.createIcons();
        }

        function renderOptions(q, index) {
            if (q.type === 'mcq') {
                return `
                    <div class="grid grid-cols-2 gap-3">
                        ${q.options.map((opt, i) => `
                            <div class="flex items-center gap-2">
                                <input type="radio" name="correct_${index}" ${q.correct === opt && opt !== '' ? 'checked' : ''} onchange="updateQuestion(${index}, 'correct', '${opt}')">
                                <input type="text" value="${opt}" onchange="updateOption(${index}, ${i}, this.value)" placeholder="خيار ${i+1}" class="flex-1 border border-gray-200 rounded px-3 py-1 text-sm">
                            </div>
                        `).join('')}
                    </div>
                `;
            } else if (q.type === 'true_false') {
                return `
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="correct_${index}" value="صح" ${q.correct === 'صح' ? 'checked' : ''} onchange="updateQuestion(${index}, 'correct', 'صح')">
                            <span>صح</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="correct_${index}" value="خطأ" ${q.correct === 'خطأ' ? 'checked' : ''} onchange="updateQuestion(${index}, 'correct', 'خطأ')">
                            <span>خطأ</span>
                        </label>
                    </div>
                `;
            }
            return '';
        }

        function updateQuestion(index, field, value) {
            questions[index][field] = value;
        }

        function updateOption(qIndex, optIndex, value) {
            questions[qIndex].options[optIndex] = value;
            // If this option was the correct answer, update correct answer too
            // This is a bit tricky, for simplicity we might require re-selecting correct answer
        }

        // AI Generation
        async function generateAIQuestions() {
            const topic = document.getElementById('ai-topic').value;
            const count = document.getElementById('ai-count').value;
            
            if (!topic) {
                alert('الرجاء إدخال موضوع');
                return;
            }

            const btn = document.querySelector('button[onclick="generateAIQuestions()"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> جاري التوليد...';
            btn.disabled = true;

            try {
                const response = await fetch('api/exams_system.php?action=generate_ai_questions', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ topic, count })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    data.questions.forEach(q => {
                        addQuestion({
                            id: Date.now() + Math.random(),
                            text: q.text,
                            type: q.type,
                            options: q.options || [],
                            correct: q.correct_answer,
                            marks: q.marks
                        });
                    });
                } else {
                    alert(data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('حدث خطأ في التوليد');
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
                lucide.createIcons();
            }
        }

        // Save Exam
        async function saveExam() {
            const title = document.getElementById('exam-title').value;
            const courseId = document.getElementById('exam-course').value;
            
            if (!title || questions.length === 0) {
                alert('الرجاء إدخال العنوان وإضافة سؤال واحد على الأقل');
                return;
            }

            const payload = {
                title,
                course_id: courseId,
                duration_minutes: document.getElementById('exam-duration').value,
                passing_marks: document.getElementById('exam-passing').value,
                questions: questions.map(q => ({
                    text: q.text,
                    type: q.type,
                    options: q.options,
                    correct_answer: q.correct,
                    marks: q.marks
                }))
            };

            try {
                const response = await fetch('api/exams_system.php?action=create', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('تم حفظ الاختبار بنجاح');
                    closeModal();
                    loadExams();
                } else {
                    alert(data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('حدث خطأ في الحفظ');
            }
        }

        async function deleteExam(id) {
            if (!confirm('هل أنت متأكد من الحذف؟')) return;
            
            const formData = new FormData();
            formData.append('exam_id', id);
            
            try {
                const response = await fetch('api/exams_system.php?action=delete', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                if (data.success) loadExams();
                else alert(data.message);
            } catch (error) {
                console.error(error);
            }
        }

        async function publishExam(id) {
            if (!confirm('هل أنت متأكد من النشر؟ سيتم إشعار الطلاب.')) return;
            
            const formData = new FormData();
            formData.append('exam_id', id);
            
            try {
                const response = await fetch('api/exams_system.php?action=publish', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                if (data.success) {
                    alert(data.message);
                    loadExams();
                } else {
                    alert(data.message);
                }
            } catch (error) {
                console.error(error);
            }
        }
    </script>
</body>
</html>
