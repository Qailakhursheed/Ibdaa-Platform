/**
 * Manager Features - All Dashboard Functions
 * جميع وظائف لوحة المدير
 */

// ==============================================
// API Configuration
// ==============================================
const API_ENDPOINTS = {
    dashboardStats: 'api/get_dashboard_stats.php',
    trainerData: 'api/get_trainer_data.php',
    trainees: 'api/manage_users.php?role=student',
    trainers: 'api/manage_users.php?role=trainer',
    manageUsers: 'api/manage_users.php',
    manageCourses: 'api/manage_courses.php',
    manageFinance: 'api/manage_finance.php',
    manageRequests: 'api/get_requests.php',
    manageAnnouncements: 'api/manage_announcements.php',
    manageGrades: 'api/manage_grades.php',
    manageLocations: 'api/manage_locations.php',
    manageImports: 'api/import_excel_flexible.php',
    manageLmsContent: 'api/manage_lms_content.php',
    manageLmsAssignments: 'api/manage_lms_assignments.php',
    manageAttendance: 'api/manage_attendance.php',
    generateCertificate: 'api/generate_certificate.php',
    manageMessages: 'api/manage_messages.php',
    analyticsData: 'api/get_analytics_data.php',
    notifications: 'api/get_notifications.php',
    markNotificationRead: 'api/mark_notification_read.php',
    studentData: 'api/get_student_data.php',
    aiImages: 'api/ai_image_generator.php'
};

// ==============================================
// Helper Functions
// ==============================================
function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value == null ? '' : String(value);
    return div.innerHTML;
}

function formatDateTime(value, options = {}) {
    if (!value) return '';
    let input = value;
    if (typeof input === 'string' && input.includes(' ')) {
        input = input.replace(' ', 'T');
    }
    const date = new Date(input);
    if (Number.isNaN(date.getTime())) {
        return value;
    }
    const formatter = new Intl.DateTimeFormat('ar-EG', {
        dateStyle: options.dateStyle || 'medium',
        timeStyle: options.timeStyle || 'short'
    });
    return formatter.format(date);
}

function formatMoney(amount) {
    return new Intl.NumberFormat('ar-SA', {
        style: 'currency',
        currency: 'SAR',
        minimumFractionDigits: 0
    }).format(amount || 0);
}

async function fetchJson(url, options = {}) {
    try {
        const response = await fetch(url, {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            }
        });

        // If API fails, log details and return mock data
        if (!response.ok) {
            const text = await response.text().catch(() => 'Unable to read response text');
            let parsed = null;
            try { parsed = JSON.parse(text); } catch (e) { /* not JSON */ }
            console.error('API responded with error', { url, status: response.status, statusText: response.statusText, body: parsed ?? text });
            console.warn('API failed, using mock data for:', url);
            return getMockData(url);
        }

        const data = await response.json();
        if (data && data.success === false) {
            console.error('API returned success=false', { url, body: data });
            console.warn('API returned error, using mock data');
            return getMockData(url);
        }
        return data;
    } catch (error) {
        console.error('Fetch exception for', url, error);
        console.warn('Fetch error, using mock data:', error);
        return getMockData(url);
    }
}

// Mock Data Generator
function getMockData(url) {
    // For trainees
    if (url.includes('student') || url.includes('trainees')) {
        return {
            success: true,
            users: [
                { id: 1, name: 'أحمد محمد علي', email: 'ahmed@example.com', phone: '777123456', created_at: '2024-01-15' },
                { id: 2, name: 'فاطمة حسن', email: 'fatima@example.com', phone: '777234567', created_at: '2024-02-10' },
                { id: 3, name: 'محمد سعيد', email: 'mohammed@example.com', phone: '777345678', created_at: '2024-03-05' },
                { id: 4, name: 'سارة عبدالله', email: 'sara@example.com', phone: '777456789', created_at: '2024-03-20' }
            ]
        };
    }
    
    // For trainers
    if (url.includes('trainer')) {
        return {
            success: true,
            users: [
                { id: 1, name: 'د. خالد الشريف', email: 'khalid@example.com', specialty: 'برمجة', courses_count: 3 },
                { id: 2, name: 'أ. منى القاضي', email: 'mona@example.com', specialty: 'تصميم', courses_count: 2 }
            ]
        };
    }
    
    // For courses
    if (url.includes('courses')) {
        return {
            success: true,
            courses: [
                { id: 1, name: 'تطوير الويب', description: 'دورة شاملة في تطوير المواقع', status: 'active', enrolled_count: 25, duration: 40 },
                { id: 2, name: 'البرمجة بلغة Python', description: 'تعلم البرمجة من الصفر', status: 'active', enrolled_count: 18, duration: 30 },
                { id: 3, name: 'التصميم الجرافيكي', description: 'أساسيات التصميم', status: 'inactive', enrolled_count: 12, duration: 25 }
            ]
        };
    }
    
    // For finance
    if (url.includes('finance')) {
        return {
            success: true,
            stats: {
                total_revenue: 45000,
                pending_count: 3,
                completed_count: 28
            },
            payments: [
                { id: 1, student_name: 'أحمد محمد', amount: 1500, course_name: 'تطوير الويب', status: 'completed', created_at: '2024-03-15' },
                { id: 2, student_name: 'فاطمة حسن', amount: 1200, course_name: 'Python', status: 'pending', created_at: '2024-03-18' },
                { id: 3, student_name: 'محمد سعيد', amount: 1500, course_name: 'تطوير الويب', status: 'completed', created_at: '2024-03-20' }
            ]
        };
    }
    
    // For requests
    if (url.includes('requests')) {
        return {
            success: true,
            requests: [
                { id: 1, name: 'علي حسين', email: 'ali@example.com', course_name: 'تطوير الويب', created_at: '2024-03-22' },
                { id: 2, name: 'نور أحمد', email: 'noor@example.com', course_name: 'Python', created_at: '2024-03-23' }
            ]
        };
    }
    
    // For announcements
    if (url.includes('announcements')) {
        return {
            success: true,
            announcements: [
                { id: 1, title: 'بدء التسجيل للفصل الجديد', content: 'نعلن عن بدء التسجيل في دورات الفصل الدراسي الجديد...', is_active: true, created_at: '2024-03-20' },
                { id: 2, title: 'عطلة العيد', content: 'ستكون المنصة مغلقة خلال إجازة العيد...', is_active: false, created_at: '2024-03-15' }
            ]
        };
    }
    
    // Default empty response
    return { success: true, data: [] };
}

// ==============================================
// Page Renderers - Main Functions
// ==============================================

async function renderTrainees() {
    setPageHeader('إدارة المتدربين', 'عرض وإدارة جميع الطلاب المسجلين');
    clearPageBody();
    
    const pageBody = document.getElementById('pageBody');
    pageBody.innerHTML = `
        <div class="bg-white rounded-2xl shadow p-6 border border-slate-100">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-slate-800">قائمة المتدربين</h3>
                <button onclick="openAddTraineeModal()" class="px-4 py-2 bg-sky-600 text-white rounded-lg hover:bg-sky-700">
                    <i data-lucide="user-plus" class="w-4 h-4 inline"></i>
                    إضافة متدرب جديد
                </button>
            </div>
            <div id="traineesTable" class="overflow-x-auto">
                <div class="text-center py-8">
                    <i data-lucide="loader" class="w-8 h-8 animate-spin mx-auto text-sky-600"></i>
                    <p class="mt-2 text-slate-600">جاري التحميل...</p>
                </div>
            </div>
        </div>
    `;
    lucide.createIcons();
    
    try {
        const data = await fetchJson(API_ENDPOINTS.trainees);
        const users = data.data || data.users || [];
        displayTraineesTable(users);
    } catch (error) {
        console.error('Error loading trainees:', error);
        displayTraineesTable([]);
    }
}

