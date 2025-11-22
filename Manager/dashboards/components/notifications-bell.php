<!-- Unified Notifications Bell Component -->
<!-- Component المكون الموحد للإشعارات -->
<!-- يمكن استخدامه في جميع اللوحات مباشرة -->

<div id="notificationsBellContainer" class="relative">
    <!-- Notifications Button -->
    <button id="notificationsBell" class="relative p-2 rounded-lg hover:bg-slate-100 transition-colors focus:outline-none focus:ring-2 focus:ring-sky-500" aria-label="عرض الإشعارات" title="الإشعارات">
        <i data-lucide="bell" class="w-6 h-6 text-slate-600"></i>
        <span id="notificationsCounter" class="absolute top-0 right-0 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center font-bold hidden">0</span>
    </button>
    
    <!-- Notifications Dropdown Modal -->
    <div id="notificationsModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
        
        <!-- Modal Content -->
        <div class="relative w-full h-full flex items-start justify-end pt-16 pr-4">
            <div class="bg-white rounded-lg shadow-xl w-96 max-w-md max-h-96 overflow-hidden flex flex-col">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-slate-200 bg-gradient-to-r from-sky-50 to-blue-50">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                            <i data-lucide="bell" class="w-5 h-5"></i>
                            الإشعارات
                        </h3>
                        <button class="text-slate-500 hover:text-slate-700 transition-colors" onclick="closeNotificationsModal()">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Notifications List -->
                <div id="notificationsList" class="flex-1 overflow-y-auto">
                    <div class="flex items-center justify-center h-32">
                        <div class="text-center text-slate-500">
                            <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-2 opacity-50"></i>
                            <p class="text-sm">جاري تحميل الإشعارات...</p>
                        </div>
                    </div>
                </div>
                
                <!-- Footer -->
                <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex gap-2">
                    <button onclick="markAllAsRead()" class="flex-1 px-4 py-2 bg-sky-600 text-white rounded-lg hover:bg-sky-700 transition-colors text-sm font-semibold">
                        تحديد الكل كمقروء
                    </button>
                    <button onclick="closeNotificationsModal()" class="flex-1 px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 transition-colors text-sm font-semibold">
                        إغلاق
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * Unified Notifications System Component
 * نظام الإشعارات الموحد
 */

let notificationsRefreshInterval = null;
let allNotifications = [];

// Load notifications on page load
document.addEventListener('DOMContentLoaded', function() {
    loadNotifications();
    // Refresh every minute
    notificationsRefreshInterval = setInterval(loadNotifications, 60000);
});

// Function to load notifications
async function loadNotifications() {
    try {
        if (!window.DashboardIntegration || !window.DashboardIntegration.api.notifications) {
            console.warn('DashboardIntegration not available yet');
            return;
        }
        
        const response = await DashboardIntegration.api.notifications.getAll(1, 50);
        
        if (response.success && response.data) {
            allNotifications = response.data;
            updateNotificationsCounter();
        }
    } catch (error) {
        console.error('Failed to load notifications:', error);
    }
}

// Update counter badge
function updateNotificationsCounter() {
    const unreadCount = allNotifications.filter(n => !n.is_read).length;
    const counter = document.getElementById('notificationsCounter');
    
    if (unreadCount > 0) {
        counter.textContent = unreadCount > 99 ? '99+' : unreadCount;
        counter.classList.remove('hidden');
    } else {
        counter.classList.add('hidden');
    }
}

// Open notifications modal
document.getElementById('notificationsBell')?.addEventListener('click', function(e) {
    e.stopPropagation();
    const modal = document.getElementById('notificationsModal');
    
    if (modal.classList.contains('hidden')) {
        modal.classList.remove('hidden');
        renderNotifications();
    } else {
        modal.classList.add('hidden');
    }
});

// Close modal when clicking backdrop
document.getElementById('notificationsModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeNotificationsModal();
    }
});

