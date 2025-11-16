<!-- 
=============================================
نظام المراسلة - Chat Sidebar Component
قائمة جانبية للمحادثات النشطة
=============================================
-->

<div id="chat-sidebar" class="chat-sidebar hidden fixed right-0 top-0 h-full w-80 bg-white shadow-2xl z-50 transform transition-transform duration-300" style="direction: rtl;">
    
    <!-- Header -->
    <div class="chat-sidebar-header bg-gradient-to-r from-blue-600 to-blue-700 text-white p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i data-lucide="message-circle" class="w-6 h-6"></i>
                <h3 class="text-lg font-bold">الرسائل</h3>
            </div>
            <button id="close-chat-sidebar" class="hover:bg-blue-800 rounded-lg p-2 transition">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        
        <!-- Search Bar -->
        <div class="mt-3">
            <div class="relative">
                <input 
                    type="text" 
                    id="chat-search" 
                    placeholder="بحث عن محادثة..."
                    class="w-full bg-white/20 text-white placeholder-white/70 rounded-lg px-4 py-2 pr-10 focus:outline-none focus:bg-white/30 transition"
                >
                <i data-lucide="search" class="absolute right-3 top-2.5 w-5 h-5 text-white/70"></i>
            </div>
        </div>
    </div>
    
    <!-- New Conversation Button -->
    <div class="p-3 border-b border-gray-200">
        <button 
            id="new-conversation-btn" 
            class="w-full bg-blue-50 hover:bg-blue-100 text-blue-600 font-semibold py-2 px-4 rounded-lg flex items-center justify-center gap-2 transition"
        >
            <i data-lucide="plus" class="w-5 h-5"></i>
            <span>محادثة جديدة</span>
        </button>
    </div>
    
    <!-- Tabs -->
    <div class="flex border-b border-gray-200">
        <button 
            id="tab-all-conversations" 
            class="flex-1 py-3 px-4 text-center font-semibold text-blue-600 border-b-2 border-blue-600 transition hover:bg-gray-50"
            data-tab="all"
        >
            الكل
            <span id="total-conversations-badge" class="inline-block bg-blue-600 text-white text-xs rounded-full px-2 py-0.5 mr-1">0</span>
        </button>
        <button 
            id="tab-group-conversations" 
            class="flex-1 py-3 px-4 text-center font-semibold text-gray-600 transition hover:bg-gray-50"
            data-tab="groups"
        >
            المجموعات
            <span id="groups-count-badge" class="inline-block bg-gray-400 text-white text-xs rounded-full px-2 py-0.5 mr-1">0</span>
        </button>
    </div>
    
    <!-- Conversations List -->
    <div id="conversations-list" class="overflow-y-auto" style="height: calc(100vh - 260px);">
        <!-- Loading State -->
        <div id="conversations-loading" class="flex items-center justify-center py-12">
            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600"></div>
        </div>
        
        <!-- Empty State -->
        <div id="conversations-empty" class="hidden text-center py-12 px-4">
            <i data-lucide="message-circle" class="w-16 h-16 text-gray-300 mx-auto mb-3"></i>
            <p class="text-gray-500">لا توجد محادثات حتى الآن</p>
            <p class="text-sm text-gray-400 mt-1">ابدأ محادثة جديدة الآن</p>
        </div>
        
        <!-- Conversations Container -->
        <div id="conversations-container" class="hidden">
            <!-- سيتم ملؤها ديناميكياً بواسطة JavaScript -->
        </div>
    </div>
    
</div>

<!-- Chat Sidebar Overlay -->
<div id="chat-sidebar-overlay" class="hidden fixed inset-0 bg-black/30 z-40"></div>

