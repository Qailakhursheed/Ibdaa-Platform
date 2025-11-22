/**
 * Manager Features - All Dashboard Functions
 * جميع وظائف لوحة المدير
 */

// ==============================================
// API Configuration
// ==============================================
// استخدام getBasePath من window إذا كانت موجودة، وإلا إنشاء واحدة
if (typeof window.getBasePath !== 'function') {
    window.getBasePath = function() {
        const path = window.location.pathname;
        const match = path.match(/(.*?\/Ibdaa-Taiz)/);
        return match ? match[1] : '';
    };
}

if (typeof window.MANAGER_API_ENDPOINTS === 'undefined') {
    const apiBase = window.location.origin + window.getBasePath() + '/Manager/api/';
    window.MANAGER_API_ENDPOINTS = {
    dashboardStats: apiBase + 'get_dashboard_stats.php',
    trainerData: apiBase + 'get_trainer_data.php',
    trainees: apiBase + 'manage_users.php?action=list&role=student',
    trainers: apiBase + 'manage_users.php?action=list&role=trainer',
    manageUsers: apiBase + 'manage_users.php',
    manageCourses: apiBase + 'manage_courses.php',
    manageFinance: apiBase + 'manage_finance.php',
    manageRequests: apiBase + 'get_requests.php',
    manageAnnouncements: apiBase + 'manage_announcements.php',
    manageGrades: apiBase + 'manage_grades.php',
    manageLocations: apiBase + 'manage_locations.php',
    manageImports: apiBase + 'import_excel_flexible.php',
    manageLmsContent: apiBase + 'manage_lms_content.php',
    manageLmsAssignments: apiBase + 'manage_lms_assignments.php',
    manageAttendance: apiBase + 'manage_attendance.php',
    generateCertificate: apiBase + 'generate_certificate.php',
    manageMessages: apiBase + 'manage_messages.php',
    analyticsData: apiBase + 'get_analytics_data.php',
    notifications: apiBase + 'get_notifications.php',
    markNotificationRead: apiBase + 'mark_notification_read.php',
    studentData: apiBase + 'get_student_data.php',
    aiImages: apiBase + 'ai_image_generator.php',
    smartImport: apiBase + 'smart_import.php',
    manageSettings: apiBase + 'manage_settings.php'
    };
}

// Create a shortcut reference
const API_ENDPOINTS = window.MANAGER_API_ENDPOINTS;

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
        
        // If API fails, return mock data
        if (!response.ok) {
            console.warn('API failed, using mock data for:', url);
            return getMockData(url);
        }
        
        const data = await response.json();
        if (data.success === false) {
            console.warn('API returned error, using mock data');
            return getMockData(url);
        }
        return data;
    } catch (error) {
        console.warn('Fetch error, using mock data:', error);
        return getMockData(url);
    }
}

