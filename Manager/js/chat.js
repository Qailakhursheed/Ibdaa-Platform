/**
 * ==============================================
 * نظام المراسلة - Chat System JavaScript
 * Messaging System Complete Frontend
 * ==============================================
 */

class ChatSystem {
    constructor() {
        this.currentContactId = null;
        this.currentGroupId = null;
        this.currentConversationType = null; // 'individual' or 'group'
        this.pollInterval = null;
        this.conversations = [];
        this.messages = [];
        this.unreadCount = 0;
        
        this.init();
    }
    
    /**
     * تهيئة النظام
     */
    init() {
        this.initElements();
        this.attachEventListeners();
        this.loadConversations();
        this.startPolling();
    }
    
    /**
     * تهيئة العناصر DOM
     */
    initElements() {
        // Sidebar elements
        this.chatSidebar = document.getElementById('chat-sidebar');
        this.chatSidebarOverlay = document.getElementById('chat-sidebar-overlay');
        this.conversationsList = document.getElementById('conversations-list');
        this.conversationsContainer = document.getElementById('conversations-container');
        this.conversationsLoading = document.getElementById('conversations-loading');
        this.conversationsEmpty = document.getElementById('conversations-empty');
        this.chatSearch = document.getElementById('chat-search');
        
        // Conversation view elements
        this.conversationView = document.getElementById('conversation-view');
        this.conversationViewOverlay = document.getElementById('conversation-view-overlay');
        this.messagesContainer = document.getElementById('messages-container');
        this.messagesList = document.getElementById('messages-list');
        this.messagesLoading = document.getElementById('messages-loading');
        this.messagesEmpty = document.getElementById('messages-empty');
        this.messageInput = document.getElementById('message-input');
        this.sendBtn = document.getElementById('send-message-btn');
        
        // Badge elements
        this.chatUnreadBadge = document.getElementById('chat-unread-badge');
        this.totalConversationsBadge = document.getElementById('total-conversations-badge');
        
        // Toggle buttons
        this.chatToggleBtn = document.getElementById('chat-toggle');
        this.closeChatSidebarBtn = document.getElementById('close-chat-sidebar');
        this.closeConversationViewBtn = document.getElementById('close-conversation-view');
        this.backToSidebarBtn = document.getElementById('back-to-sidebar');
    }
    
