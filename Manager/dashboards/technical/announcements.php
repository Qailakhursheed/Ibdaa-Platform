<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="megaphone" class="w-6 h-6 text-sky-600"></i>
                إدارة الإعلانات
            </h2>
            <p class="text-slate-600 mt-1">إنشاء وإدارة الإعلانات للطلاب والمدربين</p>
        </div>
        <button onclick="openCreateAnnouncement()" class="flex items-center gap-2 px-4 py-2 bg-sky-600 text-white rounded-lg hover:bg-sky-700 transition-colors">
            <i data-lucide="plus" class="w-5 h-5"></i>
            <span>إعلان جديد</span>
        </button>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="megaphone" class="w-8 h-8 text-sky-600"></i>
                <span class="text-2xl font-bold text-slate-800" id="totalAnnouncements">0</span>
            </div>
            <p class="text-sm text-slate-600">إجمالي الإعلانات</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="eye" class="w-8 h-8 text-emerald-600"></i>
                <span class="text-2xl font-bold text-slate-800" id="totalViews">0</span>
            </div>
            <p class="text-sm text-slate-600">إجمالي المشاهدات</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="calendar" class="w-8 h-8 text-purple-600"></i>
                <span class="text-2xl font-bold text-slate-800" id="thisMonth">0</span>
            </div>
            <p class="text-sm text-slate-600">هذا الشهر</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="trending-up" class="w-8 h-8 text-amber-600"></i>
                <span class="text-2xl font-bold text-slate-800" id="avgViews">0</span>
            </div>
            <p class="text-sm text-slate-600">متوسط المشاهدات</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white border border-slate-200 rounded-xl p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">البحث</label>
                <div class="relative">
                    <i data-lucide="search" class="w-5 h-5 absolute right-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" id="searchAnnouncements" placeholder="ابحث في العنوان أو المحتوى..."
                        class="w-full pr-10 pl-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">نوع الإعلان</label>
                <select id="filterType" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500">
                    <option value="all">جميع الأنواع</option>
                    <option value="general">عام</option>
                    <option value="urgent">عاجل</option>
                    <option value="info">معلومة</option>
                    <option value="event">حدث</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">الفئة المستهدفة</label>
                <select id="filterTarget" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500">
                    <option value="all">الجميع</option>
                    <option value="students">الطلاب</option>
                    <option value="trainers">المدربين</option>
                    <option value="both">الطلاب والمدربين</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Announcements Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="announcementsGrid">
        <div class="col-span-full flex items-center justify-center py-12">
            <i data-lucide="loader" class="w-8 h-8 animate-spin text-slate-400 mb-3"></i>
            <p class="text-slate-500 mr-3">جاري تحميل الإعلانات...</p>
        </div>
    </div>
</div>

<!-- Create/Edit Announcement Modal -->
<div id="announcementModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between">
            <h3 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="megaphone" class="w-6 h-6 text-sky-600"></i>
                <span id="modalTitle">إعلان جديد</span>
            </h3>
            <button onclick="closeAnnouncementModal()" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
                <i data-lucide="x" class="w-5 h-5 text-slate-600"></i>
            </button>
        </div>
        
        <form id="announcementForm" class="p-6 space-y-6">
            <input type="hidden" name="announcement_id" id="announcementId">
            
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">عنوان الإعلان *</label>
                <input type="text" name="title" id="announcementTitle" required
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500"
                    placeholder="مثال: بدء التسجيل للدورة الجديدة">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">المحتوى *</label>
                <textarea name="content" id="announcementContent" required rows="6"
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500"
                    placeholder="اكتب محتوى الإعلان هنا..."></textarea>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">نوع الإعلان</label>
                    <select name="type" id="announcementType" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500">
                        <option value="general">عام</option>
                        <option value="urgent">عاجل</option>
                        <option value="info">معلومة</option>
                        <option value="event">حدث</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">الفئة المستهدفة</label>
                    <select name="target_audience" id="announcementTarget" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500">
                        <option value="all">الجميع</option>
                        <option value="students">الطلاب فقط</option>
                        <option value="trainers">المدربين فقط</option>
                        <option value="both">الطلاب والمدربين</option>
                    </select>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">تاريخ البداية</label>
                    <input type="date" name="start_date" id="startDate"
                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">تاريخ الانتهاء</label>
                    <input type="date" name="end_date" id="endDate"
                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500">
                </div>
            </div>
            
            <div class="space-y-3">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="send_notification" checked class="rounded border-slate-300">
                    <span class="text-sm text-slate-700">إرسال إشعار فوري للمستهدفين</span>
                </label>
                
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="send_email" class="rounded border-slate-300">
                    <span class="text-sm text-slate-700">إرسال عبر البريد الإلكتروني</span>
                </label>
                
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="pinned" class="rounded border-slate-300">
                    <span class="text-sm text-slate-700">تثبيت الإعلان في الأعلى</span>
                </label>
            </div>
            
            <div class="flex gap-3 pt-4 border-t border-slate-200">
                <button type="submit" class="flex-1 bg-sky-600 text-white py-3 rounded-lg hover:bg-sky-700 transition-colors font-semibold">
                    نشر الإعلان
                </button>
                <button type="button" onclick="closeAnnouncementModal()" class="px-6 py-3 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<!-- View Announcement Modal -->
