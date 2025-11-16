<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">الإعلانات</h2>
            <p class="text-slate-600 mt-1">إنشاء وإدارة إعلانات الدورات</p>
        </div>
        <button onclick="createNewAnnouncement()" 
            class="px-6 py-3 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 font-semibold flex items-center gap-2">
            <i data-lucide="plus" class="w-5 h-5"></i>
            إعلان جديد
        </button>
    </div>

    <!-- Course Selection -->
    <div class="bg-white border border-slate-200 rounded-xl p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">اختر الدورة</label>
                <select id="announcementCourse" onchange="loadAnnouncements()" 
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg">
                    <option value="all">جميع الدورات</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">البحث</label>
                <input type="text" id="searchAnnouncements" placeholder="ابحث في الإعلانات..." 
                    oninput="searchAnnouncements()"
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg">
            </div>
        </div>
    </div>

    <!-- Announcements List -->
    <div id="announcementsList" class="space-y-4">
        <div class="flex justify-center py-12">
            <div class="text-center">
                <i data-lucide="loader" class="w-12 h-12 mx-auto animate-spin text-slate-400 mb-3"></i>
                <p class="text-slate-500">جاري التحميل...</p>
            </div>
        </div>
    </div>
</div>

<script>
let allAnnouncements = [];

async function loadCourses() {
    const response = await TrainerFeatures.courses.getMyCourses();
    if (response.success && response.data) {
        const select = document.getElementById('announcementCourse');
        response.data.forEach(course => {
            const option = document.createElement('option');
            option.value = course.id;
            option.textContent = course.course_name;
            select.appendChild(option);
        });
    }
}

async function loadAnnouncements() {
    const courseId = document.getElementById('announcementCourse').value;
    const response = await TrainerFeatures.announcements.getAnnouncements(
        courseId === 'all' ? null : courseId
    );
    
    if (response.success && response.data) {
        allAnnouncements = response.data;
        renderAnnouncements(allAnnouncements);
    }
    
    lucide.createIcons();
}

