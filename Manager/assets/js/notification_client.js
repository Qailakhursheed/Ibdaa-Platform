/**
 * ====================================================================
 * Real-time Notifications Client (WebSocket)
 * عميل الإشعارات الفورية
 * ====================================================================
 * Usage:
 * const notifClient = new NotificationClient(userId);
 * notifClient.connect();
 * ====================================================================
 */

class NotificationClient {
    constructor(userId, options = {}) {
        this.userId = userId;
        this.websocketUrl = options.websocketUrl || 'ws://localhost:8080';
        this.reconnectDelay = options.reconnectDelay || 3000;
        this.maxReconnectAttempts = options.maxReconnectAttempts || 10;
        
        this.socket = null;
        this.reconnectAttempts = 0;
        this.isConnected = false;
        this.reconnectTimeout = null;
        
        // Callbacks
        this.onNotification = options.onNotification || (() => {});
        this.onConnect = options.onConnect || (() => {});
        this.onDisconnect = options.onDisconnect || (() => {});
        this.onError = options.onError || (() => {});
        
        // Keep-alive
        this.pingInterval = null;
        this.pingDelay = 30000; // 30 seconds
    }
    
    /**
     * Connect to WebSocket server
     */
    connect() {
        if (this.socket && this.socket.readyState === WebSocket.OPEN) {
            console.log('✅ Already connected');
            return;
        }
        
        const url = `${this.websocketUrl}?user_id=${this.userId}`;
        console.log(`🔌 Connecting to WebSocket: ${url}`);
        
        try {
            this.socket = new WebSocket(url);
            
            this.socket.onopen = (event) => this.handleOpen(event);
            this.socket.onmessage = (event) => this.handleMessage(event);
            this.socket.onclose = (event) => this.handleClose(event);
            this.socket.onerror = (event) => this.handleError(event);
            
        } catch (error) {
            console.error('❌ WebSocket connection failed:', error);
            this.scheduleReconnect();
        }
    }
    
    /**
     * Disconnect from server
     */
    disconnect() {
        console.log('🔌 Disconnecting...');
        
        if (this.pingInterval) {
            clearInterval(this.pingInterval);
            this.pingInterval = null;
        }
        
        if (this.reconnectTimeout) {
            clearTimeout(this.reconnectTimeout);
            this.reconnectTimeout = null;
        }
        
        if (this.socket) {
            this.socket.close();
            this.socket = null;
        }
        
        this.isConnected = false;
    }
    
    /**
     * Send message to server
     */
    send(data) {
        if (!this.isConnected || !this.socket) {
            console.warn('⚠️ Not connected. Cannot send message.');
            return false;
        }
        
        try {
            this.socket.send(JSON.stringify(data));
            return true;
        } catch (error) {
            console.error('❌ Failed to send message:', error);
            return false;
        }
    }
    
    /**
     * Mark notification as read
     */
    markAsRead(notificationId) {
        return this.send({
            type: 'mark_read',
            notification_id: notificationId
        });
    }
    
    /**
     * Send ping to keep connection alive
     */
    sendPing() {
        this.send({ type: 'ping' });
    }
    
    /**
     * Handle connection open
     */
    handleOpen(event) {
        console.log('✅ WebSocket connected');
        this.isConnected = true;
        this.reconnectAttempts = 0;
        
        // Start keep-alive ping
        this.pingInterval = setInterval(() => {
            this.sendPing();
        }, this.pingDelay);
        
        // Update UI
        this.updateConnectionStatus(true);
        
        // Call user callback
        this.onConnect(event);
    }
    
    /**
     * Handle incoming message
     */
    handleMessage(event) {
        try {
            const data = JSON.parse(event.data);
            const type = data.type || '';
            
            console.log('📩 Message received:', type, data);
            
            switch (type) {
                case 'connected':
                    console.log('✅ Server confirmed connection');
                    break;
                    
                case 'notification':
                    this.handleNotification(data.data);
                    break;
                    
                case 'pong':
                    // Keep-alive response
                    break;
                    
                case 'broadcast':
                    this.handleBroadcast(data.message);
                    break;
                    
                default:
                    console.log('📨 Unknown message type:', type);
            }
            
        } catch (error) {
            console.error('❌ Failed to parse message:', error);
        }
    }
    
    /**
     * Handle connection close
     */
    handleClose(event) {
        console.log('❌ WebSocket disconnected:', event.code, event.reason);
        this.isConnected = false;
        
        if (this.pingInterval) {
            clearInterval(this.pingInterval);
            this.pingInterval = null;
        }
        
        // Update UI
        this.updateConnectionStatus(false);
        
        // Call user callback
        this.onDisconnect(event);
        
        // Try to reconnect
        if (event.code !== 1000) { // Not a normal closure
            this.scheduleReconnect();
        }
    }
    
    /**
     * Handle connection error
     */
    handleError(event) {
        console.error('❌ WebSocket error:', event);
        this.onError(event);
    }
    
