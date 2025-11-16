/**
 * نظام التكامل بين الدعم الفني والـ Dashboard
 */

// تعريف TechnicalFeatures إذا لم يكن موجوداً
if (typeof TechnicalFeatures === 'undefined') {
    window.TechnicalFeatures = {};
}

// نظام الدعم الفني
TechnicalFeatures.support = {
    apiUrl: '../api/support_api.php',

    /**
     * جلب جميع التذاكر حسب الحالة
     */
    async getAll(status = 'pending') {
        try {
            const response = await fetch(`${this.apiUrl}?action=getAll&status=${status}`);
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'فشل جلب التذاكر');
            }
            
            return data;
        } catch (error) {
            console.error('خطأ في جلب التذاكر:', error);
            return { success: false, error: error.message, data: [] };
        }
    },

    /**
     * جلب تذكرة واحدة مع الردود
     */
    async get(ticketId) {
        try {
            const response = await fetch(`${this.apiUrl}?action=get&id=${ticketId}`);
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'فشل جلب التذكرة');
            }
            
            return data;
        } catch (error) {
            console.error('خطأ في جلب التذكرة:', error);
            return { success: false, error: error.message };
        }
    },

    /**
     * الرد على تذكرة
     */
    async respond(ticketId, message, userName = 'فريق الدعم الفني') {
        try {
            const formData = new FormData();
            formData.append('action', 'respond');
            formData.append('ticket_id', ticketId);
            formData.append('message', message);
            formData.append('user_name', userName);
            formData.append('user_type', 'staff');

            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'فشل إرسال الرد');
            }
            
            return data;
        } catch (error) {
            console.error('خطأ في إرسال الرد:', error);
            return { success: false, error: error.message };
        }
    },

    /**
     * تحديث حالة التذكرة
     */
    async updateStatus(ticketId, status) {
        try {
            const formData = new FormData();
            formData.append('action', 'updateStatus');
            formData.append('ticket_id', ticketId);
            formData.append('status', status);

            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'فشل تحديث الحالة');
            }
            
            return data;
        } catch (error) {
            console.error('خطأ في تحديث الحالة:', error);
            return { success: false, error: error.message };
        }
    },

    /**
     * إغلاق تذكرة
     */
    async close(ticketId) {
        try {
            const formData = new FormData();
            formData.append('action', 'close');
            formData.append('ticket_id', ticketId);

            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'فشل إغلاق التذكرة');
            }
            
            return data;
        } catch (error) {
            console.error('خطأ في إغلاق التذكرة:', error);
            return { success: false, error: error.message };
        }
    },

    /**
     * جلب الإحصائيات
     */
    async getStats() {
        try {
            const response = await fetch(`${this.apiUrl}?action=stats`);
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'فشل جلب الإحصائيات');
            }
            
            return data;
        } catch (error) {
            console.error('خطأ في جلب الإحصائيات:', error);
            return { success: false, error: error.message, data: {} };
        }
    },

    /**
     * البحث في التذاكر
     */
    async search(query) {
        try {
            const response = await fetch(`${this.apiUrl}?action=search&query=${encodeURIComponent(query)}`);
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'فشل البحث');
            }
            
            return data;
        } catch (error) {
            console.error('خطأ في البحث:', error);
            return { success: false, error: error.message, data: [] };
        }
    },

    /**
     * حذف تذكرة
     */
    async delete(ticketId) {
        try {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('ticket_id', ticketId);

            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'فشل حذف التذكرة');
            }
            
            return data;
        } catch (error) {
            console.error('خطأ في حذف التذكرة:', error);
            return { success: false, error: error.message };
        }
    },

    /**
     * تنسيق التاريخ
     */
    formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diff = now - date;
        const seconds = Math.floor(diff / 1000);
        const minutes = Math.floor(seconds / 60);
        const hours = Math.floor(minutes / 60);
        const days = Math.floor(hours / 24);

        if (days > 7) {
            return date.toLocaleDateString('ar-SA', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        } else if (days > 0) {
            return `منذ ${days} ${days === 1 ? 'يوم' : 'أيام'}`;
        } else if (hours > 0) {
            return `منذ ${hours} ${hours === 1 ? 'ساعة' : 'ساعات'}`;
        } else if (minutes > 0) {
            return `منذ ${minutes} ${minutes === 1 ? 'دقيقة' : 'دقائق'}`;
        } else {
            return 'الآن';
        }
    },

    /**
     * الحصول على لون الأولوية
     */
    getPriorityColor(priority) {
        const colors = {
            'high': 'bg-red-100 text-red-800 border-red-200',
            'medium': 'bg-yellow-100 text-yellow-800 border-yellow-200',
            'low': 'bg-green-100 text-green-800 border-green-200'
        };
        return colors[priority] || colors['medium'];
    },

    /**
     * الحصول على نص الأولوية
     */
    getPriorityText(priority) {
        const texts = {
            'high': 'عالية',
            'medium': 'متوسطة',
            'low': 'منخفضة'
        };
        return texts[priority] || 'متوسطة';
    },

    /**
     * الحصول على لون الحالة
     */
    getStatusColor(status) {
        const colors = {
            'pending': 'bg-orange-100 text-orange-800',
            'in-progress': 'bg-blue-100 text-blue-800',
            'resolved': 'bg-green-100 text-green-800',
            'closed': 'bg-gray-100 text-gray-800'
        };
        return colors[status] || colors['pending'];
    },

    /**
     * الحصول على نص الحالة
     */
    getStatusText(status) {
        const texts = {
            'pending': 'معلقة',
            'in-progress': 'قيد المعالجة',
            'resolved': 'محلولة',
            'closed': 'مغلقة'
        };
        return texts[status] || 'معلقة';
    }
};

// دالة لعرض الإشعارات
function showNotification(message, type = 'success') {
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        info: 'bg-blue-500',
        warning: 'bg-yellow-500'
    };

    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-fade-in`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('animate-fade-out');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// دالة لتأكيد الإجراء
function confirmAction(message) {
    return new Promise((resolve) => {
        const result = confirm(message);
        resolve(result);
    });
}

console.log('✅ نظام تكامل الدعم الفني تم تحميله بنجاح');