<!-- New Conversation Modal -->
<div id="new-conversation-modal" class="hidden fixed inset-0 flex items-center justify-center z-50" style="direction: rtl;">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-bold">محادثة جديدة</h3>
                <button id="close-new-conversation-modal" class="hover:bg-blue-800 rounded-lg p-2 transition">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
        </div>
        
        <!-- Modal Body -->
        <div class="p-4">
            <!-- Search Users -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">ابحث عن مستخدم</label>
                <div class="relative">
                    <input 
                        type="text" 
                        id="search-users" 
                        placeholder="اسم المستخدم..."
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 pr-10 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                    <i data-lucide="search" class="absolute right-3 top-2.5 w-5 h-5 text-gray-400"></i>
                </div>
            </div>
            
            <!-- Users List -->
            <div id="users-list" class="max-h-64 overflow-y-auto border border-gray-200 rounded-lg">
                <div class="flex items-center justify-center py-8">
                    <p class="text-gray-400 text-sm">ابحث عن مستخدم لبدء محادثة</p>
                </div>
            </div>
        </div>
        
    </div>
</div>

<!-- Conversation Item Template -->
<template id="conversation-item-template">
    <div class="conversation-item flex items-center gap-3 p-3 hover:bg-gray-50 cursor-pointer transition border-b border-gray-100 last:border-b-0" data-conversation-id="" data-type="">
        
        <!-- Avatar -->
        <div class="flex-shrink-0">
            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-lg">
                <!-- الأحرف الأولى -->
            </div>
        </div>
        
        <!-- Content -->
        <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between mb-1">
                <h4 class="font-semibold text-gray-900 truncate conversation-name">اسم المستخدم</h4>
                <span class="text-xs text-gray-500 conversation-time">منذ 5د</span>
            </div>
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-600 truncate conversation-last-message">آخر رسالة...</p>
                <span class="unread-badge hidden bg-blue-600 text-white text-xs rounded-full px-2 py-0.5 min-w-[20px] text-center">0</span>
            </div>
        </div>
        
        <!-- Role Badge -->
        <div class="flex-shrink-0">
            <span class="conversation-role-badge text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full">طالب</span>
        </div>
        
    </div>
</template>

<!-- User List Item Template -->
<template id="user-list-item-template">
    <div class="user-list-item flex items-center gap-3 p-3 hover:bg-blue-50 cursor-pointer transition border-b border-gray-100 last:border-b-0" data-user-id="">
        
        <!-- Avatar -->
        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-green-500 to-teal-600 flex items-center justify-center text-white font-bold">
            <!-- الأحرف الأولى -->
        </div>
        
        <!-- Info -->
        <div class="flex-1">
            <h4 class="font-semibold text-gray-900 user-name">اسم المستخدم</h4>
            <p class="text-xs text-gray-500 user-role">الدور</p>
        </div>
        
        <!-- Start Chat Icon -->
        <div>
            <i data-lucide="message-circle" class="w-5 h-5 text-blue-600"></i>
        </div>
        
    </div>
</template>

<style>
.chat-sidebar {
    box-shadow: -4px 0 15px rgba(0, 0, 0, 0.1);
}

.chat-sidebar.active {
    transform: translateX(0);
}

.conversation-item:hover {
    background: linear-gradient(90deg, rgba(59, 130, 246, 0.05), transparent);
}

.conversation-item.active {
    background: linear-gradient(90deg, rgba(59, 130, 246, 0.1), transparent);
    border-right: 3px solid #3b82f6;
}

/* Scrollbar Styling */
#conversations-list::-webkit-scrollbar,
#users-list::-webkit-scrollbar {
    width: 6px;
}

#conversations-list::-webkit-scrollbar-track,
#users-list::-webkit-scrollbar-track {
    background: #f1f1f1;
}

#conversations-list::-webkit-scrollbar-thumb,
#users-list::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

#conversations-list::-webkit-scrollbar-thumb:hover,
#users-list::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Animation */
@keyframes slideInRight {
    from {
        transform: translateX(-100%);
    }
    to {
        transform: translateX(0);
    }
}

.chat-sidebar.active {
    animation: slideInRight 0.3s ease-out;
}
</style>

<script>
// Initialize Lucide icons when sidebar is loaded
if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}
</script>