    /**
     * Handle incoming notification
     */
    handleNotification(notification) {
        console.log('🔔 New notification:', notification);
        
        // Show browser notification
        this.showBrowserNotification(notification);
        
        // Play sound
        this.playNotificationSound();
        
        // Update notification counter
        this.updateNotificationCounter();
        
        // Call user callback
        this.onNotification(notification);
    }
    
    /**
     * Handle broadcast message
     */
    handleBroadcast(message) {
        console.log('📣 Broadcast:', message);
        // You can show a toast or alert for broadcasts
    }
    
    /**
     * Schedule reconnection attempt
     */
    scheduleReconnect() {
        if (this.reconnectAttempts >= this.maxReconnectAttempts) {
            console.error('❌ Max reconnect attempts reached. Giving up.');
            return;
        }
        
        this.reconnectAttempts++;
        const delay = this.reconnectDelay * this.reconnectAttempts;
        
        console.log(`🔄 Reconnecting in ${delay}ms (attempt ${this.reconnectAttempts}/${this.maxReconnectAttempts})...`);
        
        this.reconnectTimeout = setTimeout(() => {
            this.connect();
        }, delay);
    }
    
    /**
     * Show browser notification
     */
    showBrowserNotification(notification) {
        if (!('Notification' in window)) {
            return;
        }
        
        if (Notification.permission === 'granted') {
            new Notification(notification.message || 'إشعار جديد', {
                body: notification.body || '',
                icon: '/platform/photos/Sh.jpg',
                badge: '/platform/photos/Sh.jpg',
                tag: `notification-${notification.id}`,
                requireInteraction: notification.priority === 'urgent'
            });
        } else if (Notification.permission !== 'denied') {
            Notification.requestPermission().then(permission => {
                if (permission === 'granted') {
                    this.showBrowserNotification(notification);
                }
            });
        }
    }
    
    /**
     * Play notification sound
     */
    playNotificationSound() {
        try {
            const audio = new Audio('/assets/sounds/notification.mp3');
            audio.volume = 0.5;
            audio.play().catch(() => {
                // Ignore if autoplay is blocked
            });
        } catch (error) {
            // Sound file not found or error playing
        }
    }
    
    /**
     * Update notification counter in UI
     */
    updateNotificationCounter() {
        fetch('api/notifications_realtime.php?action=get_unread_count')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const counter = document.getElementById('notificationsCounter') || 
                                  document.getElementById('studentNotificationsCounter');
                    
                    if (counter) {
                        counter.textContent = data.count;
                        counter.classList.toggle('hidden', data.count === 0);
                    }
                }
            })
            .catch(console.error);
    }
    
    /**
     * Update connection status indicator in UI
     */
    updateConnectionStatus(isConnected) {
        // Add a status indicator to your UI
        const indicator = document.getElementById('wsConnectionStatus');
        
        if (indicator) {
            if (isConnected) {
                indicator.className = 'text-green-500';
                indicator.title = 'متصل بالخادم';
                indicator.innerHTML = '<i data-lucide="wifi" class="w-4 h-4"></i>';
            } else {
                indicator.className = 'text-red-500';
                indicator.title = 'غير متصل';
                indicator.innerHTML = '<i data-lucide="wifi-off" class="w-4 h-4"></i>';
            }
            
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }
    }
}

// ============================================================================
// Auto-initialize on page load
// ============================================================================

document.addEventListener('DOMContentLoaded', () => {
    // Check if user is logged in
    const userId = document.body.dataset.userId || 
                  (typeof CURRENT_USER !== 'undefined' ? CURRENT_USER.id : null);
    
    if (!userId) {
        console.log('⚠️ No user ID found. WebSocket not initialized.');
        return;
    }
    
    // Initialize WebSocket client
    window.notificationClient = new NotificationClient(userId, {
        onNotification: (notification) => {
            console.log('📬 Notification callback:', notification);
            
            // Show toast notification
            showToast({
                type: 'info',
                title: 'إشعار جديد',
                message: notification.message,
                duration: 5000,
                action: notification.action_url ? {
                    label: 'عرض',
                    onClick: () => {
                        window.location.href = notification.action_url;
                    }
                } : null
            });
        },
        onConnect: () => {
            console.log('✅ Connected to notification server');
        },
        onDisconnect: () => {
            console.log('❌ Disconnected from notification server');
        }
    });
    
    // Connect
    window.notificationClient.connect();
    
    // Disconnect on page unload
    window.addEventListener('beforeunload', () => {
        if (window.notificationClient) {
            window.notificationClient.disconnect();
        }
    });
});

/**
 * Helper: Show toast notification
 */
function showToast(options) {
    // Implementation depends on your toast library
    // Example using basic alert (replace with your toast component)
    if (typeof Toastify !== 'undefined') {
        Toastify({
            text: `${options.title}: ${options.message}`,
            duration: options.duration || 3000,
            gravity: "top",
            position: "left",
            style: {
                background: "#3b82f6",
            }
        }).showToast();
    } else {
        console.log('📣 Toast:', options);
    }
}