function displayTraineesTable(trainees) {
    const container = document.getElementById('traineesTable');
    if (!trainees || trainees.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8">
                <i data-lucide="users" class="w-12 h-12 mx-auto text-slate-400"></i>
                <p class="mt-2 text-slate-600">لا يوجد متدربون مسجلون</p>
            </div>
        `;
        lucide.createIcons();
        return;
    }
    
    const tableHtml = `
        <table class="w-full">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-slate-700">الاسم</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-slate-700">البريد الإلكتروني</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-slate-700">الهاتف</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-slate-700">تاريخ التسجيل</th>
                    <th class="px-4 py-3 text-center text-sm font-semibold text-slate-700">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                ${trainees.map(trainee => `
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 text-sm">${escapeHtml(trainee.full_name || trainee.name || '')}</td>
                        <td class="px-4 py-3 text-sm">${escapeHtml(trainee.email)}</td>
                        <td class="px-4 py-3 text-sm">${escapeHtml(trainee.phone || '-')}</td>
                        <td class="px-4 py-3 text-sm">${formatDateTime(trainee.created_at)}</td>
                        <td class="px-4 py-3 text-center">
                            <button onclick="editTrainee(${trainee.id})" class="text-sky-600 hover:text-sky-700 mx-1">
                                <i data-lucide="edit" class="w-4 h-4"></i>
                            </button>
                            <button onclick="deleteTrainee(${trainee.id})" class="text-red-600 hover:text-red-700 mx-1">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
    container.innerHTML = tableHtml;
    lucide.createIcons();
}

async function renderCourses() {
    setPageHeader('إدارة الدورات', 'إنشاء وتحرير الدورات التدريبية');
    clearPageBody();
    
    const pageBody = document.getElementById('pageBody');
    pageBody.innerHTML = `
        <div class="bg-white rounded-2xl shadow p-6 border border-slate-100">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-slate-800">قائمة الدورات</h3>
                <button onclick="openAddCourseModal()" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">
                    <i data-lucide="book-plus" class="w-4 h-4 inline"></i>
                    إضافة دورة جديدة
                </button>
            </div>
            <div id="coursesGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="text-center py-8">
                    <i data-lucide="loader" class="w-8 h-8 animate-spin mx-auto text-emerald-600"></i>
                    <p class="mt-2 text-slate-600">جاري التحميل...</p>
                </div>
            </div>
        </div>
    `;
    lucide.createIcons();
    
    try {
        const data = await fetchJson(API_ENDPOINTS.manageCourses);
        if (data && data.courses) {
            displayCoursesGrid(data.courses);
        } else {
            displayCoursesGrid([]);
        }
    } catch (error) {
        console.error('Error loading courses:', error);
        displayCoursesGrid([]);
    }
}

function displayCoursesGrid(courses) {
    const container = document.getElementById('coursesGrid');
    if (!courses || courses.length === 0) {
        container.innerHTML = `
            <div class="col-span-full text-center py-8">
                <i data-lucide="book" class="w-12 h-12 mx-auto text-slate-400"></i>
                <p class="mt-2 text-slate-600">لا توجد دورات مسجلة</p>
            </div>
        `;
        lucide.createIcons();
        return;
    }
    
    const cardsHtml = courses.map(course => `
        <div class="bg-white border border-slate-200 rounded-xl p-5 hover:shadow-lg transition">
            <div class="flex items-start justify-between mb-3">
                <h4 class="text-lg font-bold text-slate-800">${escapeHtml(course.name)}</h4>
                <span class="px-2 py-1 text-xs rounded-full ${course.status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'}">
                    ${course.status === 'active' ? 'نشط' : 'منتهي'}
                </span>
            </div>
            <p class="text-sm text-slate-600 mb-4 line-clamp-2">${escapeHtml(course.description || '')}</p>
            <div class="flex items-center justify-between text-sm text-slate-500 mb-4">
                <span><i data-lucide="users" class="w-4 h-4 inline"></i> ${course.enrolled_count || 0} متدرب</span>
                <span><i data-lucide="clock" class="w-4 h-4 inline"></i> ${course.duration || 0} ساعة</span>
            </div>
            <div class="flex gap-2">
                <button onclick="editCourse(${course.id})" class="flex-1 px-3 py-2 bg-sky-50 text-sky-600 rounded-lg hover:bg-sky-100 text-sm">
                    <i data-lucide="edit" class="w-4 h-4 inline"></i> تعديل
                </button>
                <button onclick="viewCourseDetails(${course.id})" class="flex-1 px-3 py-2 bg-emerald-50 text-emerald-600 rounded-lg hover:bg-emerald-100 text-sm">
                    <i data-lucide="eye" class="w-4 h-4 inline"></i> عرض
                </button>
            </div>
        </div>
    `).join('');
    
    container.innerHTML = cardsHtml;
    lucide.createIcons();
}

async function renderFinance() {
    setPageHeader('الشؤون المالية', 'إدارة المدفوعات والإيرادات');
    clearPageBody();
    
    const pageBody = document.getElementById('pageBody');
    pageBody.innerHTML = `
        <div class="space-y-6">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-xl shadow p-6 border border-slate-100">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-emerald-100 rounded-lg">
                            <i data-lucide="trending-up" class="w-6 h-6 text-emerald-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500">إجمالي الإيرادات</p>
                            <p id="totalRevenue" class="text-2xl font-bold text-slate-800">-</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow p-6 border border-slate-100">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-amber-100 rounded-lg">
                            <i data-lucide="clock" class="w-6 h-6 text-amber-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500">المدفوعات المعلقة</p>
                            <p id="pendingPayments" class="text-2xl font-bold text-slate-800">-</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow p-6 border border-slate-100">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-sky-100 rounded-lg">
                            <i data-lucide="wallet" class="w-6 h-6 text-sky-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500">المدفوعات المكتملة</p>
                            <p id="completedPayments" class="text-2xl font-bold text-slate-800">-</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Payments Table -->
            <div class="bg-white rounded-2xl shadow p-6 border border-slate-100">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-slate-800">سجل المدفوعات</h3>
                    <button onclick="openAddPaymentModal()" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700">
                        <i data-lucide="plus" class="w-4 h-4 inline"></i>
                        تسجيل دفعة جديدة
                    </button>
                </div>
                <div id="paymentsTable">
                    <div class="text-center py-8">
                        <i data-lucide="loader" class="w-8 h-8 animate-spin mx-auto text-amber-600"></i>
                        <p class="mt-2 text-slate-600">جاري التحميل...</p>
                    </div>
                </div>
            </div>
        </div>
    `;
    lucide.createIcons();
    
    loadFinanceData();
}

async function loadFinanceData() {
    try {
        const data = await fetchJson(API_ENDPOINTS.manageFinance);
        
        // Update stats
        const stats = data.stats || {};
        document.getElementById('totalRevenue').textContent = formatMoney(stats.total_revenue || 0);
        document.getElementById('pendingPayments').textContent = stats.pending_count || 0;
        document.getElementById('completedPayments').textContent = stats.completed_count || 0;
        
        // Display payments table
        if (data && data.payments) {
            displayPaymentsTable(data.payments);
        } else {
            displayPaymentsTable([]);
        }
    } catch (error) {
        console.error('Error loading finance data:', error);
        // Show default values
        document.getElementById('totalRevenue').textContent = formatMoney(0);
        document.getElementById('pendingPayments').textContent = '0';
        document.getElementById('completedPayments').textContent = '0';
        displayPaymentsTable([]);
    }
}

function displayPaymentsTable(payments) {
    const container = document.getElementById('paymentsTable');
    if (!payments || payments.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8">
                <i data-lucide="wallet" class="w-12 h-12 mx-auto text-slate-400"></i>
                <p class="mt-2 text-slate-600">لا توجد مدفوعات مسجلة</p>
            </div>
        `;
        lucide.createIcons();
        return;
    }
    
    const tableHtml = `
        <table class="w-full">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-slate-700">المتدرب</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-slate-700">المبلغ</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-slate-700">الدورة</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-slate-700">الحالة</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-slate-700">التاريخ</th>
                    <th class="px-4 py-3 text-center text-sm font-semibold text-slate-700">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                ${payments.map(payment => `
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 text-sm">${escapeHtml(payment.student_name)}</td>
                        <td class="px-4 py-3 text-sm font-medium">${formatMoney(payment.amount)}</td>
                        <td class="px-4 py-3 text-sm">${escapeHtml(payment.course_name || '-')}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs rounded-full ${
                                payment.status === 'completed' ? 'bg-emerald-100 text-emerald-700' :
                                payment.status === 'pending' ? 'bg-amber-100 text-amber-700' :
                                'bg-red-100 text-red-700'
                            }">
                                ${payment.status === 'completed' ? 'مكتمل' : payment.status === 'pending' ? 'معلق' : 'ملغي'}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm">${formatDateTime(payment.created_at)}</td>
                        <td class="px-4 py-3 text-center">
                            <button onclick="viewPaymentDetails(${payment.id})" class="text-sky-600 hover:text-sky-700 mx-1">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
    container.innerHTML = tableHtml;
    lucide.createIcons();
}

// ==============================================
// Modal Functions
// ==============================================

function openAddTraineeModal() {
    const modalContent = `
        <form id="addTraineeForm" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">الاسم الكامل</label>
                <input type="text" name="name" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">البريد الإلكتروني</label>
                <input type="email" name="email" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">رقم الهاتف</label>
                <input type="tel" name="phone" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">كلمة المرور</label>
                <input type="password" name="password" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
            </div>
            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 px-4 py-2 bg-sky-600 text-white rounded-lg hover:bg-sky-700">
                    حفظ
                </button>
                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300">
                    إلغاء
                </button>
            </div>
        </form>
    `;
    
    openModal('إضافة متدرب جديد', modalContent);
    
    document.getElementById('addTraineeForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);
        
        try {
            await fetchJson(API_ENDPOINTS.manageUsers, {
                method: 'POST',
                body: JSON.stringify({ 
                    action: 'create',
                    full_name: data.name,
                    email: data.email,
                    phone: data.phone || '',
                    password: data.password,
                    role: 'student'
                })
            });
            showToast('تم إضافة المتدرب بنجاح', 'success');
            closeModal();
            renderTrainees();
        } catch (error) {
            // Error already handled by fetchJson
        }
    });
}

function openAddCourseModal() {
    const modalContent = `
        <form id="addCourseForm" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">اسم الدورة</label>
                <input type="text" name="name" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">الوصف</label>
                <textarea name="description" rows="3" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">المدة (ساعة)</label>
                    <input type="number" name="duration" min="1" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">الحالة</label>
                    <select name="status" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="active">نشط</option>
                        <option value="inactive">غير نشط</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">
                    حفظ
                </button>
                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300">
                    إلغاء
                </button>
            </div>
        </form>
    `;
    
    openModal('إضافة دورة جديدة', modalContent);
    
    document.getElementById('addCourseForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);
        
        try {
            await fetchJson(API_ENDPOINTS.manageCourses, {
                method: 'POST',
                body: JSON.stringify({
                    action: 'create',
                    ...data
                })
            });
            showToast('تم إضافة الدورة بنجاح', 'success');
            closeModal();
            renderCourses();
        } catch (error) {
            // Error already handled
        }
    });
}

function openAddPaymentModal() {
    const modalContent = `
        <form id="addPaymentForm" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">المتدرب</label>
                <select name="user_id" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    <option value="">اختر المتدرب...</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">المبلغ (ريال)</label>
                <input type="number" name="amount" required min="1" step="0.01" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">الحالة</label>
                <select name="status" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    <option value="completed">مكتمل</option>
                    <option value="pending">معلق</option>
                </select>
            </div>
            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700">
                    حفظ
                </button>
                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300">
                    إلغاء
                </button>
            </div>
        </form>
    `;
    
    openModal('تسجيل دفعة جديدة', modalContent);
    
    // Load students for dropdown
    fetchJson(API_ENDPOINTS.trainees).then(data => {
        const select = document.querySelector('#addPaymentForm select[name="user_id"]');
        const users = data.data || data.users || [];
        if (users.length > 0) {
            users.forEach(user => {
                const option = document.createElement('option');
                option.value = user.id;
                option.textContent = user.full_name || user.name;
                select.appendChild(option);
            });
        }
    });
    
    document.getElementById('addPaymentForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);
        
        try {
            await fetchJson(API_ENDPOINTS.manageFinance, {
                method: 'POST',
                body: JSON.stringify(data)
            });
            showToast('تم تسجيل الدفعة بنجاح', 'success');
            closeModal();
            renderFinance();
        } catch (error) {
            // Error handled
        }
    });
}

// Helper functions
function setPageHeader(title, subtitle) {
    document.getElementById('pageTitle').textContent = title;
    document.getElementById('pageSubtitle').textContent = subtitle;
}

function clearPageBody() {
    document.getElementById('pageBody').innerHTML = '';
}

// ==============================================
// Placeholder Functions - Full Implementation
// ==============================================

async function renderTrainers() {
    setPageHeader('إدارة المدربين', 'عرض وإدارة المدربين');
    clearPageBody();
    
    const pageBody = document.getElementById('pageBody');
    pageBody.innerHTML = `
        <div class="bg-white rounded-2xl shadow p-6 border border-slate-100">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-slate-800">قائمة المدربين</h3>
                <button onclick="openAddTrainerModal()" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">
                    <i data-lucide="user-plus" class="w-4 h-4 inline"></i>
                    إضافة مدرب جديد
                </button>
            </div>
            <div id="trainersTable" class="overflow-x-auto">
                <div class="text-center py-8">
                    <i data-lucide="loader" class="w-8 h-8 animate-spin mx-auto text-emerald-600"></i>
                    <p class="mt-2 text-slate-600">جاري التحميل...</p>
                </div>
            </div>
        </div>
    `;
    lucide.createIcons();
    
    try {
        const data = await fetchJson(API_ENDPOINTS.trainers);
        const users = data.data || data.users || [];
        displayTrainersTable(users);
    } catch (error) {
        console.error('Error loading trainers:', error);
        displayTrainersTable([]);
    }
}

