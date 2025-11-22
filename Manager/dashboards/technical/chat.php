<?php
/**
 * Technical Dashboard - Chat
 * الدردشة
 */
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-slate-800 mb-2">الدردشة</h1>
    <p class="text-slate-600">التواصل مع المدربين والطلاب</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 h-[600px]">
    <!-- Conversations List -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="p-4 border-b border-slate-200">
            <div class="relative">
                <i data-lucide="search" class="w-5 h-5 absolute right-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" placeholder="بحث..." 
                    class="w-full pr-10 pl-4 py-2 border border-slate-300 rounded-lg text-sm">
            </div>
        </div>
        <div id="conversationsList" class="overflow-y-auto h-[500px]">
            <div class="text-center py-8 text-slate-500">
                <i data-lucide="loader" class="w-8 h-8 mx-auto mb-2 animate-spin"></i>
                <p>جاري التحميل...</p>
            </div>
        </div>
    </div>
    
    <!-- Chat Messages -->
    <div class="lg:col-span-2 bg-white rounded-xl shadow-lg flex flex-col">
        <div class="p-4 border-b border-slate-200">
            <h3 class="text-lg font-bold text-slate-800" id="chatHeader">اختر محادثة</h3>
        </div>
        <div id="messagesContainer" class="flex-1 p-4 overflow-y-auto">
            <div class="text-center py-16 text-slate-500">
                <i data-lucide="message-circle" class="w-16 h-16 mx-auto mb-4 text-slate-300"></i>
                <p>اختر محادثة لبدء الدردشة</p>
            </div>
        </div>
        <div class="p-4 border-t border-slate-200">
            <div class="flex gap-2">
                <input type="text" id="messageInput" placeholder="اكتب رسالة..." 
                    class="flex-1 px-4 py-2 border border-slate-300 rounded-lg" disabled>
                <button id="sendBtn" class="px-6 py-2 bg-sky-600 text-white rounded-lg hover:bg-sky-700 disabled:bg-slate-300" disabled>
                    <i data-lucide="send" class="w-5 h-5"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentConversation = null;

async function loadConversations() {
    try {
        const response = await DashboardIntegration.api.chat.getConversations();
        const container = document.getElementById('conversationsList');
        
        if (response.success && response.data && response.data.length > 0) {
            container.innerHTML = response.data.map(conv => `
                <div onclick="openConversation(${conv.contact_id}, '${conv.contact_name}')"
                    class="p-4 border-b border-slate-200 hover:bg-slate-50 cursor-pointer transition-colors">
                    <div class="flex items-center gap-3">
                        <img src="${conv.contact_photo || '<?php echo $platformBaseUrl; ?>/photos/default-avatar.png'}" 
                             class="w-12 h-12 rounded-full object-cover">
                        <div class="flex-1">
                            <h4 class="font-semibold text-slate-800">${conv.contact_name}</h4>
                            <p class="text-sm text-slate-500 truncate">${conv.last_message || 'لا توجد رسائل'}</p>
                        </div>
                        ${conv.unread_count > 0 ? `
                        <span class="bg-sky-600 text-white text-xs font-bold px-2 py-1 rounded-full">${conv.unread_count}</span>
                        ` : ''}
                    </div>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<div class="text-center py-8 text-slate-500"><p>لا توجد محادثات</p></div>';
        }
    } catch (error) {
        console.error('Failed to load conversations:', error);
    }
    lucide.createIcons();
}

async function openConversation(contactId, contactName) {
    currentConversation = contactId;
    document.getElementById('chatHeader').textContent = contactName;
    document.getElementById('messageInput').disabled = false;
    document.getElementById('sendBtn').disabled = false;
    
    try {
        const response = await DashboardIntegration.api.chat.getMessages(contactId);
        const container = document.getElementById('messagesContainer');
        
        if (response.success && response.data) {
            container.innerHTML = response.data.map(msg => `
                <div class="mb-4 flex ${msg.sender_id == window.CURRENT_USER.id ? 'justify-start' : 'justify-end'}">
                    <div class="max-w-[70%] ${msg.sender_id == window.CURRENT_USER.id ? 'bg-sky-100' : 'bg-slate-100'} rounded-lg p-3">
                        <p class="text-slate-800">${msg.message}</p>
                        <p class="text-xs text-slate-500 mt-1">${new Date(msg.created_at).toLocaleTimeString('ar-EG')}</p>
                    </div>
                </div>
            `).join('');
            container.scrollTop = container.scrollHeight;
        }
    } catch (error) {
        console.error('Failed to load messages:', error);
    }
}

document.getElementById('sendBtn')?.addEventListener('click', async function() {
    const input = document.getElementById('messageInput');
    const message = input.value.trim();
    
    if (!message || !currentConversation) return;
    
    try {
        const response = await DashboardIntegration.api.chat.sendMessage(currentConversation, message);
        if (response.success) {
            input.value = '';
            openConversation(currentConversation, document.getElementById('chatHeader').textContent);
        }
    } catch (error) {
        DashboardIntegration.ui.showToast('فشل إرسال الرسالة', 'error');
    }
});

document.addEventListener('DOMContentLoaded', () => {
    loadConversations();
    lucide.createIcons();
});
</script>