<div id="viewAnnouncementModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between">
            <h3 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="eye" class="w-6 h-6 text-sky-600"></i>
                تفاصيل الإعلان
            </h3>
            <button onclick="closeViewAnnouncement()" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
                <i data-lucide="x" class="w-5 h-5 text-slate-600"></i>
            </button>
        </div>
        
        <div id="viewAnnouncementContent" class="p-6">
            <!-- Content will be loaded dynamically -->
        </div>
    </div>
</div>

<script>
let announcementsData = [];

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadAnnouncements();
    
    // Event listeners
    document.getElementById('searchAnnouncements').addEventListener('input', debounce(loadAnnouncements, 500));
    document.getElementById('filterType').addEventListener('change', loadAnnouncements);
    document.getElementById('filterTarget').addEventListener('change', loadAnnouncements);
    document.getElementById('announcementForm').addEventListener('submit', handleSubmitAnnouncement);
});

// Load announcements
async function loadAnnouncements() {
    const search = document.getElementById('searchAnnouncements').value;
    const type = document.getElementById('filterType').value;
    const target = document.getElementById('filterTarget').value;
    
    try {
        const response = await fetch(`<?php echo $managerBaseUrl; ?>/api/manage_announcements_ai.php?action=list&search=${search}&type=${type}&target=${target}`);
        const result = await response.json();
        
        if (result.success) {
            announcementsData = result.data;
            updateStatistics(result);
            renderAnnouncements(result.data);
        }
    } catch (error) {
        console.error('Error loading announcements:', error);
        showNotification('حدث خطأ أثناء تحميل الإعلانات', 'error');
    }
    
    lucide.createIcons();
}

// Update statistics
function updateStatistics(data) {
    document.getElementById('totalAnnouncements').textContent = data.count || 0;
    
    const totalViews = data.data.reduce((sum, ann) => sum + (ann.views || 0), 0);
    document.getElementById('totalViews').textContent = totalViews;
    
    const thisMonth = data.data.filter(ann => {
        const date = new Date(ann.created_at);
        const now = new Date();
        return date.getMonth() === now.getMonth() && date.getFullYear() === now.getFullYear();
    }).length;
    document.getElementById('thisMonth').textContent = thisMonth;
    
    const avgViews = data.count > 0 ? Math.round(totalViews / data.count) : 0;
    document.getElementById('avgViews').textContent = avgViews;
}

