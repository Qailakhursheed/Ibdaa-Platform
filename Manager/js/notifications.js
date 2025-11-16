/**
 * Notifications System - Complete JavaScript
 * Real-time notifications with polling, filtering, mark as read, delete
 */

class NotificationsSystem {
    constructor() {
        this.notifications = [];
        this.currentFilter = 'all';
        this.pollInterval = null;
        this.unreadCount = 0;
        
        this.init();
    }

    init() {
        this.initElements();
        this.attachEventListeners();
        this.loadNotifications();
        this.startPolling();
    }

    initElements() {
        // Panel elements
        this.panel = document.getElementById('notificationsPanel');
        this.overlay = document.getElementById('notificationsOverlay');
        this.list = document.getElementById('notificationsList');
        this.loading = document.getElementById('notificationsLoading');
        this.empty = document.getElementById('notificationsEmpty');
        
        // Buttons
        this.closeBtn = document.getElementById('closeNotificationsPanel');
        this.markAllReadBtn = document.getElementById('markAllReadBtn');
        this.deleteAllBtn = document.getElementById('deleteAllBtn');
        
        // Badges
        this.totalBadge = document.getElementById('notificationsTotalBadge');
        this.allCountBadge = document.getElementById('allCount');
        this.unreadCountBadge = document.getElementById('unreadCount');
        
        // Toggle button (should exist in dashboard header)
        this.toggleBtn = document.getElementById('notificationsToggle');
        this.headerBadge = document.getElementById('notificationsHeaderBadge');
        
        // Filter tabs
        this.filterTabs = document.querySelectorAll('.filter-tab');
        
        // Template
        this.template = document.getElementById('notificationItemTemplate');
    }