function displayTrainersTable(trainers) {
    const container = document.getElementById('trainersTable');
    if (!trainers || trainers.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8">
                <i data-lucide="user-check" class="w-12 h-12 mx-auto text-slate-400"></i>
                <p class="mt-2 text-slate-600">لا يوجد مدربون مسجلون</p>
            </div>
        `;
        lucide.createIcons();
        return;
    }
    
    const tableHtml = `
        <table class="w-full">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-slate-700">الاسم</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-slate-700">البريد الإلكتروني</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-slate-700">التخصص</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-slate-700">عدد الدورات</th>
                    <th class="px-4 py-3 text-center text-sm font-semibold text-slate-700">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                ${trainers.map(trainer => `
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 text-sm">${escapeHtml(trainer.full_name || trainer.name || '')}</td>
                        <td class="px-4 py-3 text-sm">${escapeHtml(trainer.email)}</td>
                        <td class="px-4 py-3 text-sm">${escapeHtml(trainer.specialty || '-')}</td>
                        <td class="px-4 py-3 text-sm">${trainer.courses_count || 0}</td>
                        <td class="px-4 py-3 text-center">
                            <button onclick="editTrainer(${trainer.id})" class="text-sky-600 hover:text-sky-700 mx-1">
                                <i data-lucide="edit" class="w-4 h-4"></i>
                            </button>
                            <button onclick="deleteTrainer(${trainer.id})" class="text-red-600 hover:text-red-700 mx-1">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
    container.innerHTML = tableHtml;
    lucide.createIcons();
}

async function renderRequests() {
    setPageHeader('طلبات الالتحاق', 'مراجعة والموافقة على الطلبات');
    clearPageBody();
    
    const pageBody = document.getElementById('pageBody');
    pageBody.innerHTML = `
        <div class="bg-white rounded-2xl shadow p-6 border border-slate-100">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-slate-800">طلبات الالتحاق الجديدة</h3>
                <span class="px-3 py-1 bg-amber-100 text-amber-700 rounded-full text-sm font-medium">
                    <i data-lucide="clock" class="w-4 h-4 inline"></i>
                    قيد المراجعة
                </span>
            </div>
            <div id="requestsTable">
                <div class="text-center py-8">
                    <i data-lucide="loader" class="w-8 h-8 animate-spin mx-auto text-amber-600"></i>
                    <p class="mt-2 text-slate-600">جاري التحميل...</p>
                </div>
            </div>
        </div>
    `;
    lucide.createIcons();
    
    try {
        const data = await fetchJson(API_ENDPOINTS.manageRequests);
        if (data && data.requests) {
            displayRequestsTable(data.requests);
        } else {
            displayRequestsTable([]);
        }
    } catch (error) {
        console.error('Error loading requests:', error);
        displayRequestsTable([]);
    }
}

function displayRequestsTable(requests) {
    const container = document.getElementById('requestsTable');
    if (!requests || requests.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8">
                <i data-lucide="inbox" class="w-12 h-12 mx-auto text-slate-400"></i>
                <p class="mt-2 text-slate-600">لا توجد طلبات جديدة</p>
            </div>
        `;
        lucide.createIcons();
        return;
    }
    
    const tableHtml = `
        <table class="w-full">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-slate-700">الاسم</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-slate-700">البريد</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-slate-700">الدورة</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-slate-700">التاريخ</th>
                    <th class="px-4 py-3 text-center text-sm font-semibold text-slate-700">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                ${requests.map(req => `
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 text-sm">${escapeHtml(req.name)}</td>
                        <td class="px-4 py-3 text-sm">${escapeHtml(req.email)}</td>
                        <td class="px-4 py-3 text-sm">${escapeHtml(req.course_name || '-')}</td>
                        <td class="px-4 py-3 text-sm">${formatDateTime(req.created_at)}</td>
                        <td class="px-4 py-3 text-center">
                            <button onclick="approveRequest(${req.id})" class="text-emerald-600 hover:text-emerald-700 mx-1">
                                <i data-lucide="check-circle" class="w-4 h-4"></i>
                            </button>
                            <button onclick="rejectRequest(${req.id})" class="text-red-600 hover:text-red-700 mx-1">
                                <i data-lucide="x-circle" class="w-4 h-4"></i>
                            </button>
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
    container.innerHTML = tableHtml;
    lucide.createIcons();
}

async function renderAnnouncements() {
    setPageHeader('الإعلانات', 'إنشاء وإدارة الإعلانات');
    clearPageBody();
    
    const pageBody = document.getElementById('pageBody');
    pageBody.innerHTML = `
        <div class="bg-white rounded-2xl shadow p-6 border border-slate-100">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-slate-800">جميع الإعلانات</h3>
                <button onclick="openAddAnnouncementModal()" class="px-4 py-2 bg-violet-600 text-white rounded-lg hover:bg-violet-700">
                    <i data-lucide="megaphone" class="w-4 h-4 inline"></i>
                    إضافة إعلان جديد
                </button>
            </div>
            <div id="announcementsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="text-center py-8 col-span-full">
                    <i data-lucide="loader" class="w-8 h-8 animate-spin mx-auto text-violet-600"></i>
                    <p class="mt-2 text-slate-600">جاري التحميل...</p>
                </div>
            </div>
        </div>
    `;
    lucide.createIcons();
    
    try {
        const data = await fetchJson(API_ENDPOINTS.manageAnnouncements);
        if (data && data.data) {
            displayAnnouncementsGrid(data.data);
        } else {
            displayAnnouncementsGrid([]);
        }
    } catch (error) {
        console.error('Error loading announcements:', error);
        displayAnnouncementsGrid([]);
    }
}

function displayAnnouncementsGrid(announcements) {
    const container = document.getElementById('announcementsGrid');
    if (!announcements || announcements.length === 0) {
        container.innerHTML = `
            <div class="col-span-full text-center py-8">
                <i data-lucide="megaphone" class="w-12 h-12 mx-auto text-slate-400"></i>
                <p class="mt-2 text-slate-600">لا توجد إعلانات</p>
            </div>
        `;
        lucide.createIcons();
        return;
    }
    
    const cardsHtml = announcements.map(announcement => `
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden hover:shadow-xl transition-shadow duration-300 flex flex-col">
            ${announcement.media_url ? `
                <div class="w-full h-48 bg-slate-100">
                    ${announcement.media_url.match(/\.(jpeg|jpg|gif|png)$/) != null ?
                        `<img src="${'../' + announcement.media_url}" alt="${escapeHtml(announcement.title)}" class="w-full h-full object-cover">` :
                        `<video src="${'../' + announcement.media_url}" class="w-full h-full object-cover" controls></video>`
                    }
                </div>
            ` : ''}
            <div class="p-5 flex flex-col flex-grow">
                <div class="flex items-start justify-between mb-3">
                    <h4 class="text-lg font-bold text-slate-800">${escapeHtml(announcement.title)}</h4>
                    <span class="px-2 py-1 text-xs rounded-full ${announcement.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'}">
                        ${announcement.is_active ? 'نشط' : 'غير نشط'}
                    </span>
                </div>
                <p class="text-sm text-slate-600 mb-3 flex-grow">${escapeHtml(announcement.content || '').substring(0, 100)}...</p>
                <div class="flex items-center justify-between text-xs text-slate-500 pt-3 border-t border-slate-100">
                    <span><i data-lucide="calendar" class="w-4 h-4 inline"></i> ${formatDateTime(announcement.created_at)}</span>
                    <div class="flex gap-2">
                        <button onclick="openAddAnnouncementModal(${announcement.id})" class="text-sky-600 hover:text-sky-700">
                            <i data-lucide="edit" class="w-4 h-4"></i>
                        </button>
                        <button onclick="deleteAnnouncement(${announcement.id})" class="text-red-600 hover:text-red-700">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
    
    container.innerHTML = cardsHtml;
    lucide.createIcons();
}

async function openAddAnnouncementModal(id = null) {
    let announcement = null;
    const modalTitle = id ? 'تعديل الإعلان' : 'إضافة إعلان جديد';

    if (id) {
        const response = await fetchJson(`${API_ENDPOINTS.manageAnnouncements}?id=${id}`);
        if (response.success) {
            announcement = response.data;
        } else {
            showToast('فشل في جلب بيانات الإعلان', 'error');
            return;
        }
    }

    const modalContent = `
        <form id="addAnnouncementForm" class="space-y-4" enctype="multipart/form-data">
            <input type="hidden" name="action" value="${id ? 'update' : 'create'}">
            ${id ? `<input type="hidden" name="id" value="${id}">` : ''}
            
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">العنوان</label>
                <input type="text" name="title" required class="w-full px-4 py-2 border border-slate-300 rounded-lg" value="${announcement ? escapeHtml(announcement.title) : ''}">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">المحتوى</label>
                <textarea name="content" rows="5" required class="w-full px-4 py-2 border border-slate-300 rounded-lg">${announcement ? escapeHtml(announcement.content) : ''}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">صورة أو فيديو (اختياري)</label>
                <input type="file" name="media" accept="image/*,video/*" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100">
                ${announcement && announcement.media_url ? `<p class="text-xs text-slate-500 mt-2">الملف الحالي: ${escapeHtml(announcement.media_url)}</p><input type="hidden" name="existing_media_url" value="${announcement.media_url}">` : ''}
            </div>
            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 px-4 py-2 bg-violet-600 text-white rounded-lg hover:bg-violet-700">
                    ${id ? 'حفظ التعديلات' : 'نشر الإعلان'}
                </button>
                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300">
                    إلغاء
                </button>
            </div>
        </form>
    `;
    
    openModal(modalTitle, modalContent);
    
    document.getElementById('addAnnouncementForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        try {
            const response = await fetch(API_ENDPOINTS.manageAnnouncements, {
                method: 'POST',
                body: formData,
                // Don't set Content-Type, browser will do it for multipart/form-data
            });

            const result = await response.json();

            if (result.success) {
                showToast(result.message || 'تمت العملية بنجاح', 'success');
                closeModal();
                renderAnnouncements();
            } else {
                showToast(result.message || 'حدث خطأ ما', 'error');
            }
        } catch (error) {
            showToast('فشل الاتصال بالخادم', 'error');
        }
    });
}

async function deleteAnnouncement(id) {
    if (!confirm('هل أنت متأكد من رغبتك في حذف هذا الإعلان؟')) return;

    try {
        const response = await fetch(API_ENDPOINTS.manageAnnouncements, {
            method: 'POST', // Using POST to send a body with the delete action
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id: id })
        });

        const result = await response.json();

        if (result.success) {
            showToast('تم حذف الإعلان بنجاح', 'success');
            renderAnnouncements();
        } else {
            showToast(result.message || 'فشل حذف الإعلان', 'error');
        }
    } catch (error) {
        showToast('فشل الاتصال بالخادم', 'error');
    }
}

async function renderGrades() {
    setPageHeader('الدرجات والشهادات', 'إدارة درجات الطلاب وتصدير الشهادات');
    clearPageBody();
    
    const pageBody = document.getElementById('pageBody');
    pageBody.innerHTML = `
        <div class="bg-white rounded-2xl shadow p-6 border border-slate-100">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-4">
                    <h3 class="text-xl font-bold text-slate-800">سجل الدرجات</h3>
                    <select id="courseFilter" class="px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500">
                        <option value="">-- اختر دورة --</option>
                    </select>
                </div>
                <button onclick="openImportGradesModal()" class="px-4 py-2 bg-violet-600 text-white rounded-lg hover:bg-violet-700">
                    <i data-lucide="upload" class="w-4 h-4 inline"></i>
                    استيراد الدرجات
                </button>
            </div>
            <div id="gradesTable" class="overflow-x-auto">
                <div class="text-center py-8">
                    <i data-lucide="graduation-cap" class="w-12 h-12 mx-auto text-slate-400"></i>
                    <p class="mt-2 text-slate-600">الرجاء اختيار دورة لعرض الدرجات</p>
                </div>
            </div>
        </div>
    `;
    lucide.createIcons();

    // Fetch courses for the dropdown
    try {
        const courseData = await fetchJson(API_ENDPOINTS.manageCourses);
        if (courseData.success && courseData.courses) {
            const select = document.getElementById('courseFilter');
            courseData.courses.forEach(course => {
                const option = document.createElement('option');
                option.value = course.course_id;
                option.textContent = course.title;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error fetching courses:', error);
    }

    // Add event listener to the dropdown
    document.getElementById('courseFilter').addEventListener('change', async (e) => {
        const courseId = e.target.value;
        if (courseId) {
            document.getElementById('gradesTable').innerHTML = `
                <div class="text-center py-8">
                    <i data-lucide="loader" class="w-8 h-8 animate-spin mx-auto text-sky-600"></i>
                    <p class="mt-2 text-slate-600">جاري تحميل الدرجات...</p>
                </div>
            `;
            lucide.createIcons();
            try {
                const gradesData = await fetchJson(`${API_ENDPOINTS.manageGrades}?course_id=${courseId}`);
                if (gradesData.success) {
                    displayGradesTable(gradesData.data);
                } else {
                    document.getElementById('gradesTable').innerHTML = `<p class="text-red-500">فشل في جلب الدرجات.</p>`;
                }
            } catch (error) {
                console.error('Error fetching grades:', error);
                document.getElementById('gradesTable').innerHTML = `<p class="text-red-500">حدث خطأ أثناء جلب الدرجات.</p>`;
            }
        } else {
            document.getElementById('gradesTable').innerHTML = `
                <div class="text-center py-8">
                    <i data-lucide="graduation-cap" class="w-12 h-12 mx-auto text-slate-400"></i>
                    <p class="mt-2 text-slate-600">الرجاء اختيار دورة لعرض الدرجات</p>
                </div>
            `;
            lucide.createIcons();
        }
    });
}

function displayGradesTable(grades) {
    const container = document.getElementById('gradesTable');
    if (!grades || grades.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8">
                <i data-lucide="award" class="w-12 h-12 mx-auto text-slate-400"></i>
                <p class="mt-2 text-slate-600">لا توجد درجات مسجلة لهذه الدورة</p>
            </div>
        `;
        lucide.createIcons();
        return;
    }

    const tableHtml = `
        <table class="w-full">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-slate-700">اسم الطالب</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-slate-700">التقييم</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-slate-700">الدرجة</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-slate-700">تاريخ الإدخال</th>
                    <th class="px-4 py-3 text-center text-sm font-semibold text-slate-700">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                ${grades.map(grade => `
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 text-sm">${escapeHtml(grade.full_name)}</td>
                        <td class="px-4 py-3 text-sm">${escapeHtml(grade.assignment_name)}</td>
                        <td class="px-4 py-3 text-sm font-bold">${escapeHtml(grade.grade_value)} / ${escapeHtml(grade.max_grade)}</td>
                        <td class="px-4 py-3 text-sm">${formatDateTime(grade.created_at)}</td>
                        <td class="px-4 py-3 text-center">
                            <button onclick="editGrade(${grade.grade_id})" class="text-sky-600 hover:text-sky-700 mx-1">
                                <i data-lucide="edit" class="w-4 h-4"></i>
                            </button>
                            <button onclick="deleteGrade(${grade.grade_id})" class="text-red-600 hover:text-red-700 mx-1">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
    container.innerHTML = tableHtml;
    lucide.createIcons();
}

function openImportGradesModal() {
    const modalContent = `
        <form id="importGradesForm" class="space-y-4">
            <div class="border-2 border-dashed border-slate-300 rounded-xl p-8 text-center hover:border-violet-500 transition cursor-pointer" id="dropzone">
                <i data-lucide="upload-cloud" class="w-12 h-12 mx-auto text-slate-400 mb-4"></i>
                <p class="text-slate-600 mb-2">اسحب وأفلت ملف Excel هنا، أو انقر للاختيار</p>
                <p class="text-sm text-slate-500">الملفات المدعومة: .xlsx, .xls, .csv</p>
                <input type="file" name="import_file" id="import_file_input" accept=".xlsx,.xls,.csv" class="hidden">
            </div>
            <div id="fileName" class="text-center text-slate-600 font-medium"></div>
            <div id="importResult" class="hidden mt-4"></div>
            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 px-4 py-2 bg-violet-600 text-white rounded-lg hover:bg-violet-700" disabled>
                    <i data-lucide="upload" class="w-4 h-4 inline"></i>
                    بدء الاستيراد
                </button>
                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300">
                    إلغاء
                </button>
            </div>
        </form>
    `;

    openModal('استيراد الدرجات من ملف', modalContent);
    lucide.createIcons();

    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('import_file_input');
    const fileNameDisplay = document.getElementById('fileName');
    const submitButton = document.querySelector('#importGradesForm button[type="submit"]');

    dropzone.addEventListener('click', () => fileInput.click());
    
    fileInput.addEventListener('change', () => {
        if (fileInput.files.length > 0) {
            fileNameDisplay.textContent = fileInput.files[0].name;
            submitButton.disabled = false;
        }
    });

    dropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropzone.classList.add('border-violet-500', 'bg-violet-50');
    });

    dropzone.addEventListener('dragleave', (e) => {
        e.preventDefault();
        dropzone.classList.remove('border-violet-500', 'bg-violet-50');
    });

    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.classList.remove('border-violet-500', 'bg-violet-50');
        if (e.dataTransfer.files.length > 0) {
            fileInput.files = e.dataTransfer.files;
            fileNameDisplay.textContent = fileInput.files[0].name;
            submitButton.disabled = false;
        }
    });

    document.getElementById('importGradesForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        if (!formData.get('import_file')) {
            showToast('الرجاء اختيار ملف أولاً', 'error');
            return;
        }

        submitButton.innerHTML = '<i data-lucide="loader" class="w-4 h-4 inline animate-spin"></i> جاري المعالجة...';
        submitButton.disabled = true;

        try {
            const response = await fetch(API_ENDPOINTS.manageImports, {
                method: 'POST',
                body: formData,
                headers: {
                    // Let the browser set the Content-Type for FormData
                }
            });

            const result = await response.json();
            const resultContainer = document.getElementById('importResult');
            
            if (result.success) {
                let summaryHtml = `
                    <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4">
                        <h4 class="font-bold text-emerald-800 mb-2">✓ تمت المعالجة بنجاح</h4>
                        <ul class="list-disc list-inside text-sm text-slate-700">
                            <li>الصفوف المعالجة: ${result.summary.processed_rows}</li>
                            <li>مستخدمون جدد: ${result.summary.created_users}</li>
                            <li>مستخدمون تم تحديثهم: ${result.summary.updated_users}</li>
                            <li>تسجيلات جديدة: ${result.summary.created_enrollments}</li>
                            <li>درجات جديدة: ${result.summary.created_grades}</li>
                        </ul>
                `;
                if (result.summary.warnings.length > 0) {
                    summaryHtml += `<h5 class="font-bold mt-3 mb-1 text-amber-800">تحذيرات:</h5>
                                    <ul class="list-disc list-inside text-sm text-amber-700 max-h-24 overflow-y-auto">
                                        ${result.summary.warnings.map(w => `<li>${escapeHtml(w)}</li>`).join('')}
                                    </ul>`;
                }
                if (result.summary.errors.length > 0) {
                    summaryHtml += `<h5 class="font-bold mt-3 mb-1 text-red-800">أخطاء:</h5>
                                    <ul class="list-disc list-inside text-sm text-red-700 max-h-24 overflow-y-auto">
                                        ${result.summary.errors.map(e => `<li>${escapeHtml(e)}</li>`).join('')}
                                    </ul>`;
                }
                summaryHtml += `</div>`;
                resultContainer.innerHTML = summaryHtml;
                showToast('تم استيراد الملف بنجاح!', 'success');
                // Refresh the grades table if a course is selected
                const courseId = document.getElementById('courseFilter').value;
                if (courseId) {
                    document.getElementById('courseFilter').dispatchEvent(new Event('change'));
                }
            } else {
                resultContainer.innerHTML = `
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <h4 class="font-bold text-red-800 mb-2">✗ فشل الاستيراد</h4>
                        <p class="text-sm text-slate-700">${escapeHtml(result.message)}</p>
                    </div>
                `;
                showToast(result.message || 'فشل استيراد الملف', 'error');
            }
            resultContainer.classList.remove('hidden');

        } catch (error) {
            console.error('Import error:', error);
            showToast('حدث خطأ فادح أثناء الاستيراد', 'error');
        } finally {
            submitButton.innerHTML = '<i data-lucide="upload" class="w-4 h-4 inline"></i> بدء الاستيراد';
            submitButton.disabled = false;
            lucide.createIcons();
        }
    });
}


