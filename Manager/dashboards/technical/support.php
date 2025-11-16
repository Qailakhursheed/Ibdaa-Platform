<?php
/**
 * Technical Dashboard - Support Tickets
 * إدارة تذاكر الدعم الفني
 */
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-slate-800 mb-2">الدعم الفني</h1>
    <p class="text-slate-600">إدارة ومتابعة تذاكر الدعم</p>
</div>

<!-- Tabs -->
<div class="bg-white rounded-xl shadow-lg mb-6">
    <div class="flex border-b border-slate-200">
        <button onclick="switchTab('pending')" 
            class="tab-btn active px-6 py-4 font-semibold text-slate-700 border-b-2 border-sky-600" data-tab="pending">
            المعلقة
            <span class="mr-2 bg-red-100 text-red-700 px-2 py-1 rounded-full text-xs" id="pendingCount">0</span>
        </button>
        <button onclick="switchTab('in-progress')" 
            class="tab-btn px-6 py-4 font-semibold text-slate-500 hover:text-slate-700 border-b-2 border-transparent" data-tab="in-progress">
            قيد المعالجة
        </button>
        <button onclick="switchTab('resolved')" 
            class="tab-btn px-6 py-4 font-semibold text-slate-500 hover:text-slate-700 border-b-2 border-transparent" data-tab="resolved">
            المحلولة
        </button>
        <button onclick="switchTab('closed')" 
            class="tab-btn px-6 py-4 font-semibold text-slate-500 hover:text-slate-700 border-b-2 border-transparent" data-tab="closed">
            المغلقة
        </button>
    </div>
</div>

<!-- Tickets Container -->
<div id="ticketsContainer" class="space-y-4">
    <div class="text-center py-16">
        <i data-lucide="loader" class="w-12 h-12 mx-auto text-slate-400 mb-4 animate-spin"></i>
        <p class="text-slate-500">جاري تحميل التذاكر...</p>
    </div>
</div>

<script>
let currentTab = 'pending';

async function switchTab(tab) {
    currentTab = tab;
    
    // Update UI
    document.querySelectorAll('.tab-btn').forEach(btn => {
        if (btn.dataset.tab === tab) {
            btn.classList.add('active', 'text-slate-700', 'border-sky-600');
            btn.classList.remove('text-slate-500', 'border-transparent');
        } else {
            btn.classList.remove('active', 'text-slate-700', 'border-sky-600');
            btn.classList.add('text-slate-500', 'border-transparent');
        }
    });
    
    // Load tickets
    await loadTickets(tab);
}

async function loadTickets(status = 'pending') {
    const container = document.getElementById('ticketsContainer');
    container.innerHTML = '<div class="text-center py-8"><i data-lucide="loader" class="w-8 h-8 mx-auto animate-spin text-slate-400"></i></div>';
    lucide.createIcons();
    
    try {
        const response = await TechnicalFeatures.support.getAll(status);
        
        if (response.success && response.data && response.data.length > 0) {
            container.innerHTML = response.data.map(ticket => `
                <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="text-xl font-bold text-slate-800">${ticket.subject}</h3>
                                <span class="px-3 py-1 rounded-full text-xs font-bold
                                    ${ticket.priority === 'high' ? 'bg-red-100 text-red-700' : 
                                      ticket.priority === 'medium' ? 'bg-amber-100 text-amber-700' : 
                                      'bg-blue-100 text-blue-700'}">
                                    ${ticket.priority === 'high' ? 'عالي' : ticket.priority === 'medium' ? 'متوسط' : 'منخفض'}
                                </span>
                            </div>
                            <p class="text-slate-600 mb-2">${ticket.message}</p>
                            <div class="flex items-center gap-4 text-sm text-slate-500">
                                <span class="flex items-center gap-1">
                                    <i data-lucide="user" class="w-4 h-4"></i>
                                    ${ticket.user_name}
                                </span>
                                <span class="flex items-center gap-1">
                                    <i data-lucide="calendar" class="w-4 h-4"></i>
                                    ${new Date(ticket.created_at).toLocaleDateString('ar-EG')}
                                </span>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="viewTicket(${ticket.ticket_id})" 
                                class="p-2 bg-sky-50 text-sky-600 rounded-lg hover:bg-sky-100 transition-colors">
                                <i data-lucide="eye" class="w-5 h-5"></i>
                            </button>
                            ${status === 'pending' ? `
                            <button onclick="respondToTicket(${ticket.ticket_id})" 
                                class="p-2 bg-emerald-50 text-emerald-600 rounded-lg hover:bg-emerald-100 transition-colors">
                                <i data-lucide="message-circle" class="w-5 h-5"></i>
                            </button>
                            <button onclick="closeTicket(${ticket.ticket_id})" 
                                class="p-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors">
                                <i data-lucide="x" class="w-5 h-5"></i>
                            </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `).join('');
            
            // Update count
            if (status === 'pending') {
                document.getElementById('pendingCount').textContent = response.data.length;
            }
        } else {
            container.innerHTML = `
                <div class="bg-white rounded-xl shadow-lg p-12 text-center">
                    <i data-lucide="inbox" class="w-16 h-16 mx-auto text-slate-300 mb-4"></i>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">لا توجد تذاكر</h3>
                    <p class="text-slate-500">لا توجد تذاكر ${status === 'pending' ? 'معلقة' : status === 'resolved' ? 'محلولة' : 'في هذه الفئة'}</p>
                </div>
            `;
        }
    } catch (error) {
        container.innerHTML = `
            <div class="bg-red-50 rounded-xl p-8 text-center">
                <i data-lucide="alert-circle" class="w-12 h-12 mx-auto text-red-500 mb-4"></i>
                <p class="text-red-700 font-semibold">فشل تحميل التذاكر</p>
            </div>
        `;
    }
    
    lucide.createIcons();
}

function viewTicket(ticketId) {
    window.location.href = `?page=support&id=${ticketId}`;
}

async function respondToTicket(ticketId) {
    const message = prompt('أدخل ردك على التذكرة:');
    if (!message) return;
    
    const response = await TechnicalFeatures.support.respond(ticketId, message);
    if (response.success) {
        DashboardIntegration.ui.showToast('تم إرسال الرد بنجاح', 'success');
        loadTickets(currentTab);
    } else {
        DashboardIntegration.ui.showToast('فشل إرسال الرد', 'error');
    }
}

async function closeTicket(ticketId) {
    if (!confirm('هل أنت متأكد من إغلاق هذه التذكرة؟')) return;
    
    const response = await TechnicalFeatures.support.close(ticketId);
    if (response.success) {
        DashboardIntegration.ui.showToast('تم إغلاق التذكرة', 'success');
        loadTickets(currentTab);
    } else {
        DashboardIntegration.ui.showToast('فشل إغلاق التذكرة', 'error');
    }
}

// Load on page load
document.addEventListener('DOMContentLoaded', () => {
    loadTickets('pending');
});
</script>
