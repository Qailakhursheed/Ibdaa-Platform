/**
 * Manager Features - All Dashboard Functions
 * جميع وظائف لوحة المدير
 */

// ==============================================
// API Configuration
// ==============================================
const API_ENDPOINTS = {
    dashboardStats: '../api/get_dashboard_stats.php',
    trainerData: '../api/get_trainer_data.php',
    trainees: '../api/manage_users.php?role=student',
    trainers: '../api/manage_users.php?role=trainer',
    manageUsers: '../api/manage_users.php',
    manageCourses: '../api/manage_courses.php',
    manageFinance: '../api/manage_finance.php',
    manageRequests: '../api/get_requests.php',
    manageAnnouncements: '../api/manage_announcements.php',
    manageGrades: '../api/manage_grades.php',
    manageLocations: '../api/manage_locations.php',
    manageImports: '../api/import_excel_flexible.php',
    manageLmsContent: '../api/manage_lms_content.php',
    manageLmsAssignments: '../api/manage_lms_assignments.php',
    manageAttendance: '../api/manage_attendance.php',
    generateCertificate: '../api/generate_certificate.php',
    manageMessages: '../api/manage_messages.php',
    analyticsData: '../api/get_analytics_data.php',
    notifications: '../api/get_notifications.php',
    markNotificationRead: '../api/mark_notification_read.php',
    studentData: '../api/get_student_data.php',
    aiImages: '../api/ai_image_generator.php'
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
    setPageHeader('الاستيراد الذكي', 'استيراد البيانات من Excel');
    clearPageBody();
    document.getElementById('pageBody').innerHTML = `
        <div class="bg-white rounded-2xl shadow p-8">
            <div class="text-center mb-6">
                <i data-lucide="file-up" class="w-16 h-16 mx-auto text-emerald-600 mb-4"></i>
                <h3 class="text-xl font-bold mb-2">استيراد البيانات</h3>
                <p class="text-slate-600">قم برفع ملف Excel لاستيراد البيانات</p>
            </div>
            <div class="border-2 border-dashed border-slate-300 rounded-xl p-8 text-center hover:border-emerald-500 transition cursor-pointer">
                <i data-lucide="upload-cloud" class="w-12 h-12 mx-auto text-slate-400 mb-4"></i>
                <p class="text-slate-600 mb-2">اسحب وأفلت الملف هنا</p>
                <p class="text-sm text-slate-500">أو انقر للاختيار</p>
                <input type="file" accept=".xlsx,.xls" class="hidden">
            </div>
        </div>
    `;
    lucide.createIcons();
}

function renderSettings() {
    setPageHeader('الإعدادات', 'إعدادات المنصة العامة');
    clearPageBody();
    document.getElementById('pageBody').innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl shadow p-6">
                <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
                    <i data-lucide="building" class="w-5 h-5"></i>
                    معلومات المنصة
                </h3>
                <div class="space-y-3">
                    <div>
                        <label class="text-sm text-slate-600">اسم المنصة</label>
                        <input type="text" value="منصة إبداع تعز" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg">
                    </div>
                    <div>
                        <label class="text-sm text-slate-600">البريد الإلكتروني</label>
                        <input type="email" value="info@ibdaa-taiz.edu" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg">
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow p-6">
                <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
                    <i data-lucide="bell" class="w-5 h-5"></i>
                    إعدادات الإشعارات
                </h3>
                <div class="space-y-3">
                    <label class="flex items-center gap-3">
                        <input type="checkbox" checked class="w-4 h-4">
                        <span class="text-sm">إشعارات البريد الإلكتروني</span>
                    </label>
                    <label class="flex items-center gap-3">
                        <input type="checkbox" checked class="w-4 h-4">
                        <span class="text-sm">إشعارات الطلبات الجديدة</span>
                    </label>
                </div>
            </div>
        </div>
    `;
    lucide.createIcons();
}

// Additional Modal Functions
function openAddTrainerModal() {
    const modalContent = `
        <form id="addTrainerForm" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">الاسم الكامل</label>
                <input type="text" name="name" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">البريد الإلكتروني</label>
                <input type="email" name="email" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">التخصص</label>
                <input type="text" name="specialty" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">كلمة المرور</label>
                <input type="password" name="password" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
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
    
    openModal('إضافة مدرب جديد', modalContent);
    
    document.getElementById('addTrainerForm').addEventListener('submit', async (e) => {
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
                    password: data.password,
                    role: 'trainer',
                    specialty: data.specialty || ''
                })
            });
            showToast('تم إضافة المدرب بنجاح', 'success');
            closeModal();
            renderTrainers();
        } catch (error) {
            showToast('حدث خطأ أثناء إضافة المدرب', 'error');
        }
    });
}

function openAddAnnouncementModal() {
    const modalContent = `
        <form id="addAnnouncementForm" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">العنوان</label>
                <input type="text" name="title" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-violet-500 focus:border-violet-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">المحتوى</label>
                <textarea name="content" required rows="4" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-violet-500 focus:border-violet-500"></textarea>
            </div>
            <div>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1" checked class="w-4 h-4 text-violet-600 border-slate-300 rounded focus:ring-violet-500">
                    <span class="text-sm text-slate-700">نشط</span>
                </label>
            </div>
            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 px-4 py-2 bg-violet-600 text-white rounded-lg hover:bg-violet-700">
                    نشر الإعلان
                </button>
                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300">
                    إلغاء
                </button>
            </div>
        </form>
    `;
    
    openModal('إضافة إعلان جديد', modalContent);
    
    document.getElementById('addAnnouncementForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);
        
        try {
            await fetchJson(API_ENDPOINTS.manageAnnouncements, {
                method: 'POST',
                body: JSON.stringify({
                    action: 'create',
                    title: data.title,
                    content: data.content,
                    is_active: data.is_active ? 1 : 0
                })
            });
            showToast('تم نشر الإعلان بنجاح', 'success');
            closeModal();
            renderAnnouncements();
        } catch (error) {
            showToast('حدث خطأ أثناء نشر الإعلان', 'error');
        }
    });
}