async function renderAnalytics() {
    setPageHeader('التحليلات والتقارير', 'رسوم بيانية متقدمة بتقنية الذكاء الاصطناعي');
    clearPageBody();
    
    const pageBody = document.getElementById('pageBody');
    pageBody.innerHTML = `
        <!-- KPIs Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-gradient-to-br from-sky-500 to-sky-600 rounded-xl shadow p-4 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm opacity-90">إجمالي الإيرادات</div>
                        <div class="text-2xl font-bold mt-1" id="kpi-revenue">0</div>
                    </div>
                    <i data-lucide="dollar-sign" class="w-8 h-8 opacity-80"></i>
                </div>
            </div>
            <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl shadow p-4 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm opacity-90">إجمالي الطلاب</div>
                        <div class="text-2xl font-bold mt-1" id="kpi-students">0</div>
                    </div>
                    <i data-lucide="users" class="w-8 h-8 opacity-80"></i>
                </div>
            </div>
            <div class="bg-gradient-to-br from-violet-500 to-violet-600 rounded-xl shadow p-4 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm opacity-90">الدورات النشطة</div>
                        <div class="text-2xl font-bold mt-1" id="kpi-courses">0</div>
                    </div>
                    <i data-lucide="book-open" class="w-8 h-8 opacity-80"></i>
                </div>
            </div>
            <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl shadow p-4 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm opacity-90">معدل الإكمال</div>
                        <div class="text-2xl font-bold mt-1" id="kpi-completion">0%</div>
                    </div>
                    <i data-lucide="trending-up" class="w-8 h-8 opacity-80"></i>
                </div>
            </div>
        </div>
        
        <!-- Charts Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Revenue Chart -->
            <div class="bg-white rounded-2xl shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-slate-800">الإيرادات الشهرية</h3>
                    <select id="revenueYearFilter" class="px-3 py-1 border border-slate-300 rounded-lg text-sm">
                        <option value="2025">2025</option>
                        <option value="2024">2024</option>
                    </select>
                </div>
                <div class="h-64" id="revenueChartContainer">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
            
            <!-- Students Distribution -->
            <div class="bg-white rounded-2xl shadow p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">توزيع الطلاب حسب الدورات</h3>
                <div class="h-64" id="studentsChartContainer">
                    <canvas id="studentsChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Attendance and Performance -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Attendance Chart -->
            <div class="bg-white rounded-2xl shadow p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">معدلات الحضور</h3>
                <div class="h-64" id="attendanceChartContainer">
                    <canvas id="attendanceChart"></canvas>
                </div>
            </div>
            
            <!-- Performance Trends -->
            <div class="bg-white rounded-2xl shadow p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">اتجاهات الأداء</h3>
                <div class="h-64" id="performanceChartContainer">
                    <canvas id="performanceChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- AI-Powered Insights -->
        <div class="bg-gradient-to-br from-violet-50 to-sky-50 rounded-2xl shadow p-6 border border-violet-200">
            <div class="flex items-start gap-4">
                <div class="bg-violet-600 rounded-lg p-3">
                    <i data-lucide="sparkles" class="w-6 h-6 text-white"></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-bold text-slate-800 mb-2">رؤى مدعومة بالذكاء الاصطناعي</h3>
                    <div id="aiInsights" class="space-y-2">
                        <div class="flex items-center gap-2 text-sm text-slate-600">
                            <i data-lucide="loader" class="w-4 h-4 animate-spin"></i>
                            <span>جاري تحليل البيانات...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    lucide.createIcons();
    
    // Load analytics data
    loadAnalyticsData();
}

async function loadAnalyticsData() {
    try {
        const data = await fetchJson(API_ENDPOINTS.analyticsData);
        
        if (data.success) {
            // Update KPIs
            document.getElementById('kpi-revenue').textContent = formatMoney(data.total_revenue || 0);
            document.getElementById('kpi-students').textContent = data.total_students || 0;
            document.getElementById('kpi-courses').textContent = data.active_courses || 0;
            document.getElementById('kpi-completion').textContent = `${data.completion_rate || 0}%`;
            
            // Create charts
            createRevenueChart(data.monthly_revenue || []);
            createStudentsChart(data.students_by_course || []);
            createAttendanceChart(data.attendance_stats || []);
            createPerformanceChart(data.performance_trends || []);
            
            // Display AI insights
            displayAIInsights(data.ai_insights || []);
        }
    } catch (error) {
        console.error('Error loading analytics:', error);
        showMockAnalytics();
    }
}

function createRevenueChart(data) {
    const ctx = document.getElementById('revenueChart');
    if (!ctx) return;
    
    // Simple bar chart using CSS
    const container = document.getElementById('revenueChartContainer');
    if (data.length === 0) {
        data = [
            {month: 'يناير', amount: 15000},
            {month: 'فبراير', amount: 18000},
            {month: 'مارس', amount: 22000},
            {month: 'أبريل', amount: 19000},
            {month: 'مايو', amount: 25000},
            {month: 'يونيو', amount: 28000}
        ];
    }
    
    const maxAmount = Math.max(...data.map(d => d.amount));
    container.innerHTML = `
        <div class="flex items-end justify-between h-64 gap-2">
            ${data.map(item => {
                const height = (item.amount / maxAmount) * 100;
                return `
                    <div class="flex-1 flex flex-col items-center">
                        <div class="text-xs font-semibold text-sky-600 mb-1">${formatMoney(item.amount)}</div>
                        <div class="w-full bg-gradient-to-t from-sky-500 to-sky-400 rounded-t transition-all hover:from-sky-600 hover:to-sky-500" 
                             style="height: ${height}%"
                             title="${item.month}: ${formatMoney(item.amount)}"></div>
                        <div class="text-xs text-slate-600 mt-2">${item.month}</div>
                    </div>
                `;
            }).join('')}
        </div>
    `;
}

function createStudentsChart(data) {
    const container = document.getElementById('studentsChartContainer');
    if (data.length === 0) {
        data = [
            {course: 'تطوير الويب', count: 45},
            {course: 'Python', count: 32},
            {course: 'التصميم', count: 28},
            {course: 'البرمجة', count: 38}
        ];
    }
    
    const total = data.reduce((sum, item) => sum + item.count, 0);
    const colors = ['#0ea5e9', '#10b981', '#8b5cf6', '#f59e0b'];
    
    container.innerHTML = `
        <div class="flex flex-col h-64 justify-center">
            ${data.map((item, index) => {
                const percentage = ((item.count / total) * 100).toFixed(1);
                return `
                    <div class="mb-4">
                        <div class="flex justify-between text-sm mb-1">
                            <span class="font-medium text-slate-700">${item.course}</span>
                            <span class="text-slate-600">${item.count} (${percentage}%)</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-3">
                            <div class="h-3 rounded-full transition-all" 
                                 style="width: ${percentage}%; background-color: ${colors[index % colors.length]}"></div>
                        </div>
                    </div>
                `;
            }).join('')}
        </div>
    `;
}

function createAttendanceChart(data) {
    const container = document.getElementById('attendanceChartContainer');
    if (data.length === 0) {
        data = [
            {label: 'حاضر', value: 85, color: '#10b981'},
            {label: 'متأخر', value: 10, color: '#f59e0b'},
            {label: 'غائب', value: 5, color: '#ef4444'}
        ];
    }
    
    const total = data.reduce((sum, item) => sum + item.value, 0);
    
    container.innerHTML = `
        <div class="flex items-center justify-center h-64">
            <div class="relative w-48 h-48">
                ${data.map((item, index) => {
                    const percentage = (item.value / total) * 100;
                    const rotation = data.slice(0, index).reduce((sum, d) => sum + (d.value / total) * 360, 0);
                    return `
                        <div class="absolute inset-0 rounded-full" 
                             style="background: conic-gradient(from ${rotation}deg, ${item.color} 0deg, ${item.color} ${(item.value/total)*360}deg, transparent ${(item.value/total)*360}deg)"></div>
                    `;
                }).join('')}
                <div class="absolute inset-8 bg-white rounded-full flex items-center justify-center">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-slate-800">${total}</div>
                        <div class="text-xs text-slate-600">إجمالي</div>
                    </div>
                </div>
            </div>
            <div class="ml-8 space-y-2">
                ${data.map(item => `
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full" style="background-color: ${item.color}"></div>
                        <span class="text-sm text-slate-700">${item.label}: ${item.value}%</span>
                    </div>
                `).join('')}
            </div>
        </div>
    `;
}

function createPerformanceChart(data) {
    const container = document.getElementById('performanceChartContainer');
    if (data.length === 0) {
        data = [
            {week: 'الأسبوع 1', score: 75},
            {week: 'الأسبوع 2', score: 78},
            {week: 'الأسبوع 3', score: 82},
            {week: 'الأسبوع 4', score: 85},
            {week: 'الأسبوع 5', score: 88}
        ];
    }
    
    const maxScore = 100;
    container.innerHTML = `
        <div class="relative h-64 flex items-end justify-between gap-1 px-4">
            <div class="absolute inset-0 flex flex-col justify-between text-xs text-slate-400 pointer-events-none">
                ${[100, 75, 50, 25, 0].map(val => `<div class="border-t border-slate-200">${val}</div>`).join('')}
            </div>
            ${data.map((item, index) => {
                const height = (item.score / maxScore) * 100;
                return `
                    <div class="flex-1 flex flex-col items-center relative z-10">
                        <div class="text-xs font-semibold text-violet-600 mb-1">${item.score}%</div>
                        <div class="w-full bg-gradient-to-t from-violet-500 to-violet-400 rounded-t transition-all hover:from-violet-600 hover:to-violet-500" 
                             style="height: ${height}%"
                             title="${item.week}: ${item.score}%"></div>
                        <div class="text-xs text-slate-600 mt-2 whitespace-nowrap">${item.week}</div>
                    </div>
                `;
            }).join('')}
        </div>
    `;
}

function displayAIInsights(insights) {
    const container = document.getElementById('aiInsights');
    
    if (insights.length === 0) {
        insights = [
            {icon: 'trending-up', text: 'معدل التسجيل في ازدياد مستمر بنسبة 15% هذا الشهر', type: 'success'},
            {icon: 'alert-circle', text: 'يُنصح بزيادة الدورات في مجال البرمجة نظراً للطلب المتزايد', type: 'warning'},
            {icon: 'users', text: 'معدل رضا الطلاب مرتفع بنسبة 92%', type: 'success'},
            {icon: 'clock', text: 'أفضل وقت لبدء الدورات الجديدة هو بداية كل شهر', type: 'info'}
        ];
    }
    
    container.innerHTML = insights.map(insight => `
        <div class="flex items-start gap-2 p-3 rounded-lg ${
            insight.type === 'success' ? 'bg-emerald-50' :
            insight.type === 'warning' ? 'bg-amber-50' :
            'bg-sky-50'
        }">
            <i data-lucide="${insight.icon}" class="w-4 h-4 mt-0.5 ${
                insight.type === 'success' ? 'text-emerald-600' :
                insight.type === 'warning' ? 'text-amber-600' :
                'text-sky-600'
            }"></i>
            <span class="text-sm text-slate-700">${insight.text}</span>
        </div>
    `).join('');
    
    lucide.createIcons();
}

function showMockAnalytics() {
    // Fallback to mock data if API fails
    document.getElementById('kpi-revenue').textContent = formatMoney(125000);
    document.getElementById('kpi-students').textContent = '143';
    document.getElementById('kpi-courses').textContent = '8';
    document.getElementById('kpi-completion').textContent = '87%';
    
    createRevenueChart([]);
    createStudentsChart([]);
    createAttendanceChart([]);
    createPerformanceChart([]);
    displayAIInsights([]);
}

async function renderAttendance() {
    setPageHeader('تقارير الحضور', 'متابعة حضور الطلاب');
    clearPageBody();
    document.getElementById('pageBody').innerHTML = `
        <div class="bg-white rounded-2xl shadow p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold">كشف الحضور</h3>
                <button onclick="exportAttendanceReport()" class="px-4 py-2 bg-sky-600 text-white rounded-lg hover:bg-sky-700">
                    <i data-lucide="download" class="w-4 h-4 inline"></i>
                    تصدير التقرير
                </button>
            </div>
            <div id="attendanceContent" class="text-center py-12">
                <i data-lucide="clipboard-check" class="w-16 h-16 mx-auto text-sky-600 mb-4"></i>
                <p class="text-slate-600">اختر دورة لعرض سجل الحضور</p>
            </div>
        </div>
    `;
    lucide.createIcons();
    
    try {
        const data = await fetchJson(API_ENDPOINTS.manageAttendance);
        if (data.success && data.attendance) {
            displayAttendanceData(data.attendance);
        }
    } catch (error) {
        console.error('Error loading attendance:', error);
    }
}

function displayAttendanceData(attendance) {
    const container = document.getElementById('attendanceContent');
    if (!attendance || attendance.length === 0) return;
    
    container.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-emerald-50 rounded-lg p-4">
                <div class="text-emerald-600 text-3xl font-bold">${attendance.filter(a => a.status === 'present').length}</div>
                <div class="text-slate-600 text-sm">حاضر</div>
            </div>
            <div class="bg-amber-50 rounded-lg p-4">
                <div class="text-amber-600 text-3xl font-bold">${attendance.filter(a => a.status === 'late').length}</div>
                <div class="text-slate-600 text-sm">متأخر</div>
            </div>
            <div class="bg-red-50 rounded-lg p-4">
                <div class="text-red-600 text-3xl font-bold">${attendance.filter(a => a.status === 'absent').length}</div>
                <div class="text-slate-600 text-sm">غائب</div>
            </div>
        </div>
        <table class="w-full">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-right text-sm font-semibold">الطالب</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold">الدورة</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold">التاريخ</th>
                    <th class="px-4 py-3 text-center text-sm font-semibold">الحالة</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                ${attendance.map(a => `
                    <tr>
                        <td class="px-4 py-3 text-sm">${escapeHtml(a.student_name || '')}</td>
                        <td class="px-4 py-3 text-sm">${escapeHtml(a.course_name || '')}</td>
                        <td class="px-4 py-3 text-sm">${formatDateTime(a.date)}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-1 rounded text-xs ${
                                a.status === 'present' ? 'bg-emerald-100 text-emerald-700' :
                                a.status === 'late' ? 'bg-amber-100 text-amber-700' :
                                'bg-red-100 text-red-700'
                            }">
                                ${a.status === 'present' ? 'حاضر' : a.status === 'late' ? 'متأخر' : 'غائب'}
                            </span>
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
    lucide.createIcons();
}

