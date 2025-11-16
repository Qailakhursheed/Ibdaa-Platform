<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">المحادثات</h2>
            <p class="text-slate-600 mt-1">تواصل مع المدربين والزملاء</p>
        </div>
        <button onclick="startNewChat()" 
            class="px-6 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-colors font-semibold">
            <i data-lucide="message-circle-plus" class="w-4 h-4 inline"></i>
            محادثة جديدة
        </button>
    </div>

    <!-- Chat Interface -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Conversations List -->
        <div class="lg:col-span-1">
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                <div class="p-4 border-b border-slate-200">
                    <div class="relative">
                        <i data-lucide="search" class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                        <input type="text" 
                            placeholder="بحث في المحادثات..." 
                            class="w-full pr-10 pl-4 py-2 border border-slate-300 rounded-lg text-sm">
                    </div>
                </div>

                <div id="conversationsList" class="divide-y divide-slate-200 overflow-y-auto" style="max-height: 600px;">
                    <div class="p-8 text-center">
                        <i data-lucide="loader" class="w-8 h-8 mx-auto animate-spin text-slate-400 mb-2"></i>
                        <p class="text-slate-500 text-sm">جاري التحميل...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chat Window -->
        <div class="lg:col-span-2">
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden flex flex-col" style="height: 680px;">
                <!-- Chat Header -->
                <div id="chatHeader" class="p-4 border-b border-slate-200 bg-slate-50">
                    <div class="text-center text-slate-500 py-8">
                        <i data-lucide="message-circle" class="w-12 h-12 mx-auto mb-3 text-slate-400"></i>
                        <p>اختر محادثة للبدء</p>
                    </div>
                </div>

                <!-- Messages Container -->
                <div id="messagesContainer" class="flex-1 p-6 overflow-y-auto bg-slate-50">
                    <!-- Messages will be loaded here -->
                </div>

                <!-- Message Input -->
                <div id="messageInput" class="p-4 border-t border-slate-200 bg-white" style="display: none;">
                    <div class="flex gap-3">
                        <button class="p-2 text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
                            <i data-lucide="paperclip" class="w-5 h-5"></i>
                        </button>
                        <input type="text" 
                            id="messageText"
                            placeholder="اكتب رسالتك هنا..." 
                            class="flex-1 px-4 py-2 border border-slate-300 rounded-lg"
                            onkeypress="handleMessageKeyPress(event)">
                        <button onclick="sendMessage()" 
                            class="px-6 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-colors font-semibold">
                            <i data-lucide="send" class="w-4 h-4 inline"></i>
                            إرسال
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentConversationId = null;
let conversations = [];

async function loadConversations() {
    // Load sample conversations
    conversations = [
        {
            id: 1,
            name: 'أ. أحمد علي',
            role: 'مدرب برمجة الويب',
            avatar: 'assets/images/default-avatar.png',
            lastMessage: 'تم إرسال المواد الدراسية',
            lastMessageTime: '10:30 ص',
            unreadCount: 2,
            online: true
        },
        {
            id: 2,
            name: 'د. فاطمة حسن',
            role: 'مدربة الذكاء الاصطناعي',
            avatar: 'assets/images/default-avatar.png',
            lastMessage: 'لا تنسى تسليم الواجب',
            lastMessageTime: 'أمس',
            unreadCount: 0,
            online: false
        },
        {
            id: 3,
            name: 'م. خالد محمد',
            role: 'مدرب تطوير التطبيقات',
            avatar: 'assets/images/default-avatar.png',
            lastMessage: 'درجتك في الاختبار ممتازة',
            lastMessageTime: '2 أيام',
            unreadCount: 0,
            online: true
        }
    ];
    
    renderConversations();
    lucide.createIcons();
}

function renderConversations() {
    const container = document.getElementById('conversationsList');
    
    if (conversations.length === 0) {
        container.innerHTML = `
            <div class="p-8 text-center">
                <i data-lucide="message-circle-x" class="w-8 h-8 mx-auto text-slate-400 mb-2"></i>
                <p class="text-slate-500 text-sm">لا توجد محادثات</p>
            </div>
        `;
        lucide.createIcons();
        return;
    }
    
    container.innerHTML = conversations.map(conv => `
        <div onclick="openConversation(${conv.id})" 
            class="p-4 hover:bg-slate-50 cursor-pointer transition-colors ${currentConversationId === conv.id ? 'bg-amber-50' : ''}">
            <div class="flex items-start gap-3">
                <div class="relative flex-shrink-0">
                    <img src="${conv.avatar}" 
                        alt="${conv.name}" 
                        class="w-12 h-12 rounded-full object-cover"
                        onerror="this.src='assets/images/default-avatar.png'">
                    ${conv.online ? `
                        <span class="absolute bottom-0 left-0 w-3 h-3 bg-emerald-500 border-2 border-white rounded-full"></span>
                    ` : ''}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between mb-1">
                        <h4 class="font-bold text-slate-800 text-sm truncate">${conv.name}</h4>
                        <span class="text-xs text-slate-500 flex-shrink-0">${conv.lastMessageTime}</span>
                    </div>
                    <p class="text-xs text-slate-600 mb-1">${conv.role}</p>
                    <p class="text-sm text-slate-600 truncate">${conv.lastMessage}</p>
                </div>
                ${conv.unreadCount > 0 ? `
                    <span class="flex-shrink-0 w-5 h-5 bg-amber-600 text-white rounded-full flex items-center justify-center text-xs font-bold">
                        ${conv.unreadCount}
                    </span>
                ` : ''}
            </div>
        </div>
    `).join('');
    
    lucide.createIcons();
}