    attachEventListeners() {
        // Toggle panel
        if (this.toggleBtn) {
            this.toggleBtn.addEventListener('click', () => this.openPanel());
        }
        
        // Close panel
        this.closeBtn?.addEventListener('click', () => this.closePanel());
        this.overlay?.addEventListener('click', () => this.closePanel());
        
        // Mark all as read
        this.markAllReadBtn?.addEventListener('click', () => this.markAllAsRead());
        
        // Delete all
        this.deleteAllBtn?.addEventListener('click', () => this.deleteAllNotifications());
        
        // Filter tabs
        this.filterTabs.forEach(tab => {
            tab.addEventListener('click', (e) => {
                const filter = e.currentTarget.dataset.filter;
                this.setFilter(filter);
            });
        });
        
        // Keyboard shortcut (ESC to close)
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.panel?.classList.contains('active')) {
                this.closePanel();
            }
        });
    }

    /**
     * Open notifications panel
     */
    openPanel() {
        this.panel?.classList.add('active');
        this.overlay?.classList.add('active');
        this.loadNotifications();
    }

    /**
     * Close notifications panel
     */
    closePanel() {
        this.panel?.classList.remove('active');
        this.overlay?.classList.remove('active');
    }

    /**
     * Set filter
     */
    setFilter(filter) {
        this.currentFilter = filter;
        
        // Update active tab
        this.filterTabs.forEach(tab => {
            if (tab.dataset.filter === filter) {
                tab.classList.add('active');
            } else {
                tab.classList.remove('active');
            }
        });
        
        this.renderNotifications();
    }

    /**
     * Load notifications from API
     */
    async loadNotifications() {
        try {
            this.showLoading();
            
            const response = await fetch('api/notifications_system.php?action=all', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.notifications = data.notifications || [];
                this.unreadCount = data.unread_count || 0;
                this.renderNotifications();
                this.updateBadges();
            } else {
                console.error('Error loading notifications:', data.message);
                this.showEmpty();
            }
            
        } catch (error) {
            console.error('Error fetching notifications:', error);
            this.showEmpty();
        } finally {
            this.hideLoading();
        }
    }

    /**
     * Render notifications based on current filter
     */
    renderNotifications() {
        // Clear list
        this.list.innerHTML = '';
        
        // Filter notifications
        let filtered = this.notifications;
        
        if (this.currentFilter === 'unread') {
            filtered = this.notifications.filter(n => !n.is_read);
        } else if (this.currentFilter !== 'all') {
            filtered = this.notifications.filter(n => n.type === this.currentFilter);
        }
        
        // Show empty state if no notifications
        if (filtered.length === 0) {
            this.showEmpty();
            return;
        }
        
        // Render each notification
        filtered.forEach(notification => {
            const item = this.createNotificationItem(notification);
            this.list.appendChild(item);
        });
        
        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    /**
     * Create notification item DOM element
     */
    createNotificationItem(notification) {
        const template = this.template.content.cloneNode(true);
        const item = template.querySelector('.notification-item');
        
        // Set notification ID
        item.dataset.notificationId = notification.notification_id;
        
        // Add unread class
        if (!notification.is_read) {
            item.classList.add('unread');
        }
        
        // Set icon based on type
        const icon = item.querySelector('.notification-icon');
        const iconElement = icon.querySelector('i');
        
        icon.className = 'notification-icon ' + notification.type;
        
        switch (notification.type) {
            case 'success':
                iconElement.setAttribute('data-lucide', 'check-circle');
                break;
            case 'warning':
                iconElement.setAttribute('data-lucide', 'alert-triangle');
                break;
            case 'error':
                iconElement.setAttribute('data-lucide', 'alert-circle');
                break;
            case 'message':
                iconElement.setAttribute('data-lucide', 'message-circle');
                break;
            default:
                iconElement.setAttribute('data-lucide', 'info');
        }
        
        // Set content
        item.querySelector('.notification-title').textContent = notification.title || 'إشعار';
        item.querySelector('.notification-message').textContent = notification.message;
        item.querySelector('.notification-time span').textContent = notification.time_ago || 'الآن';
        
        // Click to mark as read and navigate
        item.addEventListener('click', (e) => {
            if (!e.target.closest('.notification-actions-btn')) {
                this.markAsRead(notification.notification_id);
                if (notification.link) {
                    window.location.href = notification.link;
                }
            }
        });
        
        // Delete button
        const deleteBtn = item.querySelector('.notification-actions-btn');
        deleteBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            this.deleteNotification(notification.notification_id);
        });
        
        return item;
    }

    /**
     * Mark notification as read
     */
    async markAsRead(notificationId) {
        try {
            const response = await fetch('api/mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    notification_id: notificationId
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Update local state
                const notification = this.notifications.find(n => n.notification_id === notificationId);
                if (notification) {
                    notification.is_read = true;
                }
                
                this.unreadCount = Math.max(0, this.unreadCount - 1);
                this.renderNotifications();
                this.updateBadges();
            }
            
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }

    /**
     * Mark all notifications as read
     */
    async markAllAsRead() {
        if (this.unreadCount === 0) {
            return;
        }
        
        if (!confirm('هل تريد تحديد جميع الإشعارات كمقروءة؟')) {
            return;
        }
        
        try {
            const response = await fetch('api/notifications_system.php?action=mark_all_read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Update local state
                this.notifications.forEach(n => n.is_read = true);
                this.unreadCount = 0;
                this.renderNotifications();
                this.updateBadges();
                
                this.showToast('تم تحديد جميع الإشعارات كمقروءة', 'success');
            } else {
                this.showToast(data.message || 'حدث خطأ', 'error');
            }
            
        } catch (error) {
            console.error('Error marking all as read:', error);
            this.showToast('حدث خطأ أثناء العملية', 'error');
        }
    }

    /**
     * Delete single notification
     */
    async deleteNotification(notificationId) {
        if (!confirm('هل تريد حذف هذا الإشعار؟')) {
            return;
        }
        
        try {
            const response = await fetch(`api/delete_notifications.php?notification_id=${notificationId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Remove from local state
                const index = this.notifications.findIndex(n => n.notification_id === notificationId);
                if (index !== -1) {
                    const notification = this.notifications[index];
                    if (!notification.is_read) {
                        this.unreadCount = Math.max(0, this.unreadCount - 1);
                    }
                    this.notifications.splice(index, 1);
                }
                
                this.renderNotifications();
                this.updateBadges();
                
                this.showToast('تم حذف الإشعار', 'success');
            } else {
                this.showToast(data.message || 'حدث خطأ', 'error');
            }
            
        } catch (error) {
            console.error('Error deleting notification:', error);
            this.showToast('حدث خطأ أثناء الحذف', 'error');
        }
    }

    /**
     * Delete all notifications
     */
    async deleteAllNotifications() {
        if (this.notifications.length === 0) {
            return;
        }
        
        if (!confirm('هل تريد حذف جميع الإشعارات؟ لا يمكن التراجع عن هذا الإجراء.')) {
            return;
        }
        
        try {
            const response = await fetch('api/delete_notifications.php?all=true', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Clear local state
                this.notifications = [];
                this.unreadCount = 0;
                this.renderNotifications();
                this.updateBadges();
                
                this.showToast('تم حذف جميع الإشعارات', 'success');
            } else {
                this.showToast(data.message || 'حدث خطأ', 'error');
            }
            
        } catch (error) {
            console.error('Error deleting all notifications:', error);
            this.showToast('حدث خطأ أثناء الحذف', 'error');
        }
    }

    /**
     * Update badges
     */
    updateBadges() {
        // Header badge (main dashboard)
        if (this.headerBadge) {
            if (this.unreadCount > 0) {
                this.headerBadge.textContent = this.unreadCount > 99 ? '99+' : this.unreadCount;
                this.headerBadge.style.display = 'inline-block';
            } else {
                this.headerBadge.style.display = 'none';
            }
        }
        
        // Panel total badge
        if (this.totalBadge) {
            if (this.notifications.length > 0) {
                this.totalBadge.textContent = this.notifications.length;
                this.totalBadge.style.display = 'inline-block';
            } else {
                this.totalBadge.style.display = 'none';
            }
        }
        
        // All count badge
        if (this.allCountBadge) {
            if (this.notifications.length > 0) {
                this.allCountBadge.textContent = this.notifications.length;
                this.allCountBadge.style.display = 'inline-block';
            } else {
                this.allCountBadge.style.display = 'none';
            }
        }
        
        // Unread count badge
        if (this.unreadCountBadge) {
            if (this.unreadCount > 0) {
                this.unreadCountBadge.textContent = this.unreadCount;
                this.unreadCountBadge.style.display = 'inline-block';
            } else {
                this.unreadCountBadge.style.display = 'none';
            }
        }
    }

    /**
     * Start polling for new notifications
     */
    startPolling() {
        // Poll every 30 seconds
        this.pollInterval = setInterval(() => {
            this.loadNotifications();
        }, 30000);
    }

    /**
     * Stop polling
     */
    stopPolling() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
        }
    }

    /**
     * Show loading state
     */
    showLoading() {
        if (this.loading) {
            this.loading.style.display = 'block';
        }
        if (this.empty) {
            this.empty.style.display = 'none';
        }
    }

    /**
     * Hide loading state
     */
    hideLoading() {
        if (this.loading) {
            this.loading.style.display = 'none';
        }
    }

    /**
     * Show empty state
     */
    showEmpty() {
        if (this.empty) {
            this.empty.style.display = 'block';
        }
        if (this.loading) {
            this.loading.style.display = 'none';
        }
        
        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    /**
     * Show toast notification
     */
    showToast(message, type = 'info') {
        // Simple toast notification
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: ${type === 'success' ? '#4caf50' : type === 'error' ? '#f44336' : '#2196f3'};
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000;
            animation: slideUp 0.3s ease;
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideDown 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    window.notificationsSystem = new NotificationsSystem();
});

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateX(-50%) translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
    }
    
    @keyframes slideDown {
        from {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
        to {
            opacity: 0;
            transform: translateX(-50%) translateY(20px);
        }
    }
`;
document.head.appendChild(style);