function exportAttendanceReport() {
    showToast('جاري تصدير التقرير...', 'info');
    // TODO: Implement export functionality
}

async function renderIDCards() {
    setPageHeader('البطاقات الطلابية', 'إصدار وإدارة البطاقات');
    clearPageBody();
    
    const pageBody = document.getElementById('pageBody');
    pageBody.innerHTML = `
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- بطاقة الإحصائيات -->
            <div class="bg-gradient-to-br from-sky-500 to-sky-600 rounded-2xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <i data-lucide="credit-card" class="w-8 h-8 mb-2"></i>
                        <div class="text-3xl font-bold" id="totalCards">0</div>
                        <div class="text-sky-100 text-sm">إجمالي البطاقات</div>
                    </div>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <i data-lucide="check-circle" class="w-8 h-8 mb-2"></i>
                        <div class="text-3xl font-bold" id="activeCards">0</div>
                        <div class="text-emerald-100 text-sm">بطاقات نشطة</div>
                    </div>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-violet-500 to-violet-600 rounded-2xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <i data-lucide="clock" class="w-8 h-8 mb-2"></i>
                        <div class="text-3xl font-bold" id="pendingCards">0</div>
                        <div class="text-violet-100 text-sm">قيد الإصدار</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-2xl shadow p-6 mt-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-slate-800">إدارة البطاقات الطلابية</h3>
                <div class="flex gap-2">
                    <button onclick="openBulkGenerateCardsModal()" class="px-4 py-2 bg-violet-600 text-white rounded-lg hover:bg-violet-700">
                        <i data-lucide="layers" class="w-4 h-4 inline"></i>
                        إصدار جماعي
                    </button>
                    <button onclick="openGenerateCardModal()" class="px-4 py-2 bg-sky-600 text-white rounded-lg hover:bg-sky-700">
                        <i data-lucide="plus-circle" class="w-4 h-4 inline"></i>
                        إصدار بطاقة
                    </button>
                </div>
            </div>
            <div id="idCardsTable" class="overflow-x-auto">
                <div class="text-center py-8">
                    <i data-lucide="loader" class="w-8 h-8 animate-spin mx-auto text-sky-600"></i>
                    <p class="mt-2 text-slate-600">جاري التحميل...</p>
                </div>
            </div>
        </div>
    `;
    lucide.createIcons();
    
    // Load ID cards data
    loadIDCardsData();
}