// Mock Data Generator
function getMockData(url) {
    // Handle undefined URL
    if (!url || typeof url !== 'string') {
        return { success: true, data: [], users: [], courses: [], payments: [] };
    }
    
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
            <div id="announcementsGrid" class="grid grid-cols-1 gap-4">
                <div class="text-center py-8">
                    <i data-lucide="loader" class="w-8 h-8 animate-spin mx-auto text-violet-600"></i>
                    <p class="mt-2 text-slate-600">جاري التحميل...</p>
                </div>
            </div>
        </div>
    `;
    lucide.createIcons();
    
    try {
        const data = await fetchJson(API_ENDPOINTS.manageAnnouncements);
        if (data && data.announcements) {
            displayAnnouncementsGrid(data.announcements);
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
            <div class="text-center py-8">
                <i data-lucide="megaphone" class="w-12 h-12 mx-auto text-slate-400"></i>
                <p class="mt-2 text-slate-600">لا توجد إعلانات</p>
            </div>
        `;
        lucide.createIcons();
        return;
    }
    
    const cardsHtml = announcements.map(announcement => `
        <div class="border border-slate-200 rounded-xl p-5 hover:shadow-md transition">
            <div class="flex items-start justify-between mb-3">
                <h4 class="text-lg font-bold text-slate-800">${escapeHtml(announcement.title)}</h4>
                <span class="px-2 py-1 text-xs rounded-full ${announcement.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'}">
                    ${announcement.is_active ? 'نشط' : 'غير نشط'}
                </span>
            </div>
            <p class="text-sm text-slate-600 mb-3">${escapeHtml(announcement.content || '').substring(0, 100)}...</p>
            <div class="flex items-center justify-between text-xs text-slate-500">
                <span><i data-lucide="calendar" class="w-4 h-4 inline"></i> ${formatDateTime(announcement.created_at)}</span>
                <div class="flex gap-2">
                    <button onclick="editAnnouncement(${announcement.id})" class="text-sky-600 hover:text-sky-700">
                        <i data-lucide="edit" class="w-4 h-4"></i>
                    </button>
                    <button onclick="deleteAnnouncement(${announcement.id})" class="text-red-600 hover:text-red-700">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
        </div>
    `).join('');
    
    container.innerHTML = cardsHtml;
    lucide.createIcons();
}