    /**
     * ربط الأحداث
     */
    attachEventListeners() {
        // Toggle chat sidebar
        if (this.chatToggleBtn) {
            this.chatToggleBtn.addEventListener('click', () => this.toggleChatSidebar());
        }
        
        // Close sidebar
        if (this.closeChatSidebarBtn) {
            this.closeChatSidebarBtn.addEventListener('click', () => this.closeChatSidebar());
        }
        
        if (this.chatSidebarOverlay) {
            this.chatSidebarOverlay.addEventListener('click', () => this.closeChatSidebar());
        }
        
        // Close conversation view
        if (this.closeConversationViewBtn) {
            this.closeConversationViewBtn.addEventListener('click', () => this.closeConversationView());
        }
        
        if (this.backToSidebarBtn) {
            this.backToSidebarBtn.addEventListener('click', () => this.closeConversationView());
        }
        
        if (this.conversationViewOverlay) {
            this.conversationViewOverlay.addEventListener('click', () => this.closeConversationView());
        }
        
        // Search conversations
        if (this.chatSearch) {
            this.chatSearch.addEventListener('input', (e) => {
                const query = e.target.value.toLowerCase();
                this.filterConversations(query);
            });
        }
        
        // Send message
        if (this.sendBtn) {
            this.sendBtn.addEventListener('click', () => this.sendMessage());
        }
        
        // New conversation button
        const newConvBtn = document.getElementById('new-conversation-btn');
        if (newConvBtn) {
            newConvBtn.addEventListener('click', () => this.openNewConversationModal());
        }
        
        // Delete conversation
        const deleteBtn = document.getElementById('conversation-delete-btn');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', () => this.showDeleteConversationModal());
        }
    }
    
    /**
     * فتح/إغلاق Chat Sidebar
     */
    toggleChatSidebar() {
        if (this.chatSidebar.classList.contains('hidden')) {
            this.openChatSidebar();
        } else {
            this.closeChatSidebar();
        }
    }
    
    openChatSidebar() {
        this.chatSidebar.classList.remove('hidden');
        this.chatSidebar.classList.add('active');
        this.chatSidebarOverlay.classList.remove('hidden');
        this.loadConversations();
    }
    
    closeChatSidebar() {
        this.chatSidebar.classList.remove('active');
        this.chatSidebar.classList.add('hidden');
        this.chatSidebarOverlay.classList.add('hidden');
    }
    
    /**
     * فتح/إغلاق Conversation View
     */
    openConversationView() {
        this.conversationView.classList.remove('hidden');
        this.conversationViewOverlay.classList.remove('hidden');
        
        // على الشاشات الصغيرة، إخفاء الـ sidebar
        if (window.innerWidth < 768) {
            this.chatSidebar.classList.add('hidden');
        }
    }
    
    closeConversationView() {
        this.conversationView.classList.add('hidden');
        this.conversationViewOverlay.classList.add('hidden');
        this.currentContactId = null;
        this.currentGroupId = null;
        this.currentConversationType = null;
        
        // إعادة فتح الـ sidebar على الشاشات الصغيرة
        if (window.innerWidth < 768) {
            this.openChatSidebar();
        }
    }
    
    /**
     * جلب قائمة المحادثات
     */
    async loadConversations() {
        try {
            this.conversationsLoading.classList.remove('hidden');
            this.conversationsContainer.classList.add('hidden');
            this.conversationsEmpty.classList.add('hidden');
            
            const response = await fetch('api/get_conversations.php?limit=50');
            const data = await response.json();
            
            if (data.success) {
                this.conversations = data.conversations;
                this.unreadCount = data.total_unread || 0;
                
                this.renderConversations();
                this.updateUnreadBadge();
            } else {
                console.error('Failed to load conversations:', data.message);
            }
        } catch (error) {
            console.error('Error loading conversations:', error);
        } finally {
            this.conversationsLoading.classList.add('hidden');
        }
    }
    
    /**
     * عرض المحادثات
     */
    renderConversations() {
        if (this.conversations.length === 0) {
            this.conversationsEmpty.classList.remove('hidden');
            this.conversationsContainer.classList.add('hidden');
            return;
        }
        
        this.conversationsEmpty.classList.add('hidden');
        this.conversationsContainer.classList.remove('hidden');
        this.conversationsContainer.innerHTML = '';
        
        const template = document.getElementById('conversation-item-template');
        
        this.conversations.forEach(conv => {
            const clone = template.content.cloneNode(true);
            const item = clone.querySelector('.conversation-item');
            
            // Set data attributes
            item.setAttribute('data-conversation-id', conv.conversation_id);
            item.setAttribute('data-type', conv.type);
            
            if (conv.type === 'individual') {
                item.setAttribute('data-contact-id', conv.contact_id);
            } else {
                item.setAttribute('data-group-id', conv.group_id);
            }
            
            // Avatar (first letters)
            const avatar = clone.querySelector('.w-12');
            const name = conv.type === 'individual' ? conv.contact_name : conv.group_name;
            const initials = this.getInitials(name);
            avatar.textContent = initials;
            
            // Name
            clone.querySelector('.conversation-name').textContent = name;
            
            // Last message
            clone.querySelector('.conversation-last-message').textContent = conv.last_message || 'لا توجد رسائل';
            
            // Time
            clone.querySelector('.conversation-time').textContent = conv.time_ago;
            
            // Unread badge
            if (conv.unread_count > 0) {
                const badge = clone.querySelector('.unread-badge');
                badge.classList.remove('hidden');
                badge.textContent = conv.unread_count;
            }
            
            // Role badge
            if (conv.type === 'individual') {
                const roleMap = {
                    'student': 'طالب',
                    'trainer': 'مدرب',
                    'technical': 'فني',
                    'manager': 'مدير'
                };
                clone.querySelector('.conversation-role-badge').textContent = roleMap[conv.contact_role] || conv.contact_role;
            } else {
                clone.querySelector('.conversation-role-badge').textContent = 'مجموعة';
            }
            
            // Click event
            item.addEventListener('click', () => {
                this.openConversation(conv);
            });
            
            this.conversationsContainer.appendChild(clone);
        });
        
        // Update total conversations badge
        if (this.totalConversationsBadge) {
            this.totalConversationsBadge.textContent = this.conversations.length;
        }
        
        // Re-initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
    
    /**
     * فلترة المحادثات بالبحث
     */
    filterConversations(query) {
        const items = this.conversationsContainer.querySelectorAll('.conversation-item');
        
        items.forEach(item => {
            const name = item.querySelector('.conversation-name').textContent.toLowerCase();
            if (name.includes(query)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    }
    
    /**
     * فتح محادثة محددة
     */
    async openConversation(conv) {
        if (conv.type === 'individual') {
            this.currentContactId = conv.contact_id;
            this.currentGroupId = null;
            this.currentConversationType = 'individual';
            
            // Update header
            document.getElementById('conversation-contact-name').textContent = conv.contact_name;
            document.getElementById('conversation-contact-status').textContent = 'متصل';
            
            const avatar = document.getElementById('conversation-avatar');
            avatar.textContent = this.getInitials(conv.contact_name);
            
        } else {
            this.currentGroupId = conv.group_id;
            this.currentContactId = null;
            this.currentConversationType = 'group';
            
            document.getElementById('conversation-contact-name').textContent = conv.group_name;
            document.getElementById('conversation-contact-status').textContent = 'مجموعة';
            
            const avatar = document.getElementById('conversation-avatar');
            avatar.textContent = this.getInitials(conv.group_name);
        }
        
        this.openConversationView();
        await this.loadMessages();
        
        // Mark conversation as active
        document.querySelectorAll('.conversation-item').forEach(item => {
            item.classList.remove('active');
        });
        const activeItem = document.querySelector(`[data-conversation-id="${conv.conversation_id}"]`);
        if (activeItem) {
            activeItem.classList.add('active');
        }
    }
    
    /**
     * جلب رسائل المحادثة
     */
    async loadMessages() {
        try {
            this.messagesLoading.classList.remove('hidden');
            this.messagesList.classList.add('hidden');
            this.messagesEmpty.classList.add('hidden');
            
            let url = 'api/get_messages.php?limit=50';
            if (this.currentConversationType === 'individual') {
                url += `&contact_id=${this.currentContactId}`;
            } else {
                url += `&group_id=${this.currentGroupId}`;
            }
            
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.success) {
                this.messages = data.messages;
                this.renderMessages();
                this.scrollToBottom();
            } else {
                console.error('Failed to load messages:', data.message);
            }
        } catch (error) {
            console.error('Error loading messages:', error);
        } finally {
            this.messagesLoading.classList.add('hidden');
        }
    }
    
    /**
     * عرض الرسائل
     */
    renderMessages() {
        if (this.messages.length === 0) {
            this.messagesEmpty.classList.remove('hidden');
            this.messagesList.classList.add('hidden');
            return;
        }
        
        this.messagesEmpty.classList.add('hidden');
        this.messagesList.classList.remove('hidden');
        this.messagesList.innerHTML = '';
        
        const mineTemplate = document.getElementById('message-mine-template');
        const theirsTemplate = document.getElementById('message-theirs-template');
        
        this.messages.forEach((msg, index) => {
            const template = msg.is_mine ? mineTemplate : theirsTemplate;
            const clone = template.content.cloneNode(true);
            const item = clone.querySelector('.message-item');
            
            item.setAttribute('data-message-id', msg.id);
            
            // Message text
            clone.querySelector('.message-text').textContent = msg.message_text;
            
            // Time
            clone.querySelector('.message-time').textContent = msg.time || msg.time_ago;
            
            // Sender name (for theirs messages)
            if (!msg.is_mine && this.currentConversationType === 'group') {
                clone.querySelector('.message-sender-name').textContent = msg.sender_name;
            } else if (!msg.is_mine) {
                const senderName = clone.querySelector('.message-sender-name');
                if (senderName) senderName.remove();
            }
            
            // Status (for mine messages)
            if (msg.is_mine) {
                const statusIcon = clone.querySelector('.message-status');
                if (msg.status === 'seen') {
                    statusIcon.classList.add('seen');
                    statusIcon.innerHTML = '<i data-lucide="check-check" class="w-3 h-3 text-blue-500 inline"></i>';
                } else {
                    statusIcon.classList.add('sent');
                    statusIcon.innerHTML = '<i data-lucide="check" class="w-3 h-3 text-gray-400 inline"></i>';
                }
            }
            
            this.messagesList.appendChild(clone);
        });
        
        // Re-initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
    
    /**
     * إرسال رسالة جديدة
     */
    async sendMessage() {
        const text = this.messageInput.value.trim();
        
        if (!text || text.length === 0 || text.length > 5000) {
            return;
        }
        
        // Disable send button
        this.sendBtn.disabled = true;
        
        try {
            const payload = {
                message_text: text
            };
            
            if (this.currentConversationType === 'individual') {
                payload.receiver_id = this.currentContactId;
            } else {
                payload.group_id = this.currentGroupId;
            }
            
            const response = await fetch('api/send_message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Clear input
                this.messageInput.value = '';
                this.messageInput.style.height = 'auto';
                document.getElementById('char-counter').textContent = '0/5000';
                
                // Reload messages
                await this.loadMessages();
                
                // Reload conversations to update last message
                await this.loadConversations();
            } else {
                alert('فشل إرسال الرسالة: ' + data.message);
            }
        } catch (error) {
            console.error('Error sending message:', error);
            alert('حدث خطأ أثناء إرسال الرسالة');
        } finally {
            this.sendBtn.disabled = false;
        }
    }
    
    /**
     * حذف محادثة
     */
    showDeleteConversationModal() {
        const modal = document.getElementById('delete-conversation-modal');
        modal.classList.remove('hidden');
        
        const confirmBtn = document.getElementById('confirm-delete-conversation');
        const cancelBtn = document.getElementById('cancel-delete-conversation');
        
        const handleConfirm = async () => {
            await this.deleteConversation();
            modal.classList.add('hidden');
            confirmBtn.removeEventListener('click', handleConfirm);
            cancelBtn.removeEventListener('click', handleCancel);
        };
        
        const handleCancel = () => {
            modal.classList.add('hidden');
            confirmBtn.removeEventListener('click', handleConfirm);
            cancelBtn.removeEventListener('click', handleCancel);
        };
        
        confirmBtn.addEventListener('click', handleConfirm);
        cancelBtn.addEventListener('click', handleCancel);
    }
    
    async deleteConversation() {
        try {
            let url = 'api/delete_message.php?';
            if (this.currentConversationType === 'individual') {
                url += `contact_id=${this.currentContactId}`;
            }
            
            const response = await fetch(url, {
                method: 'DELETE'
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.closeConversationView();
                await this.loadConversations();
            } else {
                alert('فشل حذف المحادثة: ' + data.message);
            }
        } catch (error) {
            console.error('Error deleting conversation:', error);
            alert('حدث خطأ أثناء حذف المحادثة');
        }
    }
    
    /**
     * تحديث Badge عدد غير المقروءة
     */
    updateUnreadBadge() {
        if (this.chatUnreadBadge) {
            if (this.unreadCount > 0) {
                this.chatUnreadBadge.textContent = this.unreadCount > 99 ? '99+' : this.unreadCount;
                this.chatUnreadBadge.classList.remove('hidden');
            } else {
                this.chatUnreadBadge.classList.add('hidden');
            }
        }
    }
    
    /**
     * Scroll to bottom
     */
    scrollToBottom() {
        if (this.messagesContainer) {
            setTimeout(() => {
                this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
            }, 100);
        }
    }
    
    /**
     * فتح نافذة محادثة جديدة
     */
    openNewConversationModal() {
        // TODO: Implement new conversation modal
        alert('سيتم إضافة هذه الميزة قريباً');
    }
    
    /**
     * بدء Real-time Polling
     */
    startPolling() {
        // Poll every 5 seconds
        this.pollInterval = setInterval(() => {
            // Only update conversations if sidebar is open
            if (!this.chatSidebar.classList.contains('hidden')) {
                this.loadConversations();
            }
            
            // Update messages if conversation is open
            if (!this.conversationView.classList.contains('hidden')) {
                this.loadMessages();
            }
        }, 5000);
    }
    
    /**
     * إيقاف Polling
     */
    stopPolling() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
        }
    }
    
    /**
     * الحصول على الأحرف الأولى من الاسم
     */
    getInitials(name) {
        if (!name) return '؟';
        const words = name.trim().split(' ');
        if (words.length === 1) {
            return words[0].substring(0, 2).toUpperCase();
        }
        return (words[0][0] + words[words.length - 1][0]).toUpperCase();
    }
}

// Initialize chat system when DOM is ready
let chatSystem;
document.addEventListener('DOMContentLoaded', function() {
    chatSystem = new ChatSystem();
});

// Clean up on page unload
window.addEventListener('beforeunload', function() {
    if (chatSystem) {
        chatSystem.stopPolling();
    }
});
