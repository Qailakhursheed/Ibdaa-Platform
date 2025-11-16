<!-- 
=============================================
نظام المراسلة - Conversation View Component
عرض الرسائل ومربع الإرسال
=============================================
-->

<div id="conversation-view" class="conversation-view hidden fixed left-0 top-0 h-full w-full md:w-[600px] bg-white shadow-2xl z-50" style="direction: rtl;">
    
    <!-- Conversation Header -->
    <div class="conversation-header bg-gradient-to-r from-blue-600 to-blue-700 text-white p-4 shadow-md">
        <div class="flex items-center justify-between">
            
            <!-- Contact Info -->
            <div class="flex items-center gap-3 flex-1">
                <button id="back-to-sidebar" class="hover:bg-blue-800 rounded-lg p-2 transition">
                    <i data-lucide="arrow-right" class="w-5 h-5"></i>
                </button>
                
                <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center text-white font-bold text-sm" id="conversation-avatar">
                    <!-- الأحرف الأولى -->
                </div>
                
                <div class="flex-1">
                    <h3 id="conversation-contact-name" class="font-bold text-lg">اسم المستخدم</h3>
                    <p id="conversation-contact-status" class="text-xs text-blue-100">متصل</p>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="flex items-center gap-2">
                <button id="conversation-search-btn" class="hover:bg-blue-800 rounded-lg p-2 transition" title="بحث في المحادثة">
                    <i data-lucide="search" class="w-5 h-5"></i>
                </button>
                <button id="conversation-info-btn" class="hover:bg-blue-800 rounded-lg p-2 transition" title="معلومات">
                    <i data-lucide="info" class="w-5 h-5"></i>
                </button>
                <button id="conversation-delete-btn" class="hover:bg-red-600 rounded-lg p-2 transition" title="حذف المحادثة">
                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                </button>
                <button id="close-conversation-view" class="hover:bg-blue-800 rounded-lg p-2 transition">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            
        </div>
    </div>
    
    <!-- Messages Container -->
    <div id="messages-container" class="messages-container overflow-y-auto p-4 bg-gray-50" style="height: calc(100vh - 140px);">
        
        <!-- Loading State -->
        <div id="messages-loading" class="flex items-center justify-center py-12">
            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600"></div>
        </div>
        
        <!-- Empty State -->
        <div id="messages-empty" class="hidden text-center py-12">
            <i data-lucide="message-circle" class="w-16 h-16 text-gray-300 mx-auto mb-3"></i>
            <p class="text-gray-500">لا توجد رسائل بعد</p>
            <p class="text-sm text-gray-400 mt-1">ابدأ المحادثة الآن</p>
        </div>
        
        <!-- Messages List -->
        <div id="messages-list" class="space-y-3 hidden">
            <!-- سيتم ملؤها ديناميكياً -->
        </div>
        
        <!-- Load More Button -->
        <div id="load-more-messages" class="hidden text-center py-3">
            <button class="text-blue-600 hover:text-blue-700 font-semibold text-sm">
                تحميل رسائل أقدم
            </button>
        </div>
        
    </div>
    
    <!-- Message Input Area -->
    <div class="message-input-area bg-white border-t border-gray-200 p-3">
        <div class="flex items-end gap-2">
            
            <!-- Attach Button -->
            <button id="attach-file-btn" class="flex-shrink-0 bg-gray-100 hover:bg-gray-200 rounded-lg p-2.5 transition" title="إرفاق ملف">
                <i data-lucide="paperclip" class="w-5 h-5 text-gray-600"></i>
            </button>
            
            <!-- Text Input -->
            <div class="flex-1 relative">
                <textarea 
                    id="message-input" 
                    placeholder="اكتب رسالتك هنا..."
                    rows="1"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none max-h-32"
                ></textarea>
                <div id="char-counter" class="absolute left-2 bottom-2 text-xs text-gray-400">0/5000</div>
            </div>
            
            <!-- Send Button -->
            <button id="send-message-btn" class="flex-shrink-0 bg-blue-600 hover:bg-blue-700 text-white rounded-lg p-2.5 transition disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                <i data-lucide="send" class="w-5 h-5"></i>
            </button>
            
        </div>
        
        <!-- File Preview (Hidden by default) -->
        <div id="file-preview" class="hidden mt-2 p-2 bg-blue-50 rounded-lg flex items-center gap-2">
            <i data-lucide="file" class="w-5 h-5 text-blue-600"></i>
            <span id="file-name" class="text-sm text-gray-700 flex-1"></span>
            <button id="remove-file-btn" class="text-red-600 hover:text-red-700">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    </div>
    
</div>