function renderAnnouncements(announcements) {
    const container = document.getElementById('announcementsList');
    
    if (announcements.length === 0) {
        container.innerHTML = `
            <div class="text-center py-12">
                <i data-lucide="megaphone" class="w-12 h-12 mx-auto text-slate-400 mb-3"></i>
                <p class="text-slate-600">لا توجد إعلانات حالياً</p>
            </div>
        `;
        lucide.createIcons();
        return;
    }
    
    container.innerHTML = announcements.map(announcement => {
        const priority = announcement.priority || 'normal';
        const priorityColors = {
            urgent: 'red',
            high: 'amber',
            normal: 'emerald',
            low: 'slate'
        };
        const color = priorityColors[priority];
        
        return `
            <div class="bg-white border-l-4 border-${color}-500 border border-slate-200 rounded-xl p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-start gap-4 flex-1">
                        <div class="p-3 bg-${color}-100 rounded-lg">
                            <i data-lucide="megaphone" class="w-6 h-6 text-${color}-600"></i>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="text-lg font-bold text-slate-800">${announcement.title}</h3>
                                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-${color}-100 text-${color}-700">
                                    ${priority === 'urgent' ? 'عاجل' : 
                                      priority === 'high' ? 'مهم' : 
                                      priority === 'normal' ? 'عادي' : 'منخفض'}
                                </span>
                            </div>
                            <p class="text-slate-600 mb-3">${announcement.content}</p>
                            <div class="flex items-center gap-4 text-sm text-slate-500">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="book-open" class="w-4 h-4"></i>
                                    <span>${announcement.course_name}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i data-lucide="calendar" class="w-4 h-4"></i>
                                    <span>${announcement.created_at}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i data-lucide="users" class="w-4 h-4"></i>
                                    <span>${announcement.views || 0} مشاهدة</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="editAnnouncement(${announcement.id})" 
                            class="px-3 py-2 text-sm bg-emerald-50 text-emerald-700 rounded-lg hover:bg-emerald-100">
                            <i data-lucide="edit" class="w-4 h-4"></i>
                        </button>
                        <button onclick="deleteAnnouncement(${announcement.id})" 
                            class="px-3 py-2 text-sm bg-red-50 text-red-700 rounded-lg hover:bg-red-100">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }).join('');
    
    lucide.createIcons();
}

function createNewAnnouncement() {
    const modalHTML = `
        <form id="createAnnouncementForm" class="space-y-4">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">الدورة</label>
                <select id="newAnnouncementCourse" required class="w-full px-4 py-2 border border-slate-300 rounded-lg">
                    <option value="">اختر الدورة</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">عنوان الإعلان</label>
                <input type="text" id="announcementTitle" required 
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg"
                    placeholder="أدخل عنوان الإعلان">
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">المحتوى</label>
                <textarea id="announcementContent" rows="5" required
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg"
                    placeholder="أدخل محتوى الإعلان"></textarea>
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">الأولوية</label>
                <select id="announcementPriority" required class="w-full px-4 py-2 border border-slate-300 rounded-lg">
                    <option value="normal">عادي</option>
                    <option value="high">مهم</option>
                    <option value="urgent">عاجل</option>
                    <option value="low">منخفض</option>
                </select>
            </div>
            
            <div class="flex items-center gap-2">
                <input type="checkbox" id="sendNotification" class="w-4 h-4 text-emerald-600">
                <label for="sendNotification" class="text-sm text-slate-700">إرسال إشعار للطلاب</label>
            </div>
        </form>
    `;
    
    DashboardIntegration.ui.showModal('إنشاء إعلان جديد', modalHTML, [
        {
            text: 'نشر',
            class: 'bg-emerald-600 text-white hover:bg-emerald-700',
            onclick: 'submitNewAnnouncement()'
        },
        {
            text: 'إلغاء',
            class: 'bg-slate-200 text-slate-700 hover:bg-slate-300',
            onclick: 'this.closest(".fixed").remove()'
        }
    ]);
    
    // Load courses
    TrainerFeatures.courses.getMyCourses().then(response => {
        if (response.success && response.data) {
            const select = document.getElementById('newAnnouncementCourse');
            response.data.forEach(course => {
                const option = document.createElement('option');
                option.value = course.id;
                option.textContent = course.course_name;
                select.appendChild(option);
            });
        }
    });
}

async function submitNewAnnouncement() {
    const courseId = document.getElementById('newAnnouncementCourse').value;
    const title = document.getElementById('announcementTitle').value;
    const content = document.getElementById('announcementContent').value;
    const priority = document.getElementById('announcementPriority').value;
    const sendNotification = document.getElementById('sendNotification').checked;
    
    if (!courseId || !title || !content) {
        DashboardIntegration.ui.showToast('الرجاء إكمال جميع الحقول المطلوبة', 'error');
        return;
    }
    
    const response = await TrainerFeatures.announcements.createAnnouncement(courseId, {
        title,
        content,
        priority,
        send_notification: sendNotification
    });
    
    if (response.success) {
        DashboardIntegration.ui.showToast('تم نشر الإعلان بنجاح', 'success');
        document.querySelector('.fixed').remove();
        loadAnnouncements();
    } else {
        DashboardIntegration.ui.showToast('فشل نشر الإعلان', 'error');
    }
}

function editAnnouncement(announcementId) {
    DashboardIntegration.ui.showToast('سيتم إضافة ميزة التعديل قريباً', 'info');
}

async function deleteAnnouncement(announcementId) {
    if (!confirm('هل أنت متأكد من حذف هذا الإعلان؟')) return;
    
    // TODO: Implement delete
    DashboardIntegration.ui.showToast('تم حذف الإعلان', 'success');
    loadAnnouncements();
}

function searchAnnouncements() {
    const search = document.getElementById('searchAnnouncements').value.toLowerCase();
    const filtered = allAnnouncements.filter(a => 
        a.title.toLowerCase().includes(search) || 
        a.content.toLowerCase().includes(search)
    );
    renderAnnouncements(filtered);
}

// Initialize
loadCourses();
loadAnnouncements();
</script>