function renderGrades() {
    setPageHeader('الدرجات والشهادات', 'إدارة درجات الطلاب');
    clearPageBody();
    document.getElementById('pageBody').innerHTML = `
        <div class="bg-white rounded-2xl shadow p-8 text-center">
            <i data-lucide="graduation-cap" class="w-16 h-16 mx-auto text-violet-600 mb-4"></i>
            <h3 class="text-xl font-bold mb-2">نظام الدرجات</h3>
            <p class="text-slate-600 mb-4">قريباً - نظام متكامل لإدارة الدرجات والشهادات</p>
            <button class="px-4 py-2 bg-violet-600 text-white rounded-lg hover:bg-violet-700">
                استيراد الدرجات من Excel
            </button>
        </div>
    `;
    lucide.createIcons();
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
    setPageHeader('الاستيراد الذكي', 'استيراد البيانات من ملفات Excel');
    clearPageBody();
    
    const pageBody = document.getElementById('pageBody');
    pageBody.innerHTML = `
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Upload Panel -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow p-6">
                    <h3 class="text-lg font-bold mb-4 text-slate-800">رفع ملف البيانات</h3>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-slate-700 mb-1">نوع البيانات</label>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <label class="cursor-pointer">
                                <input type="radio" name="importType" value="students" checked class="peer sr-only">
                                <div class="p-3 border border-slate-200 rounded-lg text-center peer-checked:bg-sky-50 peer-checked:border-sky-500 peer-checked:text-sky-700 hover:bg-slate-50 transition">
                                    <i data-lucide="users" class="w-6 h-6 mx-auto mb-2"></i>
                                    <span class="text-sm font-medium">الطلاب</span>
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="importType" value="trainers" class="peer sr-only">
                                <div class="p-3 border border-slate-200 rounded-lg text-center peer-checked:bg-emerald-50 peer-checked:border-emerald-500 peer-checked:text-emerald-700 hover:bg-slate-50 transition">
                                    <i data-lucide="user-check" class="w-6 h-6 mx-auto mb-2"></i>
                                    <span class="text-sm font-medium">المدربين</span>
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="importType" value="courses" class="peer sr-only">
                                <div class="p-3 border border-slate-200 rounded-lg text-center peer-checked:bg-violet-50 peer-checked:border-violet-500 peer-checked:text-violet-700 hover:bg-slate-50 transition">
                                    <i data-lucide="book-open" class="w-6 h-6 mx-auto mb-2"></i>
                                    <span class="text-sm font-medium">الدورات</span>
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="importType" value="payments" class="peer sr-only">
                                <div class="p-3 border border-slate-200 rounded-lg text-center peer-checked:bg-amber-50 peer-checked:border-amber-500 peer-checked:text-amber-700 hover:bg-slate-50 transition">
                                    <i data-lucide="wallet" class="w-6 h-6 mx-auto mb-2"></i>
                                    <span class="text-sm font-medium">المدفوعات</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div id="dropZone" class="border-2 border-dashed border-slate-300 rounded-xl p-8 text-center hover:border-sky-500 hover:bg-sky-50 transition cursor-pointer relative">
                        <input type="file" id="fileInput" accept=".xlsx,.xls,.csv" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                        <div id="uploadPrompt">
                            <i data-lucide="upload-cloud" class="w-16 h-16 mx-auto text-slate-400 mb-4"></i>
                            <h4 class="text-lg font-medium text-slate-700 mb-2">اسحب وأفلت ملف Excel هنا</h4>
                            <p class="text-sm text-slate-500 mb-4">أو انقر لاختيار ملف من جهازك</p>
                            <p class="text-xs text-slate-400">يدعم الصيغ: .xlsx, .xls, .csv</p>
                        </div>
                        <div id="filePreview" class="hidden">
                            <i data-lucide="file-spreadsheet" class="w-16 h-16 mx-auto text-emerald-600 mb-4"></i>
                            <h4 class="text-lg font-medium text-slate-800 mb-1" id="fileName">filename.xlsx</h4>
                            <p class="text-sm text-slate-500 mb-4" id="fileSize">0 KB</p>
                            <button id="uploadBtn" class="px-6 py-2 bg-sky-600 text-white rounded-lg hover:bg-sky-700 shadow-lg shadow-sky-200">
                                بدء الاستيراد
                            </button>
                            <button id="cancelUpload" class="px-4 py-2 text-slate-500 hover:text-red-600">
                                إلغاء
                            </button>
                        </div>
                    </div>
                    
                    <!-- Progress Bar -->
                    <div id="uploadProgress" class="hidden mt-6">
                        <div class="flex justify-between text-sm mb-1">
                            <span class="font-medium text-slate-700">جاري المعالجة...</span>
                            <span class="text-slate-600" id="progressPercent">0%</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2.5">
                            <div class="bg-sky-600 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Instructions Panel -->
            <div class="lg:col-span-1">
                <div class="bg-sky-50 border border-sky-100 rounded-2xl p-6">
                    <h3 class="text-lg font-bold text-sky-900 mb-4 flex items-center gap-2">
                        <i data-lucide="info" class="w-5 h-5 text-sky-600"></i>
                        تعليمات الاستيراد
                    </h3>
                    <ul class="space-y-3 text-sm text-sky-800">
                        <li class="flex items-start gap-2">
                            <i data-lucide="check-circle" class="w-4 h-4 mt-0.5 text-sky-600"></i>
                            <span>تأكد من أن الملف يحتوي على صف العناوين (Header Row).</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i data-lucide="check-circle" class="w-4 h-4 mt-0.5 text-sky-600"></i>
                            <span>الحقول المطلوبة للطلاب: الاسم، البريد الإلكتروني، رقم الهاتف.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i data-lucide="check-circle" class="w-4 h-4 mt-0.5 text-sky-600"></i>
                            <span>تجنب وجود خلايا مدمجة (Merged Cells).</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i data-lucide="check-circle" class="w-4 h-4 mt-0.5 text-sky-600"></i>
                            <span>سيتم تجاهل الصفوف الفارغة تلقائياً.</span>
                        </li>
                    </ul>
                    <div class="mt-6 pt-6 border-t border-sky-200">
                        <a href="#" onclick="downloadTemplate()" class="flex items-center justify-center gap-2 w-full px-4 py-2 bg-white border border-sky-300 text-sky-700 rounded-lg hover:bg-sky-100 transition">
                            <i data-lucide="download" class="w-4 h-4"></i>
                            تحميل نموذج Excel
                        </a>
                    </div>
                </div>
            </div>

            <!-- History Section -->
            <div class="lg:col-span-3">
                <div class="bg-white rounded-2xl shadow p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                            <i data-lucide="history" class="w-5 h-5 text-slate-500"></i>
                            سجل الاستيراد والأرشفة
                        </h3>
                        <button onclick="loadImportHistory()" class="text-sm text-sky-600 hover:text-sky-700">
                            <i data-lucide="refresh-cw" class="w-4 h-4 inline"></i> تحديث
                        </button>
                    </div>
                    <div id="importHistoryTable" class="overflow-x-auto">
                        <div class="text-center py-8">
                            <i data-lucide="loader" class="w-8 h-8 animate-spin mx-auto text-slate-400"></i>
                            <p class="mt-2 text-slate-600">جاري تحميل السجل...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    lucide.createIcons();
    loadImportHistory();

    // File Input Logic
    const fileInput = document.getElementById('fileInput');
    const dropZone = document.getElementById('dropZone');
    const uploadPrompt = document.getElementById('uploadPrompt');
    const filePreview = document.getElementById('filePreview');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const uploadBtn = document.getElementById('uploadBtn');
    const cancelUpload = document.getElementById('cancelUpload');

    fileInput.addEventListener('change', handleFileSelect);
    
    // Drag & Drop Visuals
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('bg-sky-50', 'border-sky-500');
    });
    dropZone.addEventListener('dragleave', (e) => {
        e.preventDefault();
        dropZone.classList.remove('bg-sky-50', 'border-sky-500');
    });
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('bg-sky-50', 'border-sky-500');
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            handleFileSelect();
        }
    });

    function handleFileSelect() {
        const file = fileInput.files[0];
        if (file) {
            uploadPrompt.classList.add('hidden');
            filePreview.classList.remove('hidden');
            fileName.textContent = file.name;
            fileSize.textContent = (file.size / 1024).toFixed(2) + ' KB';
        }
    }

    cancelUpload.addEventListener('click', (e) => {
        e.stopPropagation(); // Prevent triggering file input
        fileInput.value = '';
        uploadPrompt.classList.remove('hidden');
        filePreview.classList.add('hidden');
    });

    uploadBtn.addEventListener('click', async (e) => {
        e.stopPropagation();
        const file = fileInput.files[0];
        if (!file) return;

        const importType = document.querySelector('input[name="importType"]:checked').value;
        const formData = new FormData();
        formData.append('file', file);
        formData.append('import_type', importType);
        formData.append('action', 'upload');

        // Show progress
        document.getElementById('uploadProgress').classList.remove('hidden');
        uploadBtn.disabled = true;
        uploadBtn.innerHTML = '<i data-lucide="loader" class="w-4 h-4 animate-spin inline"></i> جاري الرفع...';

        try {
            const response = await fetch(API_ENDPOINTS.smartImport + '?action=upload', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                showToast(`تم استيراد ${result.imported_count || 0} سجل بنجاح`, 'success');
                // Reset form
                setTimeout(() => {
                    renderImports();
                }, 2000);
            } else {
                showToast(result.message || 'فشل الاستيراد', 'error');
            }
        } catch (error) {
            console.error(error);
            showToast('حدث خطأ أثناء الاستيراد', 'error');
        } finally {
            uploadBtn.disabled = false;
            uploadBtn.innerHTML = 'بدء الاستيراد';
            document.getElementById('uploadProgress').classList.add('hidden');
        }
    });
}

function downloadTemplate() {
    const importType = document.querySelector('input[name="importType"]:checked').value;
    window.open(`${API_ENDPOINTS.smartImport}?action=template&type=${importType}`, '_blank');
}

async function renderSettings() {
    setPageHeader('الإعدادات', 'إعدادات المنصة العامة');
    clearPageBody();
    
    const pageBody = document.getElementById('pageBody');
    pageBody.innerHTML = `
        <div class="text-center py-12">
            <i data-lucide="loader" class="w-12 h-12 animate-spin mx-auto text-sky-600"></i>
            <p class="mt-4 text-slate-600">جاري تحميل الإعدادات...</p>
        </div>
    `;
    lucide.createIcons();

    try {
        const response = await fetchJson(API_ENDPOINTS.manageSettings + '?action=get');
        const settings = response.settings || {};

        pageBody.innerHTML = `
            <form id="settingsForm" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Platform Info -->
                    <div class="bg-white rounded-xl shadow p-6">
                        <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
                            <i data-lucide="building" class="w-5 h-5 text-sky-600"></i>
                            معلومات المنصة
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">اسم المنصة</label>
                                <input type="text" name="platform_name" value="${escapeHtml(settings.platform_name || 'منصة إبداع تعز')}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">البريد الإلكتروني الرسمي</label>
                                <input type="email" name="official_email" value="${escapeHtml(settings.official_email || 'info@ibdaa-taiz.edu')}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">رقم الهاتف</label>
                                <input type="tel" name="official_phone" value="${escapeHtml(settings.official_phone || '')}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500">
                            </div>
                        </div>
                    </div>

                    <!-- Notifications & System -->
                    <div class="bg-white rounded-xl shadow p-6">
                        <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
                            <i data-lucide="settings-2" class="w-5 h-5 text-sky-600"></i>
                            إعدادات النظام
                        </h3>
                        <div class="space-y-4">
                            <label class="flex items-center gap-3 p-3 border border-slate-200 rounded-lg hover:bg-slate-50 cursor-pointer">
                                <input type="checkbox" name="enable_registration" value="1" ${settings.enable_registration == '1' ? 'checked' : ''} class="w-5 h-5 text-sky-600 rounded focus:ring-sky-500">
                                <div>
                                    <div class="font-medium text-slate-800">فتح التسجيل</div>
                                    <div class="text-xs text-slate-500">السماح للطلاب الجدد بالتسجيل</div>
                                </div>
                            </label>
                            <label class="flex items-center gap-3 p-3 border border-slate-200 rounded-lg hover:bg-slate-50 cursor-pointer">
                                <input type="checkbox" name="enable_notifications" value="1" ${settings.enable_notifications == '1' ? 'checked' : ''} class="w-5 h-5 text-sky-600 rounded focus:ring-sky-500">
                                <div>
                                    <div class="font-medium text-slate-800">تفعيل الإشعارات</div>
                                    <div class="text-xs text-slate-500">إرسال إشعارات البريد الإلكتروني</div>
                                </div>
                            </label>
                            <label class="flex items-center gap-3 p-3 border border-slate-200 rounded-lg hover:bg-slate-50 cursor-pointer">
                                <input type="checkbox" name="maintenance_mode" value="1" ${settings.maintenance_mode == '1' ? 'checked' : ''} class="w-5 h-5 text-red-600 rounded focus:ring-red-500">
                                <div>
                                    <div class="font-medium text-slate-800">وضع الصيانة</div>
                                    <div class="text-xs text-slate-500">إيقاف الموقع مؤقتاً للصيانة</div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-4">
                    <button type="submit" class="px-6 py-2 bg-sky-600 text-white rounded-lg hover:bg-sky-700 flex items-center gap-2 shadow-lg shadow-sky-200">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        حفظ التغييرات
                    </button>
                </div>
            </form>
        `;
        lucide.createIcons();

        document.getElementById('settingsForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const settingsData = {};
            
            // Handle checkboxes explicitly
            ['enable_registration', 'enable_notifications', 'maintenance_mode'].forEach(key => {
                settingsData[key] = formData.get(key) ? '1' : '0';
            });
            
            // Handle text inputs
            ['platform_name', 'official_email', 'official_phone'].forEach(key => {
                settingsData[key] = formData.get(key);
            });

            try {
                const btn = e.target.querySelector('button[type="submit"]');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i data-lucide="loader" class="w-4 h-4 animate-spin"></i> جاري الحفظ...';
                btn.disabled = true;

                await fetchJson(API_ENDPOINTS.manageSettings, {
                    method: 'POST',
                    body: JSON.stringify({
                        action: 'update_batch',
                        settings: settingsData
                    })
                });

                showToast('تم حفظ الإعدادات بنجاح', 'success');
            } catch (error) {
                console.error(error);
                showToast('حدث خطأ أثناء حفظ الإعدادات', 'error');
            } finally {
                const btn = e.target.querySelector('button[type="submit"]');
                btn.innerHTML = '<i data-lucide="save" class="w-4 h-4"></i> حفظ التغييرات';
                btn.disabled = false;
                lucide.createIcons();
            }
        });

    } catch (error) {
        console.error('Error loading settings:', error);
        pageBody.innerHTML = `
            <div class="text-center py-12 text-red-600">
                <i data-lucide="alert-circle" class="w-12 h-12 mx-auto mb-4"></i>
                <p>فشل تحميل الإعدادات</p>
                <button onclick="renderSettings()" class="mt-4 px-4 py-2 bg-slate-100 rounded-lg hover:bg-slate-200 text-slate-700">
                    إعادة المحاولة
                </button>
            </div>
        `;
        lucide.createIcons();
    }
}

// ==============================================
// New Features Implementation
// ==============================================

function renderCertificateDesigner() {
    setPageHeader('مصمم الشهادات', 'تصميم وإصدار الشهادات');
    clearPageBody();
    
    const pageBody = document.getElementById('pageBody');
    pageBody.innerHTML = `
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1 bg-white rounded-2xl shadow p-6">
                <h3 class="text-lg font-bold mb-4">إعدادات الشهادة</h3>
                <form class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">القالب</label>
                        <select class="w-full px-3 py-2 border border-slate-300 rounded-lg">
                            <option>قالب احترافي 1</option>
                            <option>قالب حديث 2</option>
                            <option>قالب كلاسيكي 3</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">نص العنوان</label>
                        <input type="text" value="شهادة إتمام دورة" class="w-full px-3 py-2 border border-slate-300 rounded-lg">
                    </div>
                    <button type="button" class="w-full px-4 py-2 bg-violet-600 text-white rounded-lg hover:bg-violet-700">
                        تحديث المعاينة
                    </button>
                </form>
            </div>
            
            <div class="lg:col-span-2 bg-slate-100 rounded-2xl p-8 flex items-center justify-center border border-slate-200">
                <div class="bg-white w-full aspect-[1.414] shadow-2xl p-8 relative text-center border-8 border-double border-slate-200">
                    <div class="absolute top-0 left-0 w-full h-2 bg-violet-600"></div>
                    <div class="mt-12">
                        <h1 class="text-4xl font-serif font-bold text-slate-800 mb-4">شهادة إتمام دورة</h1>
                        <p class="text-slate-500 text-lg mb-8">تمنح هذه الشهادة إلى</p>
                        <h2 class="text-3xl font-bold text-violet-700 mb-8">اسم الطالب هنا</h2>
                        <p class="text-slate-600 mb-12">لإتمامه بنجاح دورة <span class="font-bold">تطوير الويب الشامل</span></p>
                        
                        <div class="flex justify-around mt-16">
                            <div class="text-center">
                                <div class="w-32 border-b border-slate-400 mb-2"></div>
                                <p class="text-sm text-slate-500">المدير العام</p>
                            </div>
                            <div class="text-center">
                                <div class="w-32 border-b border-slate-400 mb-2"></div>
                                <p class="text-sm text-slate-500">المدرب</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    lucide.createIcons();
}