<!-- Conversation View Overlay -->
<div id="conversation-view-overlay" class="hidden fixed inset-0 bg-black/30 z-40"></div>

<!-- Message Template (Mine) -->
<template id="message-mine-template">
    <div class="message-item message-mine flex justify-end mb-3" data-message-id="">
        <div class="max-w-[75%]">
            <div class="bg-blue-600 text-white rounded-2xl rounded-br-sm px-4 py-2.5 shadow-sm">
                <p class="message-text text-sm leading-relaxed whitespace-pre-wrap break-words"></p>
            </div>
            <div class="flex items-center justify-end gap-2 mt-1 px-1">
                <span class="message-time text-xs text-gray-500"></span>
                <span class="message-status text-xs">
                    <i data-lucide="check" class="w-3 h-3 text-gray-400 inline"></i>
                </span>
            </div>
        </div>
    </div>
</template>

<!-- Message Template (Theirs) -->
<template id="message-theirs-template">
    <div class="message-item message-theirs flex justify-start mb-3" data-message-id="">
        <div class="max-w-[75%]">
            <div class="bg-white rounded-2xl rounded-bl-sm px-4 py-2.5 shadow-sm border border-gray-200">
                <p class="message-sender-name text-xs font-semibold text-blue-600 mb-1"></p>
                <p class="message-text text-sm leading-relaxed text-gray-800 whitespace-pre-wrap break-words"></p>
            </div>
            <div class="flex items-center gap-2 mt-1 px-1">
                <span class="message-time text-xs text-gray-500"></span>
            </div>
        </div>
    </div>
</template>

<!-- Date Separator Template -->
<template id="date-separator-template">
    <div class="date-separator flex items-center justify-center my-4">
        <div class="bg-gray-200 text-gray-600 text-xs font-semibold px-3 py-1 rounded-full">
            اليوم
        </div>
    </div>
</template>

<!-- Delete Confirmation Modal -->
<div id="delete-conversation-modal" class="hidden fixed inset-0 flex items-center justify-center z-50" style="direction: rtl;">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-6">
        <div class="text-center">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-lucide="trash-2" class="w-8 h-8 text-red-600"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">حذف المحادثة</h3>
            <p class="text-gray-600 mb-6">هل أنت متأكد من حذف هذه المحادثة؟ لا يمكن التراجع عن هذا الإجراء.</p>
            <div class="flex gap-3">
                <button id="confirm-delete-conversation" class="flex-1 bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg transition">
                    حذف
                </button>
                <button id="cancel-delete-conversation" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2 px-4 rounded-lg transition">
                    إلغاء
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.conversation-view {
    box-shadow: -4px 0 15px rgba(0, 0, 0, 0.1);
}

.messages-container {
    background: linear-gradient(to bottom, #f9fafb, #f3f4f6);
}

/* Scrollbar Styling */
.messages-container::-webkit-scrollbar {
    width: 6px;
}

.messages-container::-webkit-scrollbar-track {
    background: transparent;
}

.messages-container::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.messages-container::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Message Animations */
.message-item {
    animation: messageSlideIn 0.2s ease-out;
}

@keyframes messageSlideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Message Status Icons */
.message-status.sent i {
    color: #9ca3af;
}

.message-status.seen i {
    color: #3b82f6;
}

/* Textarea Auto-resize */
#message-input {
    transition: height 0.2s ease;
}

/* File Preview Animation */
#file-preview {
    animation: slideDown 0.2s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        max-height: 0;
    }
    to {
        opacity: 1;
        max-height: 100px;
    }
}

/* Responsive */
@media (max-width: 768px) {
    .conversation-view {
        width: 100% !important;
    }
}
</style>

<script>
// Initialize Lucide icons
if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}

// Auto-resize textarea
document.addEventListener('DOMContentLoaded', function() {
    const messageInput = document.getElementById('message-input');
    const charCounter = document.getElementById('char-counter');
    const sendBtn = document.getElementById('send-message-btn');
    
    if (messageInput) {
        messageInput.addEventListener('input', function() {
            // Auto-resize
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 128) + 'px';
            
            // Character counter
            const length = this.value.length;
            charCounter.textContent = length + '/5000';
            
            // Enable/disable send button
            sendBtn.disabled = length === 0 || length > 5000;
            
            // Change counter color if exceeding limit
            if (length > 5000) {
                charCounter.classList.add('text-red-500');
                charCounter.classList.remove('text-gray-400');
            } else {
                charCounter.classList.remove('text-red-500');
                charCounter.classList.add('text-gray-400');
            }
        });
        
        // Send on Enter (Shift+Enter for new line)
        messageInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                if (!sendBtn.disabled) {
                    sendBtn.click();
                }
            }
        });
    }
});
</script>