// Render announcements
function renderAnnouncements(announcements) {
    const grid = document.getElementById('announcementsGrid');
    
    if (announcements.length === 0) {
        grid.innerHTML = `
            <div class="col-span-full text-center py-12">
                <i data-lucide="megaphone" class="w-16 h-16 mx-auto text-slate-300 mb-4"></i>
                <p class="text-slate-600">لا توجد إعلانات</p>
            </div>
        `;
        lucide.createIcons();
        return;
    }
    
    grid.innerHTML = announcements.map(announcement => `
        <div class="bg-white border border-slate-200 rounded-xl p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center gap-2">
                    ${getTypeBadge(announcement.type)}
                    ${announcement.pinned ? '<span class="px-2 py-1 bg-amber-100 text-amber-700 text-xs font-semibold rounded-full">مثبت</span>' : ''}
                </div>
                <div class="flex gap-2">
                    <button onclick="viewAnnouncement(${announcement.id})" class="p-2 hover:bg-sky-50 rounded-lg" title="عرض">
                        <i data-lucide="eye" class="w-4 h-4 text-sky-600"></i>
                    </button>
                    <button onclick="editAnnouncement(${announcement.id})" class="p-2 hover:bg-amber-50 rounded-lg" title="تعديل">
                        <i data-lucide="edit" class="w-4 h-4 text-amber-600"></i>
                    </button>
                    <button onclick="deleteAnnouncement(${announcement.id})" class="p-2 hover:bg-red-50 rounded-lg" title="حذف">
                        <i data-lucide="trash-2" class="w-4 h-4 text-red-600"></i>
                    </button>
                </div>
            </div>
            
            <h3 class="text-lg font-bold text-slate-800 mb-2">${announcement.title}</h3>
            <p class="text-sm text-slate-600 mb-4 line-clamp-3">${announcement.content}</p>
            
            <div class="flex items-center justify-between pt-4 border-t border-slate-200">
                <div class="flex items-center gap-4 text-xs text-slate-500">
                    <span class="flex items-center gap-1">
                        <i data-lucide="eye" class="w-3 h-3"></i>
                        ${announcement.views || 0}
                    </span>
                    <span class="flex items-center gap-1">
                        <i data-lucide="users" class="w-3 h-3"></i>
                        ${getTargetLabel(announcement.target_audience)}
                    </span>
                </div>
                <span class="text-xs text-slate-500">${formatDate(announcement.created_at)}</span>
            </div>
        </div>
    `).join('');
    
    lucide.createIcons();
}

// Type badge
function getTypeBadge(type) {
    const badges = {
        general: '<span class="px-2 py-1 bg-sky-100 text-sky-700 text-xs font-semibold rounded-full">عام</span>',
        urgent: '<span class="px-2 py-1 bg-red-100 text-red-700 text-xs font-semibold rounded-full">عاجل</span>',
        info: '<span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs font-semibold rounded-full">معلومة</span>',
        event: '<span class="px-2 py-1 bg-purple-100 text-purple-700 text-xs font-semibold rounded-full">حدث</span>'
    };
    return badges[type] || badges.general;
}

// Target label
function getTargetLabel(target) {
    const labels = {
        all: 'الجميع',
        students: 'الطلاب',
        trainers: 'المدربين',
        both: 'الطلاب والمدربين'
    };
    return labels[target] || labels.all;
}

// Modal functions
function openCreateAnnouncement() {
    document.getElementById('modalTitle').textContent = 'إعلان جديد';
    document.getElementById('announcementForm').reset();
    document.getElementById('announcementId').value = '';
    document.getElementById('announcementModal').classList.remove('hidden');
    document.getElementById('announcementModal').classList.add('flex');
}

function closeAnnouncementModal() {
    document.getElementById('announcementModal').classList.add('hidden');
    document.getElementById('announcementModal').classList.remove('flex');
}