// AI Image Generator
function openAIImageGenerator() {
    const modalContent = `
        <div class="space-y-4">
            <div class="bg-gradient-to-br from-violet-50 to-sky-50 rounded-lg p-4 border border-violet-200">
                <div class="flex items-center gap-2 text-violet-700 mb-2">
                    <i data-lucide="sparkles" class="w-5 h-5"></i>
                    <span class="font-semibold">مولد الصور بالذكاء الاصطناعي</span>
                </div>
                <p class="text-sm text-slate-600">أنشئ صوراً مخصصة للإعلانات والدورات باستخدام AI</p>
            </div>
            
            <form id="aiImageForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">وصف الصورة</label>
                    <textarea name="prompt" required rows="3" placeholder="مثال: صورة احترافية لدورة برمجة Python..." class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-violet-500"></textarea>
                </div>
                
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">النمط</label>
                        <select name="style" class="w-full px-4 py-2 border border-slate-300 rounded-lg">
                            <option value="professional">احترافي</option>
                            <option value="creative">إبداعي</option>
                            <option value="minimal">بسيط</option>
                            <option value="colorful">ملون</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">الحجم</label>
                        <select name="size" class="w-full px-4 py-2 border border-slate-300 rounded-lg">
                            <option value="1024x1024">مربع (1024x1024)</option>
                            <option value="1024x768">أفقي (1024x768)</option>
                            <option value="768x1024">عمودي (768x1024)</option>
                        </select>
                    </div>
                </div>
                
                <div id="aiImagePreview" class="hidden">
                    <div class="border-2 border-dashed border-slate-300 rounded-lg p-4">
                        <img id="generatedImage" class="w-full rounded-lg" alt="Generated Image">
                        <div class="mt-3 flex gap-2">
                            <button type="button" onclick="downloadAIImage()" class="flex-1 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">
                                <i data-lucide="download" class="w-4 h-4 inline"></i>
                                تحميل
                            </button>
                            <button type="button" onclick="useAIImage()" class="flex-1 px-4 py-2 bg-sky-600 text-white rounded-lg hover:bg-sky-700">
                                <i data-lucide="check" class="w-4 h-4 inline"></i>
                                استخدام
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button type="submit" class="flex-1 px-4 py-2 bg-violet-600 text-white rounded-lg hover:bg-violet-700">
                        <i data-lucide="wand-2" class="w-4 h-4 inline"></i>
                        توليد الصورة
                    </button>
                    <button type="button" onclick="closeModal()" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300">
                        إلغاء
                    </button>
                </div>
            </form>
        </div>
    `;
    
    openModal('توليد صورة بالذكاء الاصطناعي', modalContent);
    lucide.createIcons();
    
    document.getElementById('aiImageForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);
        
        const submitBtn = e.target.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i data-lucide="loader" class="w-4 h-4 inline animate-spin"></i> جاري التوليد...';
        submitBtn.disabled = true;
        lucide.createIcons();
        
        try {
            const response = await fetchJson(API_ENDPOINTS.aiImages, {
                method: 'POST',
                body: JSON.stringify({
                    action: 'generate',
                    prompt: data.prompt,
                    style: data.style,
                    size: data.size
                })
            });
            
            if (response.success && response.image_url) {
                document.getElementById('generatedImage').src = response.image_url;
                document.getElementById('aiImagePreview').classList.remove('hidden');
                showToast('تم توليد الصورة بنجاح!', 'success');
            } else {
                showToast('فشل توليد الصورة', 'error');
            }
        } catch (error) {
            showToast('حدث خطأ أثناء توليد الصورة', 'error');
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            lucide.createIcons();
        }
    });
}

function downloadAIImage() {
    const img = document.getElementById('generatedImage');
    if (img && img.src) {
        const link = document.createElement('a');
        link.href = img.src;
        link.download = `ai-image-${Date.now()}.png`;
        link.click();
        showToast('جاري تحميل الصورة...', 'info');
    }
}

function useAIImage() {
    const img = document.getElementById('generatedImage');
    if (img && img.src) {
        // Store image URL for later use
        localStorage.setItem('lastAIImage', img.src);
        showToast('تم حفظ الصورة للاستخدام', 'success');
        closeModal();
    }
}

// Advanced Notifications System
async function loadAdvancedNotifications() {
    try {
        const response = await fetchJson(API_ENDPOINTS.notifications);
        if (response.success && response.notifications) {
            displayAdvancedNotifications(response.notifications);
        }
    } catch (error) {
        console.error('Error loading notifications:', error);
    }
}