function openConversation(conversationId) {
    currentConversationId = conversationId;
    const conversation = conversations.find(c => c.id === conversationId);
    
    if (!conversation) return;
    
    // Update header
    const header = document.getElementById('chatHeader');
    header.innerHTML = `
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="relative">
                    <img src="${conversation.avatar}" 
                        alt="${conversation.name}" 
                        class="w-10 h-10 rounded-full object-cover"
                        onerror="this.src='assets/images/default-avatar.png'">
                    ${conversation.online ? `
                        <span class="absolute bottom-0 left-0 w-3 h-3 bg-emerald-500 border-2 border-white rounded-full"></span>
                    ` : ''}
                </div>
                <div>
                    <h3 class="font-bold text-slate-800">${conversation.name}</h3>
                    <p class="text-xs text-slate-600">${conversation.online ? 'متصل الآن' : conversation.role}</p>
                </div>
            </div>
            <div class="flex gap-2">
                <button class="p-2 text-slate-600 hover:bg-slate-200 rounded-lg transition-colors">
                    <i data-lucide="phone" class="w-5 h-5"></i>
                </button>
                <button class="p-2 text-slate-600 hover:bg-slate-200 rounded-lg transition-colors">
                    <i data-lucide="video" class="w-5 h-5"></i>
                </button>
                <button class="p-2 text-slate-600 hover:bg-slate-200 rounded-lg transition-colors">
                    <i data-lucide="more-vertical" class="w-5 h-5"></i>
                </button>
            </div>
        </div>
    `;
    
    // Show message input
    document.getElementById('messageInput').style.display = 'block';
    
    // Load messages
    loadMessages(conversationId);
    
    // Mark as read
    if (conversation.unreadCount > 0) {
        conversation.unreadCount = 0;
        renderConversations();
    }
    
    lucide.createIcons();
}

function loadMessages(conversationId) {
    const messages = [
        {
            id: 1,
            sender: 'trainer',
            text: 'مرحباً، كيف يمكنني مساعدتك؟',
            time: '10:00 ص',
            avatar: 'assets/images/default-avatar.png'
        },
        {
            id: 2,
            sender: 'student',
            text: 'لدي سؤال حول الواجب الأخير',
            time: '10:15 ص',
            avatar: 'assets/images/default-avatar.png'
        },
        {
            id: 3,
            sender: 'trainer',
            text: 'تفضل، ما هو سؤالك؟',
            time: '10:16 ص',
            avatar: 'assets/images/default-avatar.png'
        },
        {
            id: 4,
            sender: 'trainer',
            text: 'تم إرسال المواد الدراسية الإضافية',
            time: '10:30 ص',
            avatar: 'assets/images/default-avatar.png'
        }
    ];
    
    renderMessages(messages);
}

function renderMessages(messages) {
    const container = document.getElementById('messagesContainer');
    
    container.innerHTML = messages.map(msg => {
        const isStudent = msg.sender === 'student';
        return `
            <div class="flex ${isStudent ? 'justify-end' : 'justify-start'} mb-4">
                <div class="flex ${isStudent ? 'flex-row-reverse' : 'flex-row'} items-end gap-2 max-w-md">
                    ${!isStudent ? `
                        <img src="${msg.avatar}" 
                            alt="Avatar" 
                            class="w-8 h-8 rounded-full flex-shrink-0"
                            onerror="this.src='assets/images/default-avatar.png'">
                    ` : ''}
                    <div>
                        <div class="px-4 py-2 rounded-2xl ${
                            isStudent 
                                ? 'bg-amber-600 text-white rounded-br-none' 
                                : 'bg-white border border-slate-200 text-slate-800 rounded-bl-none'
                        }">
                            <p class="text-sm">${msg.text}</p>
                        </div>
                        <p class="text-xs text-slate-500 mt-1 ${isStudent ? 'text-left' : 'text-right'}">${msg.time}</p>
                    </div>
                </div>
            </div>
        `;
    }).join('');
    
    // Scroll to bottom
    container.scrollTop = container.scrollHeight;
}

function sendMessage() {
    const input = document.getElementById('messageText');
    const text = input.value.trim();
    
    if (!text || !currentConversationId) return;
    
    // Add message to UI
    const container = document.getElementById('messagesContainer');
    const now = new Date();
    const timeStr = now.toLocaleTimeString('ar-SA', { hour: '2-digit', minute: '2-digit' });
    
    container.innerHTML += `
        <div class="flex justify-end mb-4">
            <div class="flex flex-row-reverse items-end gap-2 max-w-md">
                <div>
                    <div class="px-4 py-2 rounded-2xl bg-amber-600 text-white rounded-br-none">
                        <p class="text-sm">${text}</p>
                    </div>
                    <p class="text-xs text-slate-500 mt-1 text-left">${timeStr}</p>
                </div>
            </div>
        </div>
    `;
    
    // Clear input
    input.value = '';
    
    // Scroll to bottom
    container.scrollTop = container.scrollHeight;
    
    // Update last message in conversations
    const conversation = conversations.find(c => c.id === currentConversationId);
    if (conversation) {
        conversation.lastMessage = text;
        conversation.lastMessageTime = timeStr;
        renderConversations();
    }
}

function handleMessageKeyPress(event) {
    if (event.key === 'Enter') {
        sendMessage();
    }
}

function startNewChat() {
    DashboardIntegration.ui.showToast('سيتم فتح قائمة المدربين قريباً', 'info');
}

// Initialize
loadConversations();
</script>