// Handle submit
async function handleSubmitAnnouncement(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const announcementId = document.getElementById('announcementId').value;
    const action = announcementId ? 'update' : 'create';
    
    try {
        const response = await fetch(`<?php echo $managerBaseUrl; ?>/api/manage_announcements_ai.php?action=${action}`, {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            showNotification(announcementId ? 'تم تحديث الإعلان بنجاح' : 'تم نشر الإعلان بنجاح', 'success');
            closeAnnouncementModal();
            loadAnnouncements();
        } else {
            showNotification(result.message || 'حدث خطأ', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('حدث خطأ أثناء حفظ الإعلان', 'error');
    }
}

// View announcement
async function viewAnnouncement(id) {
    try {
        const response = await fetch(`<?php echo $managerBaseUrl; ?>/api/manage_announcements_ai.php?action=get&id=${id}`);
        const result = await response.json();
        
        if (result.success) {
            const ann = result.data;
            document.getElementById('viewAnnouncementContent').innerHTML = `
                <div class="space-y-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-2xl font-bold text-slate-800 mb-2">${ann.title}</h3>
                            <div class="flex items-center gap-3 flex-wrap">
                                ${getTypeBadge(ann.type)}
                                ${ann.pinned ? '<span class="px-2 py-1 bg-amber-100 text-amber-700 text-xs font-semibold rounded-full">مثبت</span>' : ''}
                                <span class="text-sm text-slate-500">${formatDate(ann.created_at)}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="prose max-w-none">
                        <p class="text-slate-700 leading-relaxed whitespace-pre-wrap">${ann.content}</p>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 pt-4 border-t border-slate-200">
                        <div>
                            <p class="text-sm text-slate-600 mb-1">الفئة المستهدفة</p>
                            <p class="font-semibold text-slate-800">${getTargetLabel(ann.target_audience)}</p>
                        </div>
                        <div>
                            <p class="text-sm text-slate-600 mb-1">المشاهدات</p>
                            <p class="font-semibold text-slate-800">${ann.views || 0}</p>
                        </div>
                    </div>
                    
                    <div class="flex gap-3 pt-4 border-t border-slate-200">
                        <button onclick="editAnnouncement(${ann.id}); closeViewAnnouncement();" class="flex-1 bg-amber-600 text-white py-2 rounded-lg hover:bg-amber-700">
                            تعديل
                        </button>
                        <button onclick="deleteAnnouncement(${ann.id}); closeViewAnnouncement();" class="px-6 py-2 border border-red-300 text-red-600 rounded-lg hover:bg-red-50">
                            حذف
                        </button>
                    </div>
                </div>
            `;
            
            document.getElementById('viewAnnouncementModal').classList.remove('hidden');
            document.getElementById('viewAnnouncementModal').classList.add('flex');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('حدث خطأ', 'error');
    }
    
    lucide.createIcons();
}

function closeViewAnnouncement() {
    document.getElementById('viewAnnouncementModal').classList.add('hidden');
    document.getElementById('viewAnnouncementModal').classList.remove('flex');
}

// Edit announcement
async function editAnnouncement(id) {
    try {
        const response = await fetch(`<?php echo $managerBaseUrl; ?>/api/manage_announcements_ai.php?action=get&id=${id}`);
        const result = await response.json();
        
        if (result.success) {
            const ann = result.data;
            document.getElementById('modalTitle').textContent = 'تعديل الإعلان';
            document.getElementById('announcementId').value = ann.id;
            document.getElementById('announcementTitle').value = ann.title;
            document.getElementById('announcementContent').value = ann.content;
            document.getElementById('announcementType').value = ann.type || 'general';
            document.getElementById('announcementTarget').value = ann.target_audience || 'all';
            
            if (ann.start_date) document.getElementById('startDate').value = ann.start_date;
            if (ann.end_date) document.getElementById('endDate').value = ann.end_date;
            
            document.getElementById('announcementModal').classList.remove('hidden');
            document.getElementById('announcementModal').classList.add('flex');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('حدث خطأ', 'error');
    }
}

// Delete announcement
async function deleteAnnouncement(id) {
    if (!confirm('هل أنت متأكد من حذف هذا الإعلان؟')) {
        return;
    }
    
    try {
        const response = await fetch(`<?php echo $managerBaseUrl; ?>/api/manage_announcements_ai.php?action=delete&id=${id}`, {
            method: 'DELETE'
        });
        const result = await response.json();
        
        if (result.success) {
            showNotification('تم حذف الإعلان بنجاح', 'success');
            loadAnnouncements();
        } else {
            showNotification(result.message || 'حدث خطأ', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('حدث خطأ', 'error');
    }
}

// Utility functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('ar-EG', { year: 'numeric', month: 'long', day: 'numeric' });
}

function showNotification(message, type) {
    if (window.DashboardIntegration && window.DashboardIntegration.showNotification) {
        DashboardIntegration.showNotification(message, type);
    } else {
        alert(message);
    }
}
</script>