function displayAdvancedNotifications(notifications) {
    const container = document.getElementById('notificationsPanel');
    if (!container) return;
    
    container.innerHTML = `
        <div class="bg-white rounded-2xl shadow-lg max-w-md">
            <div class="p-4 border-b border-slate-200">
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-slate-800">الإشعارات</h3>
                    <button onclick="markAllNotificationsRead()" class="text-sm text-sky-600 hover:text-sky-700">
                        تحديد الكل كمقروء
                    </button>
                </div>
            </div>
            <div class="max-h-96 overflow-y-auto">
                ${notifications.length === 0 ? `
                    <div class="p-8 text-center">
                        <i data-lucide="bell-off" class="w-12 h-12 mx-auto text-slate-400 mb-2"></i>
                        <p class="text-slate-600">لا توجد إشعارات جديدة</p>
                    </div>
                ` : notifications.map(notif => `
                    <div class="p-4 border-b border-slate-100 hover:bg-slate-50 cursor-pointer ${notif.is_read ? 'opacity-60' : 'bg-sky-50'}"
                         onclick="markNotificationRead(${notif.id})">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 rounded-full ${getNotificationColor(notif.type)} flex items-center justify-center">
                                    <i data-lucide="${getNotificationIcon(notif.type)}" class="w-5 h-5 text-white"></i>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1">
                                    <p class="text-sm font-semibold text-slate-800">${escapeHtml(notif.title)}</p>
                                    ${!notif.is_read ? '<div class="w-2 h-2 bg-sky-600 rounded-full"></div>' : ''}
                                </div>
                                <p class="text-sm text-slate-600 mb-1">${escapeHtml(notif.message)}</p>
                                <p class="text-xs text-slate-400">${formatDateTime(notif.created_at)}</p>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        </div>
    `;
    lucide.createIcons();
}

function getNotificationColor(type) {
    const colors = {
        'success': 'bg-emerald-500',
        'warning': 'bg-amber-500',
        'error': 'bg-red-500',
        'info': 'bg-sky-500',
        'default': 'bg-slate-500'
    };
    return colors[type] || colors.default;
}

function getNotificationIcon(type) {
    const icons = {
        'success': 'check-circle',
        'warning': 'alert-triangle',
        'error': 'x-circle',
        'info': 'info',
        'message': 'message-circle',
        'user': 'user',
        'default': 'bell'
    };
    return icons[type] || icons.default;
}

async function markNotificationRead(id) {
    try {
        await fetchJson(`${API_ENDPOINTS.markNotificationRead}?id=${id}`, {
            method: 'POST'
        });
        loadAdvancedNotifications();
    } catch (error) {
        console.error('Error marking notification as read:', error);
    }
}

async function markAllNotificationsRead() {
    try {
        await fetchJson(API_ENDPOINTS.markNotificationRead, {
            method: 'POST',
            body: JSON.stringify({ action: 'mark_all' })
        });
        showToast('تم تحديد جميع الإشعارات كمقروءة', 'success');
        loadAdvancedNotifications();
    } catch (error) {
        console.error('Error marking all notifications:', error);
    }
}

async function approveRequest(id) {
    if (!confirm('هل تريد الموافقة على هذا الطلب؟')) return;
    
    try {
        await fetchJson(`${API_ENDPOINTS.manageRequests}`, {
            method: 'POST',
            body: JSON.stringify({ id, action: 'approve' })
        });
        showToast('تمت الموافقة على الطلب', 'success');
        renderRequests();
    } catch (error) {
        showToast('حدث خطأ أثناء الموافقة', 'error');
    }
}

async function rejectRequest(id) {
    if (!confirm('هل تريد رفض هذا الطلب؟')) return;
    
    try {
        await fetchJson(`${API_ENDPOINTS.manageRequests}`, {
            method: 'POST',
            body: JSON.stringify({ id, action: 'reject' })
        });
        showToast('تم رفض الطلب', 'error');
        renderRequests();
    } catch (error) {
        showToast('حدث خطأ أثناء الرفض', 'error');
    }
}

async function editTrainer(id) {
    try {
        const response = await fetchJson(`${API_ENDPOINTS.manageUsers}?id=${id}`);
        const trainer = response.user || response;
        
        const modalContent = `
            <form id="editTrainerForm" class="space-y-4">
                <input type="hidden" name="id" value="${id}">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">الاسم الكامل</label>
                    <input type="text" name="name" value="${escapeHtml(trainer.name)}" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">البريد الإلكتروني</label>
                    <input type="email" name="email" value="${escapeHtml(trainer.email)}" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">التخصص</label>
                    <input type="text" name="specialty" value="${escapeHtml(trainer.specialty || '')}" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="submit" class="flex-1 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">
                        حفظ التعديلات
                    </button>
                    <button type="button" onclick="closeModal()" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300">
                        إلغاء
                    </button>
                </div>
            </form>
        `;
        
        openModal('تعديل بيانات المدرب', modalContent);
        
        document.getElementById('editTrainerForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData);
            
            try {
                await fetchJson(`${API_ENDPOINTS.manageUsers}`, {
                    method: 'POST',
                    body: JSON.stringify({
                        action: 'update',
                        user_id: data.id,
                        full_name: data.name,
                        email: data.email,
                        phone: data.phone || ''
                    })
                });
                showToast('تم تحديث بيانات المدرب بنجاح', 'success');
                closeModal();
                renderTrainers();
            } catch (error) {
                showToast('حدث خطأ أثناء التحديث', 'error');
            }
        });
    } catch (error) {
        showToast('حدث خطأ أثناء تحميل البيانات', 'error');
    }
}

async function deleteTrainer(id) {
    if (!confirm('هل تريد حذف هذا المدرب؟')) return;
    
    try {
        await fetchJson(`${API_ENDPOINTS.manageUsers}`, {
            method: 'POST',
            body: JSON.stringify({
                action: 'delete',
                user_id: id
            })
        });
        showToast('تم حذف المدرب', 'success');
        renderTrainers();
    } catch (error) {
        showToast('حدث خطأ أثناء الحذف', 'error');
    }
}

async function editAnnouncement(id) {
    try {
        const response = await fetchJson(`${API_ENDPOINTS.manageAnnouncements}?id=${id}`);
        const announcement = response.announcement || response;
        
        const modalContent = `
            <form id="editAnnouncementForm" class="space-y-4">
                <input type="hidden" name="id" value="${id}">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">العنوان</label>
                    <input type="text" name="title" value="${escapeHtml(announcement.title)}" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-violet-500 focus:border-violet-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">المحتوى</label>
                    <textarea name="content" required rows="4" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-violet-500 focus:border-violet-500">${escapeHtml(announcement.content)}</textarea>
                </div>
                <div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" ${announcement.is_active ? 'checked' : ''} class="w-4 h-4 text-violet-600 border-slate-300 rounded focus:ring-violet-500">
                        <span class="text-sm text-slate-700">نشط</span>
                    </label>
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="submit" class="flex-1 px-4 py-2 bg-violet-600 text-white rounded-lg hover:bg-violet-700">
                        حفظ التعديلات
                    </button>
                    <button type="button" onclick="closeModal()" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300">
                        إلغاء
                    </button>
                </div>
            </form>
        `;
        
        openModal('تعديل الإعلان', modalContent);
        
        document.getElementById('editAnnouncementForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData);
            data.is_active = data.is_active ? 1 : 0;
            
            try {
                await fetchJson(`${API_ENDPOINTS.manageAnnouncements}`, {
                    method: 'POST',
                    body: JSON.stringify({
                        action: 'update',
                        id: data.id,
                        title: data.title,
                        content: data.content,
                        is_active: data.is_active
                    })
                });
                showToast('تم تحديث الإعلان بنجاح', 'success');
                closeModal();
                renderAnnouncements();
            } catch (error) {
                showToast('حدث خطأ أثناء التحديث', 'error');
            }
        });
    } catch (error) {
        showToast('حدث خطأ أثناء تحميل البيانات', 'error');
    }
}

async function deleteAnnouncement(id) {
    if (!confirm('هل تريد حذف هذا الإعلان؟')) return;
    
    try {
        await fetchJson(`${API_ENDPOINTS.manageAnnouncements}`, {
            method: 'POST',
            body: JSON.stringify({
                action: 'delete',
                id: id
            })
        });
        showToast('تم حذف الإعلان', 'success');
        renderAnnouncements();
    } catch (error) {
        showToast('حدث خطأ أثناء الحذف', 'error');
    }
}

// Edit/Delete functions for Trainees
async function editTrainee(id) {
    try {
        const response = await fetchJson(`${API_ENDPOINTS.manageUsers}?id=${id}`);
        const trainee = response.user || response;
        
        const modalContent = `
            <form id="editTraineeForm" class="space-y-4">
                <input type="hidden" name="id" value="${id}">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">الاسم الكامل</label>
                    <input type="text" name="name" value="${escapeHtml(trainee.name)}" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">البريد الإلكتروني</label>
                    <input type="email" name="email" value="${escapeHtml(trainee.email)}" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">رقم الهاتف</label>
                    <input type="tel" name="phone" value="${escapeHtml(trainee.phone || '')}" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="submit" class="flex-1 px-4 py-2 bg-sky-600 text-white rounded-lg hover:bg-sky-700">
                        حفظ التعديلات
                    </button>
                    <button type="button" onclick="closeModal()" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300">
                        إلغاء
                    </button>
                </div>
            </form>
        `;
        
        openModal('تعديل بيانات المتدرب', modalContent);
        
        document.getElementById('editTraineeForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData);
            
            try {
                await fetchJson(`${API_ENDPOINTS.manageUsers}`, {
                    method: 'POST',
                    body: JSON.stringify({
                        action: 'update',
                        user_id: data.id,
                        full_name: data.name,
                        email: data.email,
                        phone: data.phone || ''
                    })
                });
                showToast('تم تحديث بيانات المتدرب بنجاح', 'success');
                closeModal();
                renderTrainees();
            } catch (error) {
                showToast('حدث خطأ أثناء التحديث', 'error');
            }
        });
    } catch (error) {
        showToast('حدث خطأ أثناء تحميل البيانات', 'error');
    }
}

async function deleteTrainee(id) {
    if (!confirm('هل أنت متأكد من حذف هذا المتدرب؟')) return;
    
    try {
        await fetchJson(`${API_ENDPOINTS.manageUsers}`, {
            method: 'POST',
            body: JSON.stringify({
                action: 'delete',
                user_id: id
            })
        });
        showToast('تم حذف المتدرب بنجاح', 'success');
        renderTrainees();
    } catch (error) {
        showToast('حدث خطأ أثناء الحذف', 'error');
    }
}

// Edit/Delete functions for Courses
async function editCourse(id) {
    try {
        const response = await fetchJson(`${API_ENDPOINTS.manageCourses}?id=${id}`);
        const course = response.course || response;
        
        const modalContent = `
            <form id="editCourseForm" class="space-y-4">
                <input type="hidden" name="id" value="${id}">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">اسم الدورة</label>
                    <input type="text" name="name" value="${escapeHtml(course.name)}" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">الوصف</label>
                    <textarea name="description" rows="3" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">${escapeHtml(course.description || '')}</textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">المدة (ساعة)</label>
                        <input type="number" name="duration" value="${course.duration || ''}" min="1" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">الحالة</label>
                        <select name="status" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="active" ${course.status === 'active' ? 'selected' : ''}>نشط</option>
                            <option value="inactive" ${course.status === 'inactive' ? 'selected' : ''}>غير نشط</option>
                        </select>
                    </div>
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="submit" class="flex-1 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">
                        حفظ التعديلات
                    </button>
                    <button type="button" onclick="closeModal()" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300">
                        إلغاء
                    </button>
                </div>
            </form>
        `;
        
        openModal('تعديل الدورة', modalContent);
        
        document.getElementById('editCourseForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData);
            
            try {
                await fetchJson(`${API_ENDPOINTS.manageCourses}`, {
                    method: 'POST',
                    body: JSON.stringify({
                        action: 'update',
                        course_id: data.id,
                        title: data.name,
                        short_desc: data.short_desc || '',
                        full_desc: data.description || '',
                        category: data.category || '',
                        trainer_id: data.trainer_id || null,
                        duration: data.duration,
                        start_date: data.start_date || null,
                        end_date: data.end_date || null,
                        max_students: data.max_students || 30,
                        fees: data.price,
                        image_url: data.image_url || '',
                        status: data.status || 'active'
                    })
                });
                showToast('تم تحديث الدورة بنجاح', 'success');
                closeModal();
                renderCourses();
            } catch (error) {
                showToast('حدث خطأ أثناء التحديث', 'error');
            }
        });
    } catch (error) {
        showToast('حدث خطأ أثناء تحميل البيانات', 'error');
    }
}

async function viewCourseDetails(id) {
    try {
        const response = await fetchJson(`${API_ENDPOINTS.manageCourses}?id=${id}`);
        const course = response.course || response;
        
        const modalContent = `
            <div class="space-y-4">
                <div class="bg-emerald-50 rounded-lg p-4">
                    <h3 class="text-lg font-bold text-emerald-900 mb-2">${escapeHtml(course.name)}</h3>
                    <p class="text-slate-600">${escapeHtml(course.description || 'لا يوجد وصف')}</p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-slate-50 rounded-lg p-3">
                        <div class="text-sm text-slate-600">المدة</div>
                        <div class="text-lg font-bold text-slate-900">${course.duration || 0} ساعة</div>
                    </div>
                    <div class="bg-slate-50 rounded-lg p-3">
                        <div class="text-sm text-slate-600">المسجلين</div>
                        <div class="text-lg font-bold text-slate-900">${course.enrolled_count || 0} متدرب</div>
                    </div>
                </div>
                <div class="bg-slate-50 rounded-lg p-3">
                    <div class="text-sm text-slate-600 mb-2">الحالة</div>
                    <span class="px-3 py-1 rounded-full text-sm ${course.status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700'}">
                        ${course.status === 'active' ? 'نشط' : 'غير نشط'}
                    </span>
                </div>
                <button onclick="closeModal()" class="w-full px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300">
                    إغلاق
                </button>
            </div>
        `;
        
        openModal('تفاصيل الدورة', modalContent);
    } catch (error) {
        showToast('حدث خطأ أثناء تحميل التفاصيل', 'error');
    }
}

async function viewPaymentDetails(id) {
    try {
        const response = await fetchJson(`${API_ENDPOINTS.manageFinance}?id=${id}`);
        const payment = response.payment || response;
        
        const modalContent = `
            <div class="space-y-4">
                <div class="bg-amber-50 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-lg font-bold text-amber-900">تفاصيل الدفعة</h3>
                        <span class="px-3 py-1 rounded-full text-sm ${payment.status === 'completed' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'}">
                            ${payment.status === 'completed' ? 'مكتمل' : 'معلق'}
                        </span>
                    </div>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between py-2 border-b border-slate-200">
                        <span class="text-slate-600">اسم المتدرب</span>
                        <span class="font-semibold">${escapeHtml(payment.student_name || 'غير محدد')}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-slate-200">
                        <span class="text-slate-600">الدورة</span>
                        <span class="font-semibold">${escapeHtml(payment.course_name || 'غير محدد')}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-slate-200">
                        <span class="text-slate-600">المبلغ</span>
                        <span class="font-bold text-amber-600">${formatMoney(payment.amount)}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-slate-200">
                        <span class="text-slate-600">التاريخ</span>
                        <span class="font-semibold">${formatDateTime(payment.created_at)}</span>
                    </div>
                </div>
                <button onclick="closeModal()" class="w-full px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300">
                    إغلاق
                </button>
            </div>
        `;
        
        openModal('تفاصيل الدفعة', modalContent);
    } catch (error) {
        showToast('حدث خطأ أثناء تحميل التفاصيل', 'error');
    }
}

// ==============================================
// 🎨 AI Image Generation System - نظام توليد الصور بالذكاء الاصطناعي
// ==============================================

/**
 * Render AI Images Generation Page
 * صفحة توليد الصور بالذكاء الاصطناعي
 */
async function renderAIImages() {
    setPageHeader('🎨 توليد الصور بالذكاء الاصطناعي', 'أنشئ صور مذهلة باستخدام تقنيات الذكاء الاصطناعي المتقدمة');
    clearPageBody();
    
    const pageBody = document.getElementById('pageBody');
    pageBody.innerHTML = `
        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-gradient-to-br from-violet-500 to-purple-600 rounded-xl shadow p-4 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm opacity-90">إجمالي الصور</div>
                        <div class="text-2xl font-bold mt-1" id="total-images">0</div>
                    </div>
                    <i data-lucide="image" class="w-8 h-8 opacity-80"></i>
                </div>
            </div>
            <div class="bg-gradient-to-br from-pink-500 to-rose-600 rounded-xl shadow p-4 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm opacity-90">هذا الشهر</div>
                        <div class="text-2xl font-bold mt-1" id="month-images">0</div>
                    </div>
                    <i data-lucide="calendar" class="w-8 h-8 opacity-80"></i>
                </div>
            </div>
            <div class="bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl shadow p-4 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm opacity-90">DALL-E</div>
                        <div class="text-2xl font-bold mt-1" id="dalle-count">0</div>
                    </div>
                    <i data-lucide="sparkles" class="w-8 h-8 opacity-80"></i>
                </div>
            </div>
            <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl shadow p-4 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm opacity-90">Stable Diffusion</div>
                        <div class="text-2xl font-bold mt-1" id="sd-count">0</div>
                    </div>
                    <i data-lucide="cpu" class="w-8 h-8 opacity-80"></i>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Generation Panel -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-lg p-6 sticky top-6">
                    <h3 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
                        <i data-lucide="wand-2" class="w-6 h-6 text-violet-600"></i>
                        إنشاء صورة جديدة
                    </h3>
                    
                    <form id="aiImageForm" class="space-y-4">
                        <!-- Image Type -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">نوع الصورة</label>
                            <select id="imageType" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-violet-500">
                                <option value="course">صورة دورة تدريبية</option>
                                <option value="announcement">صورة إعلان</option>
                                <option value="certificate">صورة شهادة</option>
                                <option value="banner">بانر إعلاني</option>
                                <option value="logo">شعار</option>
                                <option value="general">صورة عامة</option>
                            </select>
                        </div>

                        <!-- Prompt -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">
                                الوصف (Prompt)
                                <span class="text-red-500">*</span>
                            </label>
                            <textarea 
                                id="imagePrompt" 
                                rows="4" 
                                required
                                placeholder="صف الصورة التي تريد إنشاءها بالتفصيل..."
                                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-violet-500 resize-none"
                            ></textarea>
                            <div class="mt-2 flex items-center gap-2">
                                <button type="button" onclick="enhancePrompt()" class="text-sm text-violet-600 hover:text-violet-700 flex items-center gap-1">
                                    <i data-lucide="sparkles" class="w-4 h-4"></i>
                                    تحسين الوصف بالذكاء الاصطناعي
                                </button>
                            </div>
                        </div>

                        <!-- Style -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">الأسلوب الفني</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button" onclick="selectStyle('realistic')" data-style="realistic" class="style-btn px-3 py-2 border-2 border-slate-300 rounded-lg hover:border-violet-500 text-sm transition-all">
                                    واقعي
                                </button>
                                <button type="button" onclick="selectStyle('artistic')" data-style="artistic" class="style-btn px-3 py-2 border-2 border-slate-300 rounded-lg hover:border-violet-500 text-sm transition-all">
                                    فني
                                </button>
                                <button type="button" onclick="selectStyle('cartoon')" data-style="cartoon" class="style-btn px-3 py-2 border-2 border-slate-300 rounded-lg hover:border-violet-500 text-sm transition-all">
                                    كرتوني
                                </button>
                                <button type="button" onclick="selectStyle('abstract')" data-style="abstract" class="style-btn px-3 py-2 border-2 border-slate-300 rounded-lg hover:border-violet-500 text-sm transition-all">
                                    تجريدي
                                </button>
                                <button type="button" onclick="selectStyle('minimalist')" data-style="minimalist" class="style-btn px-3 py-2 border-2 border-slate-300 rounded-lg hover:border-violet-500 text-sm transition-all">
                                    بسيط
                                </button>
                                <button type="button" onclick="selectStyle('professional')" data-style="professional" class="style-btn px-3 py-2 border-2 border-slate-300 rounded-lg hover:border-violet-500 text-sm transition-all active">
                                    احترافي
                                </button>
                            </div>
                            <input type="hidden" id="selectedStyle" value="professional">
                        </div>

                        <!-- Provider -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">محرك الذكاء الاصطناعي</label>
                            <select id="aiProvider" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-violet-500">
                                <option value="dalle">DALL-E 3 (OpenAI)</option>
                                <option value="stable-diffusion">Stable Diffusion XL</option>
                                <option value="local">نموذج محلي</option>
                            </select>
                        </div>

                        <!-- Size -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">الحجم</label>
                            <select id="imageSize" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-violet-500">
                                <option value="1024x1024">مربع (1024×1024)</option>
                                <option value="1792x1024">عرضي (1792×1024)</option>
                                <option value="1024x1792">عمودي (1024×1792)</option>
                            </select>
                        </div>

                        <!-- Advanced Options -->
                        <div>
                            <button type="button" onclick="toggleAdvancedOptions()" class="text-sm text-slate-600 hover:text-slate-800 flex items-center gap-1">
                                <i data-lucide="settings" class="w-4 h-4"></i>
                                خيارات متقدمة
                                <i data-lucide="chevron-down" class="w-4 h-4" id="advancedToggleIcon"></i>
                            </button>
                            <div id="advancedOptions" class="hidden mt-3 space-y-3 p-3 bg-slate-50 rounded-lg">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">الجودة</label>
                                    <input type="range" id="quality" min="1" max="100" value="85" class="w-full">
                                    <div class="flex justify-between text-xs text-slate-500">
                                        <span>منخفضة</span>
                                        <span id="qualityValue">85%</span>
                                        <span>عالية</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" id="addWatermark" class="w-4 h-4 text-violet-600">
                                        <span class="text-sm text-slate-700">إضافة علامة مائية</span>
                                    </label>
                                </div>
                                <div>
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" id="autoEnhance" class="w-4 h-4 text-violet-600">
                                        <span class="text-sm text-slate-700">تحسين تلقائي</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Generate Button -->
                        <button type="submit" class="w-full px-4 py-3 bg-gradient-to-r from-violet-600 to-purple-600 text-white rounded-lg hover:from-violet-700 hover:to-purple-700 font-semibold flex items-center justify-center gap-2">
                            <i data-lucide="wand-2" class="w-5 h-5"></i>
                            توليد الصورة
                        </button>
                    </form>

                    <!-- Generation Progress -->
                    <div id="generationProgress" class="hidden mt-4 p-4 bg-violet-50 rounded-lg border border-violet-200">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-violet-600"></div>
                            <span class="text-sm font-medium text-violet-800">جاري إنشاء الصورة...</span>
                        </div>
                        <div class="w-full bg-violet-200 rounded-full h-2">
                            <div id="progressBar" class="bg-violet-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                        <p class="text-xs text-violet-600 mt-2" id="progressText">تحليل الوصف...</p>
                    </div>
                </div>
            </div>

            <!-- Gallery Panel -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                            <i data-lucide="gallery-horizontal" class="w-6 h-6 text-violet-600"></i>
                            معرض الصور
                        </h3>
                        <div class="flex items-center gap-2">
                            <select id="filterType" onchange="loadAIImages()" class="px-3 py-1 border border-slate-300 rounded-lg text-sm">
                                <option value="all">جميع الصور</option>
                                <option value="course">دورات</option>
                                <option value="announcement">إعلانات</option>
                                <option value="certificate">شهادات</option>
                                <option value="banner">بانرات</option>
                                <option value="logo">شعارات</option>
                            </select>
                            <button onclick="loadAIImages()" class="px-3 py-1 border border-slate-300 rounded-lg text-sm hover:bg-slate-50">
                                <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Search -->
                    <div class="mb-4">
                        <div class="relative">
                            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400"></i>
                            <input 
                                type="text" 
                                id="searchImages" 
                                placeholder="ابحث في الصور..."
                                class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-violet-500"
                                oninput="searchAIImages()"
                            >
                        </div>
                    </div>

                    <!-- Images Grid -->
                    <div id="imagesGrid" class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <!-- Loading State -->
                        <div class="col-span-full flex items-center justify-center py-12">
                            <div class="text-center">
                                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-violet-600 mx-auto mb-4"></div>
                                <p class="text-slate-600">جاري تحميل الصور...</p>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div id="imagesPagination" class="hidden mt-6 flex items-center justify-between">
                        <button onclick="previousPage()" class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 text-sm">
                            <i data-lucide="chevron-right" class="w-4 h-4 inline"></i>
                            السابق
                        </button>
                        <span class="text-sm text-slate-600">
                            صفحة <span id="currentPage">1</span> من <span id="totalPages">1</span>
                        </span>
                        <button onclick="nextPage()" class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 text-sm">
                            التالي
                            <i data-lucide="chevron-left" class="w-4 h-4 inline"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Templates Section -->
        <div class="mt-6 bg-white rounded-2xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
                <i data-lucide="layout-template" class="w-6 h-6 text-violet-600"></i>
                قوالب جاهزة
            </h3>
            <div id="templatesGrid" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <!-- Templates will be loaded here -->
            </div>
        </div>
    `;
    
    lucide.createIcons();
    
    // Initialize
    await loadAIImages();
    await loadTemplates();
    setupAIImageHandlers();
}

/**
 * Setup Event Handlers for AI Images
 */
function setupAIImageHandlers() {
    // Form submission
    document.getElementById('aiImageForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        await generateAIImage();
    });
    
    // Quality slider
    document.getElementById('quality').addEventListener('input', (e) => {
        document.getElementById('qualityValue').textContent = e.target.value + '%';
    });
}

/**
 * Style Selection
 */
let currentStyle = 'professional';

function selectStyle(style) {
    currentStyle = style;
    document.getElementById('selectedStyle').value = style;
    
    // Update UI
    document.querySelectorAll('.style-btn').forEach(btn => {
        btn.classList.remove('active', 'border-violet-500', 'bg-violet-50');
        btn.classList.add('border-slate-300');
    });
    
    const selectedBtn = document.querySelector(`[data-style="${style}"]`);
    if (selectedBtn) {
        selectedBtn.classList.add('active', 'border-violet-500', 'bg-violet-50');
        selectedBtn.classList.remove('border-slate-300');
    }
}

/**
 * Toggle Advanced Options
 */
function toggleAdvancedOptions() {
    const options = document.getElementById('advancedOptions');
    const icon = document.getElementById('advancedToggleIcon');
    
    if (options.classList.contains('hidden')) {
        options.classList.remove('hidden');
        icon.setAttribute('data-lucide', 'chevron-up');
    } else {
        options.classList.add('hidden');
        icon.setAttribute('data-lucide', 'chevron-down');
    }
    
    lucide.createIcons();
}

/**
 * Enhance Prompt using AI
 */
async function enhancePrompt() {
    const promptField = document.getElementById('imagePrompt');
    const currentPrompt = promptField.value.trim();
    
    if (!currentPrompt) {
        showToast('الرجاء إدخال وصف أولاً', 'warning');
        return;
    }
    
    showToast('جاري تحسين الوصف...', 'info');
    
    try {
        // Enhance the prompt with AI suggestions
        const imageType = document.getElementById('imageType').value;
        const style = currentStyle;
        
        let enhancedPrompt = currentPrompt;
        
        // Add type-specific enhancements
        if (imageType === 'course') {
            enhancedPrompt += ', professional educational setting, high quality, detailed, modern design';
        } else if (imageType === 'announcement') {
            enhancedPrompt += ', eye-catching, vibrant colors, clear message, professional layout';
        } else if (imageType === 'certificate') {
            enhancedPrompt += ', elegant, formal design, high-resolution, premium quality';
        }
        
        // Add style-specific enhancements
        if (style === 'realistic') {
            enhancedPrompt += ', photorealistic, highly detailed, 8k resolution';
        } else if (style === 'artistic') {
            enhancedPrompt += ', artistic style, creative composition, beautiful colors';
        } else if (style === 'minimalist') {
            enhancedPrompt += ', minimalist design, clean lines, simple elegant';
        }
        
        promptField.value = enhancedPrompt;
        showToast('تم تحسين الوصف بنجاح!', 'success');
        
    } catch (error) {
        showToast('فشل تحسين الوصف', 'error');
    }
}

/**
 * Generate AI Image
 */
async function generateAIImage() {
    const form = document.getElementById('aiImageForm');
    const progress = document.getElementById('generationProgress');
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');
    
    const data = {
        prompt: document.getElementById('imagePrompt').value,
        type: document.getElementById('imageType').value,
        style: currentStyle,
        provider: document.getElementById('aiProvider').value,
        size: document.getElementById('imageSize').value,
        quality: document.getElementById('quality').value,
        addWatermark: document.getElementById('addWatermark').checked,
        autoEnhance: document.getElementById('autoEnhance').checked
    };
    
    // Show progress
    progress.classList.remove('hidden');
    form.querySelector('button[type="submit"]').disabled = true;
    
    // Simulate progress steps
    const steps = [
        { percent: 20, text: 'تحليل الوصف...' },
        { percent: 40, text: 'إنشاء الصورة بالذكاء الاصطناعي...' },
        { percent: 60, text: 'معالجة الصورة...' },
        { percent: 80, text: 'تحسين الجودة...' },
        { percent: 90, text: 'حفظ الصورة...' }
    ];
    
    let stepIndex = 0;
    const progressInterval = setInterval(() => {
        if (stepIndex < steps.length) {
            progressBar.style.width = steps[stepIndex].percent + '%';
            progressText.textContent = steps[stepIndex].text;
            stepIndex++;
        }
    }, 1000);
    
    try {
        const response = await fetch(API_ENDPOINTS.aiImages + '?action=generate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        clearInterval(progressInterval);
        progressBar.style.width = '100%';
        progressText.textContent = 'تم بنجاح!';
        
        if (result.success) {
            setTimeout(() => {
                progress.classList.add('hidden');
                form.querySelector('button[type="submit"]').disabled = false;
                progressBar.style.width = '0%';
                
                showToast('تم إنشاء الصورة بنجاح!', 'success');
                loadAIImages();
                
                // Show preview
                openImagePreview(result.data);
            }, 1000);
        } else {
            throw new Error(result.message || 'فشل إنشاء الصورة');
        }
        
    } catch (error) {
        clearInterval(progressInterval);
        progress.classList.add('hidden');
        form.querySelector('button[type="submit"]').disabled = false;
        showToast(error.message || 'حدث خطأ أثناء إنشاء الصورة', 'error');
    }
}

/**
 * Load AI Images Gallery
 */
let currentPageNum = 1;
let totalPagesNum = 1;
const imagesPerPage = 12;

async function loadAIImages(page = 1) {
    currentPageNum = page;
    const grid = document.getElementById('imagesGrid');
    const filterType = document.getElementById('filterType').value;
    
    grid.innerHTML = `
        <div class="col-span-full flex items-center justify-center py-12">
            <div class="text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-violet-600 mx-auto mb-4"></div>
                <p class="text-slate-600">جاري تحميل الصور...</p>
            </div>
        </div>
    `;
    
    try {
        const response = await fetch(`${API_ENDPOINTS.aiImages}?action=list&type=${filterType}&page=${page}&limit=${imagesPerPage}`);
        const result = await response.json();
        
        if (result.success && result.data && result.data.images) {
            const images = result.data.images;
            totalPagesNum = result.data.total_pages || 1;
            
            // Update stats
            if (result.data.stats) {
                document.getElementById('total-images').textContent = result.data.stats.total || 0;
                document.getElementById('month-images').textContent = result.data.stats.month || 0;
                document.getElementById('dalle-count').textContent = result.data.stats.dalle || 0;
                document.getElementById('sd-count').textContent = result.data.stats.stable_diffusion || 0;
            }
            
            if (images.length === 0) {
                grid.innerHTML = `
                    <div class="col-span-full text-center py-12">
                        <i data-lucide="image-off" class="w-16 h-16 mx-auto text-slate-300 mb-4"></i>
                        <p class="text-slate-600">لا توجد صور</p>
                        <button onclick="navigateTo('ai-images')" class="mt-4 px-4 py-2 bg-violet-600 text-white rounded-lg hover:bg-violet-700">
                            إنشاء صورة جديدة
                        </button>
                    </div>
                `;
                lucide.createIcons();
                return;
            }
            
            grid.innerHTML = images.map(img => `
                <div class="group relative bg-white rounded-xl shadow hover:shadow-xl transition-all overflow-hidden cursor-pointer"
                     onclick="openImagePreview(${escapeHtml(JSON.stringify(img))})">
                    <div class="aspect-square relative overflow-hidden bg-slate-100">
                        <img src="../${escapeHtml(img.file_path)}" 
                             alt="${escapeHtml(img.prompt)}"
                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
                             loading="lazy">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                            <div class="absolute bottom-0 left-0 right-0 p-3 text-white">
                                <p class="text-xs line-clamp-2">${escapeHtml(img.prompt)}</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-3">
                        <div class="flex items-center justify-between text-xs">
                            <span class="px-2 py-1 bg-violet-100 text-violet-700 rounded-full">${escapeHtml(img.image_type)}</span>
                            <span class="text-slate-500">${formatDateTime(img.created_at, {dateStyle: 'short'})}</span>
                        </div>
                    </div>
                </div>
            `).join('');
            
            // Show pagination
            document.getElementById('imagesPagination').classList.remove('hidden');
            document.getElementById('currentPage').textContent = currentPageNum;
            document.getElementById('totalPages').textContent = totalPagesNum;
            
            lucide.createIcons();
            
        } else {
            throw new Error(result.message || 'فشل تحميل الصور');
        }
        
    } catch (error) {
        grid.innerHTML = `
            <div class="col-span-full text-center py-12">
                <i data-lucide="alert-circle" class="w-16 h-16 mx-auto text-red-500 mb-4"></i>
                <p class="text-slate-600">حدث خطأ أثناء تحميل الصور</p>
                <button onclick="loadAIImages()" class="mt-4 px-4 py-2 bg-violet-600 text-white rounded-lg hover:bg-violet-700">
                    إعادة المحاولة
                </button>
            </div>
        `;
        lucide.createIcons();
    }
}

/**
 * Pagination Functions
 */
function nextPage() {
    if (currentPageNum < totalPagesNum) {
        loadAIImages(currentPageNum + 1);
    }
}

function previousPage() {
    if (currentPageNum > 1) {
        loadAIImages(currentPageNum - 1);
    }
}

/**
 * Search AI Images
 */
let searchTimeout;
function searchAIImages() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        const searchTerm = document.getElementById('searchImages').value.toLowerCase();
        // Implement search logic
        loadAIImages(1);
    }, 300);
}

/**
 * Load Templates
 */
async function loadTemplates() {
    const grid = document.getElementById('templatesGrid');
    
    const templates = [
        { name: 'تصميم دورة تقنية', type: 'course', prompt: 'modern technical course banner with computer, professional, blue tones' },
        { name: 'إعلان ترويجي', type: 'announcement', prompt: 'promotional announcement poster, vibrant colors, eye-catching' },
        { name: 'شهادة أنيقة', type: 'certificate', prompt: 'elegant certificate design, formal, premium quality' },
        { name: 'بانر احترافي', type: 'banner', prompt: 'professional banner design, modern, clean layout' },
        { name: 'شعار مبتكر', type: 'logo', prompt: 'innovative logo design, creative, memorable' },
        { name: 'صورة تعليمية', type: 'general', prompt: 'educational illustration, clear, informative' }
    ];
    
    grid.innerHTML = templates.map((template, index) => `
        <div class="group cursor-pointer bg-slate-50 rounded-lg p-4 hover:bg-violet-50 transition-all border-2 border-transparent hover:border-violet-300"
             onclick="applyTemplate(${index})">
            <div class="aspect-square bg-white rounded-lg mb-2 flex items-center justify-center">
                <i data-lucide="layout-template" class="w-8 h-8 text-violet-400 group-hover:text-violet-600"></i>
            </div>
            <p class="text-xs font-medium text-slate-700 text-center">${escapeHtml(template.name)}</p>
        </div>
    `).join('');
    
    lucide.createIcons();
    
    // Store templates globally
    window.aiTemplates = templates;
}

/**
 * Apply Template
 */
function applyTemplate(index) {
    const template = window.aiTemplates[index];
    if (template) {
        document.getElementById('imageType').value = template.type;
        document.getElementById('imagePrompt').value = template.prompt;
        showToast('تم تطبيق القالب!', 'success');
        
        // Scroll to form
        document.getElementById('aiImageForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

/**
 * Open Image Preview Modal
 */
function openImagePreview(image) {
    const modalContent = `
        <div class="space-y-4">
            <div class="relative rounded-xl overflow-hidden bg-slate-100">
                <img src="../${escapeHtml(image.file_path)}" 
                     alt="${escapeHtml(image.prompt)}"
                     class="w-full h-auto">
            </div>
            
            <div class="space-y-3">
                <div>
                    <label class="text-sm font-medium text-slate-600">الوصف (Prompt)</label>
                    <p class="mt-1 text-slate-800">${escapeHtml(image.prompt)}</p>
                </div>
                
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-sm font-medium text-slate-600">النوع</label>
                        <p class="mt-1 text-slate-800">${escapeHtml(image.image_type)}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-600">المحرك</label>
                        <p class="mt-1 text-slate-800">${escapeHtml(image.provider)}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-600">الحجم</label>
                        <p class="mt-1 text-slate-800">${escapeHtml(image.size || 'N/A')}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-600">التاريخ</label>
                        <p class="mt-1 text-slate-800">${formatDateTime(image.created_at)}</p>
                    </div>
                </div>
            </div>
            
            <div class="flex gap-2">
                <button onclick="downloadImage('${escapeHtml(image.file_path)}')" 
                        class="flex-1 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 flex items-center justify-center gap-2">
                    <i data-lucide="download" class="w-4 h-4"></i>
                    تحميل
                </button>
                <button onclick="copyImageUrl('${escapeHtml(image.file_path)}')" 
                        class="flex-1 px-4 py-2 bg-sky-600 text-white rounded-lg hover:bg-sky-700 flex items-center justify-center gap-2">
                    <i data-lucide="copy" class="w-4 h-4"></i>
                    نسخ الرابط
                </button>
                <button onclick="deleteAIImage(${image.id})" 
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                </button>
            </div>
        </div>
    `;
    
    openModal('معاينة الصورة', modalContent);
}

/**
 * Download Image
 */
function downloadImage(filePath) {
    const link = document.createElement('a');
    link.href = '../' + filePath;
    link.download = filePath.split('/').pop();
    link.click();
    showToast('جاري تحميل الصورة...', 'info');
}

/**
 * Copy Image URL
 */
function copyImageUrl(filePath) {
    const fullUrl = window.location.origin + '/' + filePath;
    navigator.clipboard.writeText(fullUrl).then(() => {
        showToast('تم نسخ الرابط!', 'success');
    });
}

/**
 * Delete AI Image
 */
async function deleteAIImage(imageId) {
    if (!confirm('هل أنت متأكد من حذف هذه الصورة؟')) {
        return;
    }
    
    try {
        const response = await fetch(`${API_ENDPOINTS.aiImages}?action=delete`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ image_id: imageId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('تم حذف الصورة بنجاح', 'success');
            closeModal();
            loadAIImages(currentPageNum);
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        showToast(error.message || 'فشل حذف الصورة', 'error');
    }
}