// Render notifications list
function renderNotifications() {
    const list = document.getElementById('notificationsList');
    
    if (!allNotifications || allNotifications.length === 0) {
        list.innerHTML = `
            <div class="flex items-center justify-center h-32">
                <div class="text-center text-slate-500">
                    <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-2 opacity-50"></i>
                    <p class="text-sm">لا توجد إشعارات</p>
                </div>
            </div>
        `;
        return;
    }
    
    const notificationsHTML = allNotifications.map(notif => {
        const typeColors = {
            'success': 'emerald',
            'error': 'red',
            'warning': 'amber',
            'info': 'sky',
            'payment': 'purple',
            'enrollment': 'blue',
            'card': 'indigo',
            'announcement': 'orange'
        };
        
        const typeIcons = {
            'success': 'check-circle',
            'error': 'alert-circle',
            'warning': 'alert-triangle',
            'info': 'info',
            'payment': 'credit-card',
            'enrollment': 'user-plus',
            'card': 'id-card',
            'announcement': 'megaphone'
        };
        
        const color = typeColors[notif.type] || 'sky';
        const icon = typeIcons[notif.type] || 'bell';
        
        const createdDate = new Date(notif.created_at);
        const timeAgo = getTimeAgo(createdDate);
        
        return `
            <div class="px-6 py-4 border-b border-slate-100 hover:bg-slate-50 transition-colors cursor-pointer ${!notif.is_read ? 'bg-blue-50' : ''}" 
                 onclick="markNotificationAsRead(${notif.notification_id})">
                <div class="flex items-start gap-4">
                    <!-- Icon -->
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-${color}-100 flex items-center justify-center mt-0.5">
                        <i data-lucide="${icon}" class="w-5 h-5 text-${color}-600"></i>
                    </div>
                    
                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <h4 class="font-semibold text-slate-800 text-sm">${escapeHtml(notif.title)}</h4>
                        <p class="text-sm text-slate-600 mt-0.5 line-clamp-2">${escapeHtml(notif.message)}</p>
                        <p class="text-xs text-slate-400 mt-2">${timeAgo}</p>
                    </div>
                    
                    <!-- Unread indicator -->
                    ${!notif.is_read ? `<div class="flex-shrink-0 w-2 h-2 bg-sky-500 rounded-full mt-2"></div>` : ''}
                </div>
            </div>
        `;
    }).join('');
    
    list.innerHTML = notificationsHTML;
    
    // Re-initialize Lucide icons
    if (window.lucide) {
        lucide.createIcons();
    }
}

// Mark single notification as read
async function markNotificationAsRead(notificationId) {
    try {
        const response = await DashboardIntegration.api.notifications.markAsRead([notificationId]);
        
        if (response.success) {
            // Update local state
            const notif = allNotifications.find(n => n.notification_id === notificationId);
            if (notif) {
                notif.is_read = true;
                updateNotificationsCounter();
                renderNotifications();
            }
        }
    } catch (error) {
        console.error('Failed to mark notification as read:', error);
    }
}

// Mark all as read
async function markAllAsRead() {
    try {
        const unreadIds = allNotifications
            .filter(n => !n.is_read)
            .map(n => n.notification_id);
        
        if (unreadIds.length === 0) {
            showToast('جميع الإشعارات مقروءة بالفعل', 'info');
            return;
        }
        
        const response = await DashboardIntegration.api.notifications.markAsRead(unreadIds);
        
        if (response.success) {
            // Update local state
            allNotifications.forEach(n => n.is_read = true);
            updateNotificationsCounter();
            renderNotifications();
            showToast('تم تحديد جميع الإشعارات كمقروء', 'success');
        }
    } catch (error) {
        console.error('Failed to mark all as read:', error);
        showToast('فشل تحديث الإشعارات', 'error');
    }
}

// Close modal
function closeNotificationsModal() {
    const modal = document.getElementById('notificationsModal');
    modal.classList.add('hidden');
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const modal = document.getElementById('notificationsModal');
    const bell = document.getElementById('notificationsBell');
    const container = document.getElementById('notificationsBellContainer');
    
    if (!container.contains(event.target)) {
        modal.classList.add('hidden');
    }
});

// Utility: Format time ago
function getTimeAgo(date) {
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    
    if (seconds < 60) return 'للتو';
    if (seconds < 3600) return Math.floor(seconds / 60) + ' دقيقة';
    if (seconds < 86400) return Math.floor(seconds / 3600) + ' ساعة';
    if (seconds < 604800) return Math.floor(seconds / 86400) + ' أيام';
    
    return date.toLocaleDateString('ar-EG');
}

// Utility: Escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Utility: Show toast
function showToast(message, type = 'info') {
    if (window.DashboardIntegration && window.DashboardIntegration.ui && window.DashboardIntegration.ui.showToast) {
        DashboardIntegration.ui.showToast(message, type);
    } else {
        console.log(`[${type.toUpperCase()}] ${message}`);
    }
}

// Refresh notifications every minute
setInterval(loadNotifications, 60000);
</script>

<style>
#notificationsModal {
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
