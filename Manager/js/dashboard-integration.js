/**
 * Dashboard Integration System
 * نظام الترابط بين اللوحات
 * 
 * هذا الملف يوفر وظائف الترابط بين جميع لوحات التحكم
 * (المدير، المشرف الفني، المدرب، الطالب)
 */

// ===== معلومات المستخدم الحالي =====
const DashboardIntegration = {
    currentUser: window.CURRENT_USER || {},
    
    // ===== التنقل بين اللوحات =====
    navigation: {
        // الانتقال إلى لوحة المدير
        toManager: function() {
            window.location.href = '/Manager/dashboards/manager-dashboard.php';
        },
        
        // الانتقال إلى لوحة المشرف الفني
        toTechnical: function() {
            window.location.href = '/Manager/dashboards/technical-dashboard.php';
        },
        
        // الانتقال إلى لوحة المدرب
        toTrainer: function(trainerId = null) {
            if (trainerId) {
                window.location.href = `/Manager/dashboards/trainer-dashboard.php?id=${trainerId}`;
            } else {
                window.location.href = '/Manager/dashboards/trainer-dashboard.php';
            }
        },
        
        // الانتقال إلى لوحة الطالب
        toStudent: function(studentId = null) {
            if (studentId) {
                window.location.href = `/Manager/dashboards/student-dashboard.php?id=${studentId}`;
            } else {
                window.location.href = '/Manager/dashboards/student-dashboard.php';
            }
        },
        
        // الانتقال عبر موجه اللوحات
        toDashboard: function(role, userId = null) {
            let url = '/Manager/dashboard_router.php';
            if (userId) {
                url += `?user_id=${userId}`;
            }
            window.location.href = url;
        }
    },
    
    // ===== API Endpoints =====
    api: {
        // نظام الدردشة
        chat: {
            base: '/Manager/api/chat_system.php',
            
            getConversations: function() {
                return fetch(this.base + '?action=conversations')
                    .then(response => response.json());
            },
            
            getMessages: function(contactId) {
                return fetch(this.base + `?action=messages&contact_id=${contactId}`)
                    .then(response => response.json());
            },
            
            sendMessage: function(receiverId, message) {
                return fetch(this.base, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'send',
                        receiver_id: receiverId,
                        message: message
                    })
                }).then(response => response.json());
            },
            
            markAsRead: function(contactId) {
                return fetch(this.base, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'mark_read',
                        contact_id: contactId
                    })
                }).then(response => response.json());
            }
        },
        
        // نظام الإشعارات
        notifications: {
            base: '/Manager/api/notifications_system.php',
            
            getAll: function(page = 1, limit = 10) {
                return fetch(this.base + `?action=all&page=${page}&limit=${limit}`)
                    .then(response => response.json());
            },
            
            create: function(title, message, type, link = null) {
                return fetch(this.base, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'create',
                        title: title,
                        message: message,
                        type: type,
                        link: link
                    })
                }).then(response => response.json());
            },
            
            broadcast: function(title, message, targetRoles) {
                return fetch(this.base, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'broadcast',
                        title: title,
                        message: message,
                        type: 'announcement',
                        target_roles: targetRoles
                    })
                }).then(response => response.json());
            },
            
            markAsRead: function(notificationIds) {
                return fetch(this.base, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'mark_read',
                        notification_ids: notificationIds
                    })
                }).then(response => response.json());
            }
        },
        
        // نظام البطاقات
        idCards: {
            base: '/Manager/api/id_cards_system.php',
            
            generate: function(userId) {
                return fetch(this.base, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'generate',
                        user_id: userId
                    })
                }).then(response => response.json());
            },
            
            getCard: function(userId = null) {
                const url = this.base + '?action=get_card' + (userId ? `&user_id=${userId}` : '');
                return fetch(url).then(response => response.json());
            },
            
            scanVerify: function(qrData) {
                return fetch(this.base + `?action=scan_verify&qr_data=${encodeURIComponent(qrData)}`)
                    .then(response => response.json());
            }
        },
        
        // نظام الاستيراد
        import: {
            base: '/Manager/api/smart_import.php',
            
            uploadFile: function(file, importType) {
                const formData = new FormData();
                formData.append('file', file);
                formData.append('action', 'upload');
                formData.append('import_type', importType);
                
                return fetch(this.base, {
                    method: 'POST',
                    body: formData
                }).then(response => response.json());
            },
            
            getHistory: function() {
                return fetch(this.base + '?action=history')
                    .then(response => response.json());
            }
        },
        
        // نظام التحليلات
        analytics: {
            base: '/Manager/api/dynamic_analytics.php',
            
            getDashboardStats: function() {
                return fetch(this.base + '?action=dashboard_stats')
                    .then(response => response.json());
            },
            
            getStudentsByStatus: function() {
                return fetch(this.base + '?action=students_by_status')
                    .then(response => response.json());
            },
            
            getMonthlyRevenue: function(year) {
                return fetch(this.base + `?action=monthly_revenue&year=${year}`)
                    .then(response => response.json());
            },
            
            getComprehensive: function() {
                return fetch(this.base + '?action=comprehensive_analytics')
                    .then(response => response.json());
            }
        },
        
        // طلبات التسجيل
        registrationRequests: {
            base: '/Manager/api/registration_requests.php',
            
            getPending: function() {
                return fetch(this.base + '?status=pending')
                    .then(response => response.json());
            },
            
            approve: function(requestId) {
                return fetch(this.base, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'approve',
                        request_id: requestId
                    })
                }).then(response => response.json());
            },
            
            reject: function(requestId, reason) {
                return fetch(this.base, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'reject',
                        request_id: requestId,
                        rejection_reason: reason
                    })
                }).then(response => response.json());
            }
        }
    },
    
    // ===== وظائف مشتركة =====
    shared: {
        // إرسال رسالة
        sendMessage: function(receiverId, message) {
            return DashboardIntegration.api.chat.sendMessage(receiverId, message)
                .then(data => {
                    if (data.success) {
                        DashboardIntegration.ui.showToast('✓ تم إرسال الرسالة', 'success');
                    } else {
                        DashboardIntegration.ui.showToast('خطأ: ' + data.message, 'error');
                    }
                    return data;
                });
        },
        
        // إرسال إشعار
        sendNotification: function(userId, title, message, type = 'info', link = null) {
            return DashboardIntegration.api.notifications.create(title, message, type, link)
                .then(data => {
                    if (data.success) {
                        DashboardIntegration.ui.showToast('✓ تم إرسال الإشعار', 'success');
                    }
                    return data;
                });
        },
        
        // تنزيل البطاقة
        downloadIDCard: function(userId = null) {
            return DashboardIntegration.api.idCards.getCard(userId)
                .then(data => {
                    if (data.success && data.card) {
                        window.open(data.card.card_url, '_blank');
                        DashboardIntegration.ui.showToast('✓ جاري فتح البطاقة...', 'success');
                    } else {
                        DashboardIntegration.ui.showToast('لم يتم إصدار بطاقة بعد', 'warning');
                    }
                    return data;
                });
        },
        
        // مسح QR Code
        scanQRCode: function(qrData) {
            return DashboardIntegration.api.idCards.scanVerify(qrData)
                .then(data => {
                    if (data.success && data.card) {
                        DashboardIntegration.ui.showToast('✓ البطاقة صحيحة', 'success');
                        return data.card;
                    } else {
                        DashboardIntegration.ui.showToast('البطاقة غير صحيحة', 'error');
                        return null;
                    }
                });
        }
    },
    
    // ===== واجهة المستخدم =====
    ui: {
        showToast: function(message, type = 'info') {
            const colors = {
                success: 'bg-emerald-600',
                error: 'bg-red-600',
                warning: 'bg-amber-600',
                info: 'bg-slate-800'
            };
            
            let toast = document.getElementById('dashboardToast');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'dashboardToast';
                toast.className = 'fixed bottom-6 left-6 px-6 py-3 rounded-lg shadow-lg z-50 text-white transition-all';
                document.body.appendChild(toast);
            }
            
            toast.className = `fixed bottom-6 left-6 px-6 py-3 rounded-lg shadow-lg z-50 text-white ${colors[type] || colors.info}`;
            toast.textContent = message;
            toast.style.display = 'block';
            toast.style.opacity = '1';
            
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => {
                    toast.style.display = 'none';
                }, 300);
            }, 3000);
        },
        
        showModal: function(title, content, buttons = []) {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center';
            modal.innerHTML = `
                <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-auto">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-xl font-bold text-slate-800">${title}</h3>
                        <button onclick="this.closest('.fixed').remove()" class="text-slate-400 hover:text-slate-600">
                            <i data-lucide="x" class="w-6 h-6"></i>
                        </button>
                    </div>
                    <div class="p-6">${content}</div>
                    ${buttons.length > 0 ? `
                        <div class="px-6 py-4 border-t border-slate-200 flex gap-2 justify-end">
                            ${buttons.map(btn => `
                                <button class="px-4 py-2 rounded-lg ${btn.class || 'bg-slate-200 text-slate-700'}" 
                                        onclick="${btn.onclick}">
                                    ${btn.text}
                                </button>
                            `).join('')}
                        </div>
                    ` : ''}
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // تهيئة الأيقونات
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
            
            return modal;
        },
        
        confirmDialog: function(message, onConfirm, onCancel = null) {
            const modal = this.showModal('تأكيد', `
                <div class="text-center py-4">
                    <i data-lucide="alert-circle" class="w-16 h-16 mx-auto text-amber-500 mb-4"></i>
                    <p class="text-lg text-slate-700">${message}</p>
                </div>
            `, [
                {
                    text: 'إلغاء',
                    class: 'bg-slate-200 text-slate-700 hover:bg-slate-300',
                    onclick: `this.closest('.fixed').remove(); ${onCancel ? onCancel + '()' : ''}`
                },
                {
                    text: 'تأكيد',
                    class: 'bg-sky-600 text-white hover:bg-sky-700',
                    onclick: `this.closest('.fixed').remove(); ${onConfirm}()`
                }
            ]);
        }
    },
    
    // ===== تهيئة النظام =====
    init: function() {
        console.log('🔗 Dashboard Integration System Initialized');
        console.log('👤 Current User:', this.currentUser);
        
        // إضافة أنماط CSS
        this.injectStyles();
        
        // تفعيل الاختصارات العامة
        this.setupGlobalShortcuts();
        
        return this;
    },
    
    injectStyles: function() {
        const styles = `
            <style>
                #dashboardToast {
                    transition: opacity 0.3s ease;
                }
                
                .dashboard-link {
                    cursor: pointer;
                    transition: all 0.2s ease;
                }
                
                .dashboard-link:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                }
            </style>
        `;
        
        document.head.insertAdjacentHTML('beforeend', styles);
    },
    
    setupGlobalShortcuts: function() {
        document.addEventListener('keydown', (e) => {
            // Ctrl + Shift + M: لوحة المدير
            if (e.ctrlKey && e.shiftKey && e.key === 'M') {
                e.preventDefault();
                this.navigation.toManager();
            }
            
            // Ctrl + Shift + T: لوحة المشرف الفني
            if (e.ctrlKey && e.shiftKey && e.key === 'T') {
                e.preventDefault();
                this.navigation.toTechnical();
            }
        });
    }
};

// تهيئة تلقائية
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => DashboardIntegration.init());
} else {
    DashboardIntegration.init();
}

// تصدير للوصول العام
window.DashboardIntegration = DashboardIntegration;

console.log('✅ Dashboard Integration System Loaded Successfully!');