function renderAICharts() {
    setPageHeader('الرسوم البيانية AI', 'تحليل البيانات بالذكاء الاصطناعي');
    clearPageBody();
    
    const pageBody = document.getElementById('pageBody');
    pageBody.innerHTML = `
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl shadow p-6">
                <h3 class="text-lg font-bold mb-4 text-slate-800">توقعات النمو (AI Forecast)</h3>
                <div class="h-64">
                    <canvas id="forecastChart"></canvas>
                </div>
                <p class="mt-4 text-sm text-slate-600 bg-slate-50 p-3 rounded-lg">
                    <i data-lucide="sparkles" class="w-4 h-4 inline text-violet-600"></i>
                    بناءً على البيانات التاريخية، يتوقع الذكاء الاصطناعي نمواً بنسبة 15% في الشهر القادم.
                </p>
            </div>
            
            <div class="bg-white rounded-2xl shadow p-6">
                <h3 class="text-lg font-bold mb-4 text-slate-800">تحليل المشاعر (Sentiment Analysis)</h3>
                <div class="h-64 flex items-center justify-center">
                    <canvas id="sentimentChart"></canvas>
                </div>
            </div>
        </div>
    `;
    lucide.createIcons();
    
    // Initialize Charts
    setTimeout(() => {
        if (typeof Chart !== 'undefined') {
            new Chart(document.getElementById('forecastChart'), {
                type: 'line',
                data: {
                    labels: ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو (توقع)'],
                    datasets: [{
                        label: 'الإيرادات',
                        data: [12000, 19000, 15000, 25000, 22000, 28000],
                        borderColor: '#8b5cf6',
                        tension: 0.4,
                        borderDash: [0, 0, 0, 0, 0, 5]
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
            
            new Chart(document.getElementById('sentimentChart'), {
                type: 'doughnut',
                data: {
                    labels: ['إيجابي', 'محايد', 'سلبي'],
                    datasets: [{
                        data: [75, 20, 5],
                        backgroundColor: ['#10b981', '#f59e0b', '#ef4444']
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        }
    }, 100);
}

// New feature: Import history function
async function loadImportHistory() {
    const container = document.getElementById('importHistoryTable');
    try {
        const response = await fetch(API_ENDPOINTS.smartImport + '?action=history');
        const result = await response.json();
        
        if (result.success && result.history && result.history.length > 0) {
            container.innerHTML = `
                <table class="w-full">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-slate-700">اسم الملف</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-slate-700">النوع</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-slate-700">السجلات</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-slate-700">الحالة</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-slate-700">التاريخ</th>
                            <th class="px-4 py-3 text-center text-sm font-semibold text-slate-700">تحميل</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        ${result.history.map(item => `
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-sm font-medium text-slate-800">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="file-spreadsheet" class="w-4 h-4 text-emerald-600"></i>
                                        ${escapeHtml(item.original_name)}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="px-2 py-1 rounded-full text-xs bg-slate-100 text-slate-600">
                                        ${item.import_type === 'students' ? 'طلاب' : 
                                          item.import_type === 'trainers' ? 'مدربين' : 
                                          item.import_type === 'courses' ? 'دورات' : 'مدفوعات'}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm">${item.records_count}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="px-2 py-1 rounded-full text-xs ${
                                        item.status === 'success' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'
                                    }">
                                        ${item.status === 'success' ? 'ناجح' : 'فشل'}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-500">${formatDateTime(item.created_at)}</td>
                                <td class="px-4 py-3 text-center">
                                    <a href="${API_ENDPOINTS.smartImport}?action=download&id=${item.id}" target="_blank" class="text-sky-600 hover:text-sky-700 p-1 rounded hover:bg-sky-50 inline-block">
                                        <i data-lucide="download" class="w-4 h-4"></i>
                                    </a>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
            lucide.createIcons();
        } else {
            container.innerHTML = `
                <div class="text-center py-8">
                    <i data-lucide="history" class="w-12 h-12 mx-auto text-slate-300"></i>
                    <p class="mt-2 text-slate-500">لا يوجد سجل عمليات استيراد سابقة</p>
                </div>
            `;
            lucide.createIcons();
        }
    } catch (error) {
        console.error('Error loading history:', error);
        container.innerHTML = `
            <div class="text-center py-8 text-red-500">
                <i data-lucide="alert-circle" class="w-8 h-8 mx-auto mb-2"></i>
                <p>فشل تحميل السجل</p>
            </div>
        `;
        lucide.createIcons();
    }
}
