<!-- Notifications Panel Component - AI Enhanced -->
<?php 
// Include AI Libraries
include __DIR__ . '/../includes/ai-libraries.php'; 
?>

<style>
/* Notifications Panel Styles */
.notifications-panel {
    position: fixed;
    top: 0;
    left: -400px;
    width: 400px;
    height: 100vh;
    background: white;
    box-shadow: 2px 0 15px rgba(0, 0, 0, 0.1);
    transition: left 0.3s ease;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.notifications-panel.active {
    left: 0;
}

.notifications-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    flex-shrink: 0;
}

.notifications-header h3 {
    margin: 0 0 10px 0;
    font-size: 20px;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.notifications-close {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.notifications-close:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
}

.notifications-actions {
    display: flex;
    gap: 8px;
    margin-top: 10px;
}

.notifications-actions button {
    background: rgba(255, 255, 255, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: white;
    padding: 6px 12px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 12px;
    transition: all 0.3s;
}

.notifications-actions button:hover {
    background: rgba(255, 255, 255, 0.3);
}

.notifications-filter {
    padding: 15px 20px;
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    flex-shrink: 0;
}

.filter-tabs {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.filter-tab {
    background: white;
    border: 1px solid #dee2e6;
    padding: 6px 12px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    transition: all 0.3s;
    color: #495057;
}

.filter-tab:hover {
    border-color: #667eea;
    color: #667eea;
}

.filter-tab.active {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

.notifications-list {
    flex: 1;
    overflow-y: auto;
    padding: 10px;
}

.notifications-list::-webkit-scrollbar {
    width: 8px;
}

.notifications-list::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.notifications-list::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

.notifications-list::-webkit-scrollbar-thumb:hover {
    background: #555;
}

.notification-item {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 8px;
    cursor: pointer;
    transition: all 0.3s;
    position: relative;
}

.notification-item:hover {
    border-color: #667eea;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.1);
}

.notification-item.unread {
    background: #f0f4ff;
    border-left: 3px solid #667eea;
}

.notification-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-left: 12px;
    float: right;
}

.notification-icon.info {
    background: #e3f2fd;
    color: #2196f3;
}

.notification-icon.success {
    background: #e8f5e9;
    color: #4caf50;
}

.notification-icon.warning {
    background: #fff3e0;
    color: #ff9800;
}

.notification-icon.error {
    background: #ffebee;
    color: #f44336;
}

.notification-icon.message {
    background: #f3e5f5;
    color: #9c27b0;
}

.notification-content {
    overflow: hidden;
}

.notification-title {
    font-weight: 600;
    color: #212529;
    margin: 0 0 4px 0;
    font-size: 14px;
}

.notification-message {
    color: #6c757d;
    font-size: 13px;
    margin: 0 0 6px 0;
    line-height: 1.4;
}

.notification-time {
    color: #adb5bd;
    font-size: 11px;
    display: flex;
    align-items: center;
    gap: 4px;
}

.notification-actions-btn {
    position: absolute;
    top: 8px;
    left: 8px;
    background: transparent;
    border: none;
    color: #adb5bd;
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
    transition: all 0.3s;
}

.notification-actions-btn:hover {
    background: #f8f9fa;
    color: #495057;
}

.notifications-empty {
    text-align: center;
    padding: 40px 20px;
    color: #adb5bd;
}

.notifications-empty i {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.5;
}

.notifications-empty p {
    margin: 0;
    font-size: 14px;
}

.notifications-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 9998;
    display: none;
    opacity: 0;
    transition: opacity 0.3s;
}

.notifications-overlay.active {
    display: block;
    opacity: 1;
}

.notification-badge {
    background: #dc3545;
    color: white;
    border-radius: 10px;
    padding: 2px 6px;
    font-size: 11px;
    font-weight: bold;
}

/* Loading Animation */
.notifications-loading {
    text-align: center;
    padding: 20px;
}

.spinner {
    border: 3px solid #f3f3f3;
    border-top: 3px solid #667eea;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
    margin: 0 auto;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 768px) {
    .notifications-panel {
        width: 100%;
        left: -100%;
    }
}
</style>

<!-- Notifications Overlay -->
<div class="notifications-overlay" id="notificationsOverlay"></div>

<!-- Notifications Panel -->
<div class="notifications-panel" id="notificationsPanel">
    <!-- Header -->
    <div class="notifications-header">
        <h3>
            <span>
                <i data-lucide="bell" style="width: 20px; height: 20px;"></i>
                الإشعارات
                <span class="notification-badge" id="notificationsTotalBadge" style="display: none;">0</span>
            </span>
            <button class="notifications-close" id="closeNotificationsPanel">
                <i data-lucide="x" style="width: 20px; height: 20px;"></i>
            </button>
        </h3>
        <div class="notifications-actions">
            <button id="markAllReadBtn">
                <i data-lucide="check-check" style="width: 14px; height: 14px;"></i>
                تحديد الكل كمقروء
            </button>
            <button id="deleteAllBtn">
                <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                حذف الكل
            </button>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="notifications-filter">
        <div class="filter-tabs">
            <button class="filter-tab active" data-filter="all">
                الكل
                <span class="notification-badge" id="allCount" style="display: none;">0</span>
            </button>
            <button class="filter-tab" data-filter="unread">
                غير مقروءة
                <span class="notification-badge" id="unreadCount" style="display: none;">0</span>
            </button>
            <button class="filter-tab" data-filter="info">
                <i data-lucide="info" style="width: 12px; height: 12px;"></i>
                معلومات
            </button>
            <button class="filter-tab" data-filter="success">
                <i data-lucide="check-circle" style="width: 12px; height: 12px;"></i>
                نجاح
            </button>
            <button class="filter-tab" data-filter="warning">
                <i data-lucide="alert-triangle" style="width: 12px; height: 12px;"></i>
                تحذير
            </button>
            <button class="filter-tab" data-filter="message">
                <i data-lucide="message-circle" style="width: 12px; height: 12px;"></i>
                رسائل
            </button>
        </div>
    </div>

    <!-- Notifications List -->
    <div class="notifications-list" id="notificationsList">
        <!-- Loading State -->
        <div class="notifications-loading" id="notificationsLoading">
            <div class="spinner"></div>
            <p style="margin-top: 10px; color: #6c757d; font-size: 14px;">جاري التحميل...</p>
        </div>

        <!-- Empty State (Hidden initially) -->
        <div class="notifications-empty" id="notificationsEmpty" style="display: none;">
            <i data-lucide="bell-off"></i>
            <p>لا توجد إشعارات</p>
        </div>
    </div>
</div>

<!-- Notification Item Template -->
<template id="notificationItemTemplate">
    <div class="notification-item" data-notification-id="">
        <div class="notification-icon info">
            <i data-lucide="bell" style="width: 18px; height: 18px;"></i>
        </div>
        <button class="notification-actions-btn" title="حذف">
            <i data-lucide="trash-2" style="width: 16px; height: 16px;"></i>
        </button>
        <div class="notification-content">
            <h4 class="notification-title"></h4>
            <p class="notification-message"></p>
            <div class="notification-time">
                <i data-lucide="clock" style="width: 12px; height: 12px;"></i>
                <span></span>
            </div>
        </div>
    </div>
</template>

<!-- Load AI-Powered Notifications System -->
<script src="JS/ai_notifications.js"></script>

<script>
// Initialize Lucide icons after loading
if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}

// Initialize AI Notifications System
window.addEventListener('ai-libraries-ready', async () => {
    console.log('🤖 AI Libraries loaded, initializing notifications system...');
    
    try {
        // Create AI notification system instance
        window.notificationSystem = new AdvancedAINotificationsSystem();
        await window.notificationSystem.init();
        
        console.log('✅ AI Notifications System ready!');
    } catch (error) {
        console.error('❌ Failed to initialize AI notifications:', error);
    }
});
</script>