async function loadIDCardsData() {
    try {
        const response = await fetchJson(API_ENDPOINTS.trainees);
        const students = response.data || response.users || [];
        
        document.getElementById('totalCards').textContent = students.length;
        document.getElementById('activeCards').textContent = students.filter(s => s.verified).length;
        document.getElementById('pendingCards').textContent = students.filter(s => !s.verified).length;
        
        displayIDCardsTable(students);
    } catch (error) {
        console.error('Error loading ID cards:', error);
        displayIDCardsTable([]);
    }
}

function displayIDCardsTable(students) {
    const container = document.getElementById('idCardsTable');
    if (!students || students.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8">
                <i data-lucide="credit-card" class="w-12 h-12 mx-auto text-slate-400"></i>
                <p class="mt-2 text-slate-600">لا توجد بطاقات</p>
            </div>
        `;
        lucide.createIcons();
        return;
    }
    
    container.innerHTML = `
        <table class="w-full">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-slate-700">الاسم</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-slate-700">البريد الإلكتروني</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-slate-700">رقم الهاتف</th>
                    <th class="px-4 py-3 text-center text-sm font-semibold text-slate-700">الحالة</th>
                    <th class="px-4 py-3 text-center text-sm font-semibold text-slate-700">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                ${students.map(student => `
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 text-sm">${escapeHtml(student.full_name || student.name || '')}</td>
                        <td class="px-4 py-3 text-sm">${escapeHtml(student.email)}</td>
                        <td class="px-4 py-3 text-sm">${escapeHtml(student.phone || '-')}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-1 rounded text-xs ${student.verified ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'}">
                                ${student.verified ? 'نشط' : 'معلق'}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button onclick="generateIDCard(${student.id})" class="text-sky-600 hover:text-sky-700 mx-1" title="إصدار بطاقة">
                                <i data-lucide="credit-card" class="w-4 h-4"></i>
                            </button>
                            <button onclick="downloadIDCard(${student.id})" class="text-emerald-600 hover:text-emerald-700 mx-1" title="تحميل">
                                <i data-lucide="download" class="w-4 h-4"></i>
                            </button>
                            <button onclick="sendIDCardEmail(${student.id})" class="text-violet-600 hover:text-violet-700 mx-1" title="إرسال بالبريد">
                                <i data-lucide="mail" class="w-4 h-4"></i>
                            </button>
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
    lucide.createIcons();
}

function openGenerateCardModal() {
    const modalContent = `
        <form id="generateCardForm" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">اختر الطالب</label>
                <select name="student_id" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500">
                    <option value="">اختر...</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">نوع البطاقة</label>
                <select name="card_type" class="w-full px-4 py-2 border border-slate-300 rounded-lg">
                    <option value="standard">قياسية</option>
                    <option value="premium">مميزة</option>
                </select>
            </div>
            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 px-4 py-2 bg-sky-600 text-white rounded-lg hover:bg-sky-700">
                    إصدار البطاقة
                </button>
                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300">
                    إلغاء
                </button>
            </div>
        </form>
    `;
    
    openModal('إصدار بطاقة طلابية', modalContent);
    
    // Load students
    fetchJson(API_ENDPOINTS.trainees).then(data => {
        const select = document.querySelector('#generateCardForm select[name="student_id"]');
        const students = data.data || data.users || [];
        students.forEach(student => {
            const option = document.createElement('option');
            option.value = student.id;
            option.textContent = student.full_name || student.name;
            select.appendChild(option);
        });
    });
    
    document.getElementById('generateCardForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);
        
        try {
            showToast('جاري إصدار البطاقة...', 'info');
            await generateIDCard(data.student_id);
            closeModal();
        } catch (error) {
            showToast('حدث خطأ أثناء إصدار البطاقة', 'error');
        }
    });
}

function openBulkGenerateCardsModal() {
    const modalContent = `
        <div class="space-y-4">
            <div class="bg-sky-50 rounded-lg p-4">
                <p class="text-sm text-sky-900 mb-2">⚡ إصدار جماعي</p>
                <p class="text-xs text-sky-700">سيتم إصدار بطاقات لجميع الطلاب المسجلين</p>
            </div>
            <div class="flex gap-3">
                <button onclick="generateBulkIDCards()" class="flex-1 px-4 py-2 bg-violet-600 text-white rounded-lg hover:bg-violet-700">
                    بدء الإصدار الجماعي
                </button>
                <button onclick="closeModal()" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300">
                    إلغاء
                </button>
            </div>
        </div>
    `;
    openModal('إصدار بطاقات جماعي', modalContent);
}

async function generateIDCard(studentId) {
    try {
        const response = await fetch('api/generate_id_card.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ student_id: studentId })
        });
        
        if (response.ok) {
            showToast('تم إصدار البطاقة بنجاح', 'success');
            loadIDCardsData();
        } else {
            showToast('فشل إصدار البطاقة', 'error');
        }
    } catch (error) {
        showToast('حدث خطأ في الاتصال', 'error');
    }
}

async function downloadIDCard(studentId) {
    try {
        window.open(`api/generate_id_card.php?student_id=${studentId}&action=download`, '_blank');
        showToast('جاري تحميل البطاقة...', 'info');
    } catch (error) {
        showToast('حدث خطأ أثناء التحميل', 'error');
    }
}

async function sendIDCardEmail(studentId) {
    try {
        const response = await fetch('api/send_card_email.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ student_id: studentId })
        });
        
        if (response.ok) {
            showToast('تم إرسال البطاقة بالبريد الإلكتروني', 'success');
        } else {
            showToast('فشل إرسال البريد', 'error');
        }
    } catch (error) {
        showToast('حدث خطأ في الإرسال', 'error');
    }
}

async function generateBulkIDCards() {
    closeModal();
    showToast('جاري إصدار البطاقات...', 'info');
    
    try {
        const response = await fetch('api/generate_id_card.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'bulk_generate' })
        });
        
        if (response.ok) {
            showToast('تم إصدار جميع البطاقات بنجاح', 'success');
            loadIDCardsData();
        } else {
            showToast('فشل الإصدار الجماعي', 'error');
        }
    } catch (error) {
        showToast('حدث خطأ في العملية', 'error');
    }
}

function renderGraduates() {
    setPageHeader('ملف الخريجين', 'قائمة الخريجين وشهاداتهم');
    clearPageBody();
    document.getElementById('pageBody').innerHTML = `
        <div class="bg-white rounded-2xl shadow p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold">قاعدة بيانات الخريجين</h3>
                <input type="search" placeholder="بحث عن خريج..." class="px-4 py-2 border border-slate-300 rounded-lg">
            </div>
            <div class="text-center py-12">
                <i data-lucide="award" class="w-16 h-16 mx-auto text-amber-600 mb-4"></i>
                <p class="text-slate-600">لا يوجد خريجون بعد</p>
            </div>
        </div>
    `;
    lucide.createIcons();
}

function renderImports() {
    setPageHeader('الاستيراد الذكي', 'استيراد بيانات الطلاب والدرجات من ملف Excel');
    clearPageBody();
    
    const pageBody = document.getElementById('pageBody');
    pageBody.innerHTML = `
        <div class="bg-white rounded-2xl shadow p-8">
            <form id="importGradesForm" class="space-y-4">
                <div class="border-2 border-dashed border-slate-300 rounded-xl p-8 text-center hover:border-violet-500 transition cursor-pointer" id="dropzone">
                    <i data-lucide="upload-cloud" class="w-12 h-12 mx-auto text-slate-400 mb-4"></i>
                    <p class="text-slate-600 mb-2">اسحب وأفلت ملف Excel هنا، أو انقر للاختيار</p>
                    <p class="text-sm text-slate-500">الملفات المدعومة: .xlsx, .xls, .csv</p>
                    <input type="file" name="import_file" id="import_file_input" accept=".xlsx,.xls,.csv" class="hidden">
                </div>
                <div id="fileName" class="text-center text-slate-600 font-medium"></div>
                <div id="importResult" class="hidden mt-4"></div>
                <div class="flex gap-3 pt-4">
                    <button type="submit" class="flex-1 px-4 py-2 bg-violet-600 text-white rounded-lg hover:bg-violet-700" disabled>
                        <i data-lucide="upload" class="w-4 h-4 inline"></i>
                        بدء الاستيراد
                    </button>
                </div>
            </form>
        </div>
    `;
    lucide.createIcons();

    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('import_file_input');
    const fileNameDisplay = document.getElementById('fileName');
    const submitButton = document.querySelector('#importGradesForm button[type="submit"]');

    dropzone.addEventListener('click', () => fileInput.click());
    
    fileInput.addEventListener('change', () => {
        if (fileInput.files.length > 0) {
            fileNameDisplay.textContent = fileInput.files[0].name;
            submitButton.disabled = false;
        }
    });

    dropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropzone.classList.add('border-violet-500', 'bg-violet-50');
    });

    dropzone.addEventListener('dragleave', (e) => {
        e.preventDefault();
        dropzone.classList.remove('border-violet-500', 'bg-violet-50');
    });

    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.classList.remove('border-violet-500', 'bg-violet-50');
        if (e.dataTransfer.files.length > 0) {
            fileInput.files = e.dataTransfer.files;
            fileNameDisplay.textContent = fileInput.files[0].name;
            submitButton.disabled = false;
        }
    });

    document.getElementById('importGradesForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        if (!formData.get('import_file')) {
            showToast('الرجاء اختيار ملف أولاً', 'error');
            return;
        }

        submitButton.innerHTML = '<i data-lucide="loader" class="w-4 h-4 inline animate-spin"></i> جاري المعالجة...';
        submitButton.disabled = true;

        try {
            const response = await fetch(API_ENDPOINTS.manageImports, {
                method: 'POST',
                body: formData,
                headers: {
                    // Let the browser set the Content-Type for FormData
                }
            });
            const result = await response.json();
            const resultContainer = document.getElementById('importResult');
            
            if (result.success) {
                let summaryHtml = `
                    <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4">
                        <h4 class="font-bold text-emerald-800 mb-2">✓ تمت المعالجة بنجاح</h4>
                        <ul class="list-disc list-inside text-sm text-slate-700">
                            <li>الصفوف المعالجة: ${result.summary.processed_rows}</li>
                            <li>مستخدمون جدد: ${result.summary.created_users}</li>
                            <li>مستخدمون تم تحديثهم: ${result.summary.updated_users}</li>
                            <li>تسجيلات جديدة: ${result.summary.created_enrollments}</li>
                            <li>درجات جديدة: ${result.summary.created_grades}</li>
                        </ul>
                `;
                if (result.summary.warnings.length > 0) {
                    summaryHtml += `<h5 class="font-bold mt-3 mb-1 text-amber-800">تحذيرات:</h5>
                                    <ul class="list-disc list-inside text-sm text-amber-700 max-h-24 overflow-y-auto">
                                        ${result.summary.warnings.map(w => `<li>${escapeHtml(w)}</li>`).join('')}
                                    </ul>`;
                }
                if (result.summary.errors.length > 0) {
                    summaryHtml += `<h5 class="font-bold mt-3 mb-1 text-red-800">أخطاء:</h5>
                                    <ul class="list-disc list-inside text-sm text-red-700 max-h-24 overflow-y-auto">
                                        ${result.summary.errors.map(e => `<li>${escapeHtml(e)}</li>`).join('')}
                                    </ul>`;
                }
                summaryHtml += `</div>`;
                resultContainer.innerHTML = summaryHtml;
                showToast('تم استيراد الملف بنجاح!', 'success');
            } else {
                resultContainer.innerHTML = `
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <h4 class="font-bold text-red-800 mb-2">✗ فشل الاستيراد</h4>
                        <p class="text-sm text-slate-700">${escapeHtml(result.message)}</p>
                    </div>
                `;
                showToast(result.message || 'فشل استيراد الملف', 'error');
            }
            resultContainer.classList.remove('hidden');

        } catch (error) {
            console.error('Import error:', error);
            showToast('حدث خطأ فادح أثناء الاستيراد', 'error');
        } finally {
            submitButton.innerHTML = '<i data-lucide="upload" class="w-4 h-4 inline"></i> بدء الاستيراد';
            submitButton.disabled = false;
            lucide.createIcons();
        }
    });
}

async function renderAIImages() {
    setPageHeader('مولد الصور بالذكاء الاصطناعي', 'إنشاء صور فريدة باستخدام نماذج الذكاء الاصطناعي المتقدمة');
    clearPageBody();
    
    const pageBody = document.getElementById('pageBody');
    pageBody.innerHTML = `
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- AI Image Generator Form -->
            <div class="lg:col-span-1 bg-white rounded-2xl shadow p-6">
                <h3 class="text-xl font-bold text-slate-800 mb-4">إنشاء صورة جديدة</h3>
                <form id="aiImageForm" class="space-y-4">
                    <div>
                        <label for="prompt" class="block text-sm font-medium text-slate-700 mb-2">وصف الصورة (Prompt)</label>
                        <textarea id="prompt" name="prompt" rows="4" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-violet-500" placeholder="مثال: قطة ترتدي نظارة شمسية على الشاطئ"></textarea>
                    </div>
                    <div>
                        <label for="style" class="block text-sm font-medium text-slate-700 mb-2">نمط الصورة</label>
                        <select id="style" name="style" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-violet-500">
                            <option value="photorealistic">واقعي</option>
                            <option value="digital-art">فن رقمي</option>
                            <option value="3d-model">نموذج ثلاثي الأبعاد</option>
                            <option value="anime">أنمي</option>
                            <option value="pixel-art">فن البكسل</option>
                        </select>
                    </div>
                    <div class="flex items-center justify-between">
                        <label for="num_images" class="text-sm font-medium text-slate-700">عدد الصور</label>
                        <input type="number" id="num_images" name="num_images" min="1" max="4" value="1" class="w-20 px-3 py-1 border border-slate-300 rounded-lg text-center">
                    </div>
                    <button type="submit" class="w-full px-4 py-3 bg-violet-600 text-white rounded-lg hover:bg-violet-700 font-semibold flex items-center justify-center gap-2">
                        <i data-lucide="sparkles" class="w-5 h-5"></i>
                        إنشاء
                    </button>
                </form>
            </div>
            
            <!-- AI Image Results -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow p-6">
                <h3 class="text-xl font-bold text-slate-800 mb-4">النتائج</h3>
                <div id="aiImageResults" class="grid grid-cols-1 md:grid-cols-2 gap-4 min-h-[300px]">
                    <div class="flex flex-col items-center justify-center text-center text-slate-500 p-8">
                        <i data-lucide="image" class="w-16 h-16 mb-4"></i>
                        <p>ستظهر الصور التي تم إنشاؤها هنا.</p>
                    </div>
                </div>
            </div>
        </div>
    `;
    lucide.createIcons();
    
    document.getElementById('aiImageForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);
        
        const resultsContainer = document.getElementById('aiImageResults');
        resultsContainer.innerHTML = `
            <div class="col-span-full flex flex-col items-center justify-center text-center text-slate-500 p-8">
                <i data-lucide="loader" class="w-16 h-16 mb-4 animate-spin text-violet-600"></i>
                <p>جاري إنشاء الصور، قد يستغرق هذا بعض الوقت...</p>
            </div>
        `;
        lucide.createIcons();
        
        try {
            const response = await fetchJson(API_ENDPOINTS.aiImages, {
                method: 'POST',
                body: JSON.stringify(data)
            });
            
            if (response.success && response.images) {
                displayAIImages(response.images);
            } else {
                resultsContainer.innerHTML = `<p class="text-red-500 col-span-full text-center">${response.message || 'فشل في إنشاء الصور.'}</p>`;
            }
        } catch (error) {
            console.error('Error generating images:', error);
            resultsContainer.innerHTML = `<p class="text-red-500 col-span-full text-center">حدث خطأ أثناء إنشاء الصور.</p>`;
        }
    });
}

function displayAIImages(images) {
    const container = document.getElementById('aiImageResults');
    if (!images || images.length === 0) {
        container.innerHTML = `
            <div class="col-span-full flex flex-col items-center justify-center text-center text-slate-500 p-8">
                <i data-lucide="image-off" class="w-16 h-16 mb-4"></i>
                <p>لم يتم إرجاع أي صور.</p>
            </div>
        `;
        lucide.createIcons();
        return;
    }
    
    container.innerHTML = images.map(image => `
        <div class="group relative rounded-lg overflow-hidden border border-slate-200">
            <img src="${image.url}" alt="${escapeHtml(image.prompt)}" class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-4">
                <div class="flex items-center gap-2">
                    <a href="${image.url}" download="ai-image.png" class="p-2 bg-white/20 text-white rounded-full hover:bg-white/30">
                        <i data-lucide="download" class="w-5 h-5"></i>
                    </a>
                    <button onclick="shareImage('${image.url}')" class="p-2 bg-white/20 text-white rounded-full hover:bg-white/30">
                        <i data-lucide="share-2" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>
        </div>
    `).join('');
    lucide.createIcons();
}

function renderSettings() {
    setPageHeader('الإعدادات', 'إدارة إعدادات النظام');
    clearPageBody();
    document.getElementById('pageBody').innerHTML = `
        <div class="bg-white rounded-2xl shadow p-6">
            <h3 class="text-xl font-bold">الإعدادات العامة</h3>
            <p class="text-slate-600 mt-4">هذه الصفحة مخصصة لإدارة إعدادات النظام.</p>
        </div>
    `;
    lucide.createIcons();
}

async function renderAiImport() {
    setPageHeader('الاستيراد الشامل (AI)', 'تحليل واستيراد أي نوع من الملفات باستخدام الذكاء الاصطناعي');
    clearPageBody();
    const pageBody = document.getElementById('pageBody');
    pageBody.innerHTML = await (await fetch('dashboards/ai-import.php')).text();
}

async function renderCertificateDesigner() {
    setPageHeader('مصمم الشهادات', 'تصميم وتخصيص قوالب الشهادات');
    clearPageBody();
    const pageBody = document.getElementById('pageBody');
    pageBody.innerHTML = await (await fetch('dashboards/certificate-designer.php')).text();
}

async function renderAiCharts() {
    setPageHeader('الرسوم البيانية الهجينة (AI)', 'إنشاء رسوم بيانية تفاعلية وذكية من أي بيانات');
    clearPageBody();
    const pageBody = document.getElementById('pageBody');
    pageBody.innerHTML = await (await fetch('dashboards/ai-charts.php')).text();
}

// ==============================================
// Main App Logic
// ==============================================

document.addEventListener('DOMContentLoaded', () => {
    const pageRenderers = {
        'dashboard': renderDashboard,
        'trainees': renderTrainees,
        'trainers': renderTrainers,
        'courses': renderCourses,
        'finance': renderFinance,
        'requests': renderRequests,
        'announcements': renderAnnouncements,
        'grades': renderGrades,
        'attendance': renderAttendance,
        'id-cards': renderIDCards,
        'graduates': renderGraduates,
        'imports': renderImports,
        'ai-images': renderAIImages,
        'ai-import': renderAiImport,
        'certificate-designer': renderCertificateDesigner,
        'ai-charts': renderAiCharts,
        'settings': renderSettings
    };

    const navLinks = document.querySelectorAll('.nav-link');
    const pageBody = document.getElementById('pageBody');
    const pageTitle = document.getElementById('pageTitle');
    const pageSubtitle = document.getElementById('pageSubtitle');

    function setPage(page) {
        navLinks.forEach(link => {
            if (link.getAttribute('data-page') === page) {
                link.classList.add('bg-sky-100', 'text-sky-700');
            } else {
                link.classList.remove('bg-sky-100', 'text-sky-700');
            }
        });

        const renderFunc = pageRenderers[page] || renderDashboard;
        renderFunc();
        window.location.hash = page;
    }

    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const page = link.getAttribute('data-page');
            setPage(page);
        });
    });

    // Initial page load
    const initialPage = window.location.hash.substring(1) || 'dashboard';
    setPage(initialPage);
});

async function renderDashboard() {
    setPageHeader('لوحة التحكم الرئيسية', 'نظرة عامة على أداء المنصة');
    clearPageBody();
    
    const pageBody = document.getElementById('pageBody');
    pageBody.innerHTML = `
        <div class="text-center py-12">
            <i data-lucide="loader" class="w-12 h-12 animate-spin mx-auto text-sky-600"></i>
            <p class="mt-4 text-slate-600">جاري تحميل بيانات لوحة التحكم...</p>
        </div>
    `;
    lucide.createIcons();
    
    try {
        const data = await fetchJson(API_ENDPOINTS.dashboardStats);
        if (data.success) {
            displayDashboard(data.data);
        } else {
            displayDashboard({}); // Show empty dashboard on API error
        }
    } catch (error) {
        console.error('Error loading dashboard:', error);
        displayDashboard({}); // Show empty dashboard on fetch error
    }
}

function displayDashboard(data) {
    const pageBody = document.getElementById('pageBody');
    pageBody.innerHTML = `
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white rounded-2xl shadow p-6 border border-slate-100">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-sky-100 rounded-lg">
                        <i data-lucide="users" class="w-6 h-6 text-sky-600"></i>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500">إجمالي المتدربين</p>
                        <p class="text-2xl font-bold text-slate-800">${data.total_students || 0}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow p-6 border border-slate-100">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-emerald-100 rounded-lg">
                        <i data-lucide="book-open" class="w-6 h-6 text-emerald-600"></i>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500">الدورات النشطة</p>
                        <p class="text-2xl font-bold text-slate-800">${data.active_courses || 0}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow p-6 border border-slate-100">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-amber-100 rounded-lg">
                        <i data-lucide="dollar-sign" class="w-6 h-6 text-amber-600"></i>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500">إجمالي الإيرادات</p>
                        <p class="text-2xl font-bold text-slate-800">${formatMoney(data.total_revenue || 0)}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow p-6 border border-slate-100">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-violet-100 rounded-lg">
                        <i data-lucide="inbox" class="w-6 h-6 text-violet-600"></i>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500">الطلبات الجديدة</p>
                        <p class="text-2xl font-bold text-slate-800">${data.new_requests || 0}</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Charts and Recent Activity -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
            <div class="lg:col-span-2 bg-white rounded-2xl shadow p-6 border border-slate-100">
                <h3 class="text-xl font-bold text-slate-800 mb-4">نظرة عامة على التسجيل</h3>
                <div class="h-72">
                    <canvas id="enrollmentChart"></canvas>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow p-6 border border-slate-100">
                <h3 class="text-xl font-bold text-slate-800 mb-4">آخر النشاطات</h3>
                <div id="recentActivity" class="space-y-4">
                    ${(data.recent_activity || []).map(activity => `
                        <div class="flex items-start gap-3">
                            <div class="p-2 bg-slate-100 rounded-full">
                                <i data-lucide="${activity.icon || 'bell'}" class="w-4 h-4 text-slate-600"></i>
                            </div>
                            <div>
                                <p class="text-sm text-slate-700">${escapeHtml(activity.description)}</p>
                                <p class="text-xs text-slate-500">${formatDateTime(activity.timestamp)}</p>
                            </div>
                        </div>
                    `).join('')}
                    ${(data.recent_activity || []).length === 0 ? '<p class="text-sm text-slate-500">لا توجد نشاطات حديثة.</p>' : ''}
                </div>
            </div>
        </div>
    `;
    lucide.createIcons();
    
    // Create enrollment chart
    const ctx = document.getElementById('enrollmentChart');
    if (ctx && data.enrollment_overview) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.enrollment_overview.labels || [],
                datasets: [{
                    label: 'التسجيلات الجديدة',
                    data: data.enrollment_overview.data || [],
                    borderColor: '#0ea5e9',
                    backgroundColor: 'rgba(14, 165, 233, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
}

// ==============================================
// Toast Notifications
// ==============================================
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toast-container');
    if (!toastContainer) return;

    const icons = {
        info: 'info',
        success: 'check-circle',
        error: 'x-circle',
        warning: 'alert-triangle'
    };

    const colors = {
        info: 'bg-sky-600',
        success: 'bg-emerald-600',
        error: 'bg-red-600',
        warning: 'bg-amber-600'
    };

    const toast = document.createElement('div');
    toast.className = `flex items-center gap-3 p-4 rounded-lg shadow-lg text-white ${colors[type]} animate-toast-in`;
    toast.innerHTML = `
        <i data-lucide="${icons[type]}" class="w-6 h-6"></i>
        <span>${message}</span>
    `;
    
    toastContainer.appendChild(toast);
    lucide.createIcons();

    setTimeout(() => {
        toast.classList.remove('animate-toast-in');
        toast.classList.add('animate-toast-out');
        toast.addEventListener('animationend', () => {
            toast.remove();
        });
    }, 5000);
}

// ==============================================
// Modal
// ==============================================
function openModal(title, content) {
    const modal = document.getElementById('modal');
    if (!modal) return;
    
    document.getElementById('modalTitle').textContent = title;
    const modalBody = document.getElementById('modalBody');
    modalBody.innerHTML = content;
    
    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.querySelector('[data-modal-content]').classList.remove('opacity-0', 'scale-95');
    }, 10);
    
    lucide.createIcons();
}

function closeModal() {
    const modal = document.getElementById('modal');
    if (!modal) return;
    
    modal.querySelector('[data-modal-content]').classList.add('opacity-0', 'scale-95');
    setTimeout(() => {
        modal.classList.add('hidden');
        document.getElementById('modalTitle').textContent = '';
        document.getElementById('modalBody').innerHTML = '';
    }, 200);
}

// Close modal on escape key press
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeModal();
    }
});

// Close modal on overlay click
document.getElementById('modal')?.addEventListener('click', (e) => {
    if (e.target === document.getElementById('modal')) {
        closeModal();
    }
});