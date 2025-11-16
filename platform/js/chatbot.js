/**
 * AI Chatbot Widget - Modern Floating Chat Interface
 * Advanced conversational UI with typing indicators, quick replies, and smooth animations
 */

class AIChatbot {
    constructor(options = {}) {
        this.apiUrl = options.apiUrl || '/platform/api/ai_chatbot.php';
        this.sessionId = localStorage.getItem('chatbot_session_id') || null;
        this.isOpen = false;
        this.isTyping = false;
        this.messages = [];
        
        this.init();
    }
    
    init() {
        this.createChatWidget();
        this.attachEventListeners();
        
        // Auto-start conversation if new user
        if (!this.sessionId) {
            this.startConversation();
        } else {
            this.loadHistory();
        }
    }
    
    createChatWidget() {
        const html = `
            <!-- Chat Button -->
            <div id="ai-chat-button" class="ai-chat-button">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
                <span class="ai-chat-badge" id="ai-chat-badge">1</span>
            </div>
            
            <!-- Chat Window -->
            <div id="ai-chat-window" class="ai-chat-window">
                <!-- Header -->
                <div class="ai-chat-header">
                    <div class="ai-chat-header-content">
                        <div class="ai-chat-avatar">
                            <img src="/platform/photos/Sh.jpg" alt="إبداع" />
                        </div>
                        <div class="ai-chat-info">
                            <h4>اسأل عبدالله 🎓</h4>
                            <span class="ai-chat-status">
                                <span class="ai-status-dot"></span>
                                متصل • جاهز لمساعدتك
                            </span>
                        </div>
                    </div>
                    <div class="ai-chat-actions">
                        <button class="ai-chat-minimize" id="ai-chat-minimize" title="تصغير">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                        </button>
                        <button class="ai-chat-close" id="ai-chat-close" title="إغلاق">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- Messages Container -->
                <div class="ai-chat-messages" id="ai-chat-messages">
                    <!-- Messages will be inserted here -->
                </div>
                
                <!-- Quick Replies -->
                <div class="ai-chat-quick-replies" id="ai-chat-quick-replies" style="display: none;">
                    <!-- Quick reply buttons will be inserted here -->
                </div>
                
                <!-- Input Area -->
                <div class="ai-chat-input">
                    <button class="ai-chat-attach" id="ai-chat-attach" title="إرفاق ملف">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path>
                        </svg>
                    </button>
                    <input 
                        type="text" 
                        id="ai-chat-input-field" 
                        class="ai-chat-input-field" 
                        placeholder="اكتب رسالتك هنا..."
                        autocomplete="off"
                    />
                    <button class="ai-chat-send" id="ai-chat-send" title="إرسال">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="22" y1="2" x2="11" y2="13"></line>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                        </svg>
                    </button>
                </div>
                
                <!-- Powered By -->
                <div class="ai-chat-footer">
                    <span>مدعوم بالذكاء الاصطناعي</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                        <path d="M2 17l10 5 10-5M2 12l10 5 10-5"></path>
                    </svg>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', html);
    }
    
    attachEventListeners() {
        // Toggle chat
        document.getElementById('ai-chat-button').addEventListener('click', () => this.toggleChat());
        document.getElementById('ai-chat-minimize').addEventListener('click', () => this.toggleChat());
        document.getElementById('ai-chat-close').addEventListener('click', () => this.closeChat());
        
        // Send message
        document.getElementById('ai-chat-send').addEventListener('click', () => this.sendMessage());
        document.getElementById('ai-chat-input-field').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') this.sendMessage();
        });
        
        // Attach file (placeholder)
        document.getElementById('ai-chat-attach').addEventListener('click', () => {
            alert('ميزة إرفاق الملفات قريباً!');
        });
    }
    
    toggleChat() {
        this.isOpen = !this.isOpen;
        const chatWindow = document.getElementById('ai-chat-window');
        const chatButton = document.getElementById('ai-chat-button');
        const badge = document.getElementById('ai-chat-badge');
        
        if (this.isOpen) {
            chatWindow.classList.add('ai-chat-open');
            chatButton.style.display = 'none';
            badge.style.display = 'none';
            this.scrollToBottom();
        } else {
            chatWindow.classList.remove('ai-chat-open');
            chatButton.style.display = 'flex';
        }
    }
    
    closeChat() {
        this.isOpen = false;
        document.getElementById('ai-chat-window').classList.remove('ai-chat-open');
        document.getElementById('ai-chat-button').style.display = 'flex';
    }
    
    async startConversation() {
        try {
            const response = await fetch(this.apiUrl + '?action=start', {
                method: 'POST'
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.sessionId = data.data.session_id;
                localStorage.setItem('chatbot_session_id', this.sessionId);
                
                this.addMessage('bot', data.data.message);
                
                if (data.data.quick_replies) {
                    this.showQuickReplies(data.data.quick_replies);
                }
            }
        } catch (error) {
            console.error('Error starting conversation:', error);
        }
    }
    
    async loadHistory() {
        try {
            const response = await fetch(this.apiUrl + `?action=history&session_id=${this.sessionId}`);
            const data = await response.json();
            
            if (data.success && data.data.length > 0) {
                data.data.forEach(msg => {
                    this.addMessage(msg.sender, msg.message, false);
                });
                this.scrollToBottom();
            }
        } catch (error) {
            console.error('Error loading history:', error);
        }
    }
    
    async sendMessage() {
        const inputField = document.getElementById('ai-chat-input-field');
        const message = inputField.value.trim();
        
        if (!message) return;
        
        // Clear input
        inputField.value = '';
        
        // Add user message
        this.addMessage('user', message);
        
        // Show typing indicator
        this.showTyping();
        
        // Send to API
        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'chat',
                    message: message,
                    session_id: this.sessionId
                })
            });
            
            const data = await response.json();
            
            // Hide typing
            this.hideTyping();
            
            if (data.success) {
                // Update session ID if new
                if (data.data.session_id && !this.sessionId) {
                    this.sessionId = data.data.session_id;
                    localStorage.setItem('chatbot_session_id', this.sessionId);
                }
                
                // Add bot response
                this.addMessage('bot', data.data.message);
                
                // Show quick replies if available
                if (data.data.quick_replies && data.data.quick_replies.length > 0) {
                    this.showQuickReplies(data.data.quick_replies);
                }
            } else {
                this.addMessage('bot', 'عذراً، حدث خطأ. يرجى المحاولة مرة أخرى.');
            }
        } catch (error) {
            this.hideTyping();
            this.addMessage('bot', 'عذراً، لا يمكن الاتصال بالخادم. يرجى المحاولة لاحقاً.');
            console.error('Error sending message:', error);
        }
    }
    
    addMessage(sender, text, animate = true) {
        const messagesContainer = document.getElementById('ai-chat-messages');
        
        const messageDiv = document.createElement('div');
        messageDiv.className = `ai-chat-message ai-chat-message-${sender}`;
        if (animate) messageDiv.classList.add('ai-message-fade-in');
        
        const avatar = sender === 'bot' ? 
            '<div class="ai-message-avatar"><img src="/platform/photos/Sh.jpg" alt="Bot" /></div>' : '';
        
        const timestamp = new Date().toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' });
        
        messageDiv.innerHTML = `
            ${avatar}
            <div class="ai-message-content">
                <div class="ai-message-bubble">
                    ${this.formatMessage(text)}
                </div>
                <span class="ai-message-time">${timestamp}</span>
            </div>
        `;
        
        messagesContainer.appendChild(messageDiv);
        this.scrollToBottom();
        
        this.messages.push({ sender, text, timestamp: new Date() });
    }
    
    formatMessage(text) {
        // Convert line breaks to <br>
        text = text.replace(/\n/g, '<br>');
        
        // Convert URLs to links
        text = text.replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank">$1</a>');
        
        // Convert emojis (already in text)
        return text;
    }
    
    showTyping() {
        this.isTyping = true;
        const messagesContainer = document.getElementById('ai-chat-messages');
        
        const typingDiv = document.createElement('div');
        typingDiv.id = 'ai-typing-indicator';
        typingDiv.className = 'ai-chat-message ai-chat-message-bot ai-typing-indicator';
        typingDiv.innerHTML = `
            <div class="ai-message-avatar"><img src="/platform/photos/Sh.jpg" alt="Bot" /></div>
            <div class="ai-message-content">
                <div class="ai-message-bubble">
                    <div class="ai-typing-dots">
                        <span></span><span></span><span></span>
                    </div>
                </div>
            </div>
        `;
        
        messagesContainer.appendChild(typingDiv);
        this.scrollToBottom();
    }
    
    hideTyping() {
        this.isTyping = false;
        const typingIndicator = document.getElementById('ai-typing-indicator');
        if (typingIndicator) {
            typingIndicator.remove();
        }
    }
    
    showQuickReplies(replies) {
        const container = document.getElementById('ai-chat-quick-replies');
        container.innerHTML = '';
        
        replies.forEach(reply => {
            const button = document.createElement('button');
            button.className = 'ai-quick-reply-btn';
            button.innerHTML = `
                ${reply.icon ? `<i data-lucide="${reply.icon}"></i>` : ''}
                <span>${reply.text}</span>
            `;
            button.addEventListener('click', () => this.handleQuickReply(reply));
            container.appendChild(button);
        });
        
        container.style.display = 'flex';
        
        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
    
    async handleQuickReply(reply) {
        // Hide quick replies
        document.getElementById('ai-chat-quick-replies').style.display = 'none';
        
        // Add as user message
        this.addMessage('user', reply.text);
        
        // Show typing
        this.showTyping();
        
        // Send action to backend
        try {
            const formData = new FormData();
            formData.append('action', 'quick_reply');
            formData.append('reply_action', reply.action);
            formData.append('session_id', this.sessionId);
            
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            this.hideTyping();
            
            if (data.success) {
                this.addMessage('bot', data.data.message);
            }
        } catch (error) {
            this.hideTyping();
            console.error('Error handling quick reply:', error);
        }
    }
    
    scrollToBottom() {
        const messagesContainer = document.getElementById('ai-chat-messages');
        setTimeout(() => {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }, 100);
    }
}

// Initialize chatbot when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.ibdaaChatbot = new AIChatbot({
        apiUrl: '/platform/api/ai_chatbot.php'
    });
});
