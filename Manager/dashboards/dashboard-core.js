/**
 * Dashboard Core JavaScript
 * جميع الوظائف المشتركة للوحات التحكم
 */

// معلومات المستخدم الحالي (سيتم تعيينها من PHP)
window.CURRENT_USER = window.CURRENT_USER || {};

// API Endpoints
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
    manageLmsContent: '../api/manage_lms_content.php',
    manageLmsAssignments: '../api/manage_lms_assignments.php',
    manageAttendance: '../api/manage_attendance.php',
    manageMessages: '../api/manage_messages.php',
    analyticsData: '../api/get_analytics_data.php',
    notifications: '../api/get_notifications.php',
    studentData: '../api/get_student_data.php',
    aiImages: '../api/ai_image_generator.php'
};

// Helper Functions
function showToast(message, type = 'info') {
    // Create toast element if doesn't exist
    let toast = document.getElementById('toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toast';
        toast.className = 'fixed bottom-6 right-6 px-6 py-3 rounded-lg shadow-lg z-50 transition-all';
        document.body.appendChild(toast);
    }
    
    const colors = {
        success: 'bg-emerald-600 text-white',
        error: 'bg-red-600 text-white',
        warning: 'bg-amber-600 text-white',
        info: 'bg-slate-800 text-white'
    };
    
    toast.className = 'fixed bottom-6 right-6 px-6 py-3 rounded-lg shadow-lg z-50 transition-all ' + (colors[type] || colors.info);
    toast.textContent = message;
    toast.style.display = 'block';
    
    setTimeout(() => {
        toast.style.display = 'none';
    }, 4000);
}

async function fetchJson(url, options = {}) {
    try {
        const response = await fetch(url, options);
        const contentType = response.headers.get('content-type') || '';
        
        if (!contentType.includes('application/json')) {
            throw new Error('استجابة غير متوقعة من الخادم');
        }
        
        const payload = await response.json();
        
        if (!response.ok || payload.success === false) {
            throw new Error(payload.message || payload.error || 'حدث خطأ أثناء تنفيذ العملية');
        }
        
        return payload;
    } catch (error) {
        console.error('Fetch error:', error);
        throw error;
    }
}

function hasPermission(allowedRoles) {
    if (!allowedRoles) return true;
    const roles = Array.isArray(allowedRoles) ? allowedRoles : allowedRoles.split(',').map(r => r.trim());
    return roles.includes(CURRENT_USER.role);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('ar-EG', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function formatMoney(amount) {
    return new Intl.NumberFormat('ar-EG', {
        style: 'decimal',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    }).format(amount || 0) + ' ريال';
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    
    // Setup sidebar navigation
    setupSidebarNavigation();
    
    // Setup any modals
    setupModals();
    
    console.log('Dashboard initialized for user:', CURRENT_USER);
});

function setupSidebarNavigation() {
    const links = document.querySelectorAll('.sidebar-link');
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            // Remove active from all
            links.forEach(l => l.classList.remove('active'));
            // Add active to clicked
            this.classList.add('active');
        });
    });
}

function setupModals() {
    // Modal close buttons
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-close') || 
            e.target.closest('.modal-close')) {
            closeAllModals();
        }
    });
}

function closeAllModals() {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.classList.add('hidden');
    });
}

// Export functions for use in pages
window.dashboardCore = {
    showToast,
    fetchJson,
    hasPermission,
    escapeHtml,
    formatDate,
    formatMoney,
    API_ENDPOINTS
};
