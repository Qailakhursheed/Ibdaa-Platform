document.addEventListener('DOMContentLoaded', () => {
    const chatButton = document.getElementById('ai-chat-button');
    const chatWidget = document.getElementById('ai-chat-widget');
    const closeButton = document.getElementById('chat-close-btn');
    const chatBody = document.getElementById('chat-body');
    const chatInputForm = document.getElementById('chat-input-form');
    const chatInput = document.getElementById('chat-input');
    const sendButton = document.getElementById('chat-send-btn');
    const openIcon = chatButton.querySelector('.icon-open');
    const closeIcon = chatButton.querySelector('.icon-close');

    const API_URL = 'api/ai_assistant.php'; // Mock API endpoint

    // --- Toggle Chat Widget ---
    const toggleWidget = () => {
        const isOpen = chatWidget.classList.toggle('open');
        openIcon.style.display = isOpen ? 'none' : 'block';
        closeIcon.style.display = isOpen ? 'block' : 'none';
        chatButton.setAttribute('aria-expanded', isOpen);
        if (isOpen) {
            chatInput.focus();
        }
    };

    chatButton.addEventListener('click', toggleWidget);
    closeButton.addEventListener('click', toggleWidget);

    // --- Handle Form Submission ---
    chatInputForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const userInput = chatInput.value.trim();
        if (userInput) {
            addMessage(userInput, 'user');
            chatInput.value = '';
            sendButton.disabled = true;
            fetchAssistantResponse(userInput);
        }
    });

    // Enable/disable send button based on input
    chatInput.addEventListener('input', () => {
        sendButton.disabled = chatInput.value.trim().length === 0;
    });

    // --- Add Messages to Chat Body ---
    const addMessage = (text, sender) => {
        const messageElement = document.createElement('div');
        messageElement.classList.add('chat-message', sender);
        messageElement.textContent = text;
        chatBody.appendChild(messageElement);
        scrollToBottom();
    };
    
    const addThinkingIndicator = () => {
        const thinkingElement = document.createElement('div');
        thinkingElement.classList.add('chat-message', 'assistant', 'thinking');
        thinkingElement.innerHTML = `
            <div class="dot"></div>
            <div class="dot"></div>
            <div class="dot"></div>
        `;
        chatBody.appendChild(thinkingElement);
        scrollToBottom();
        return thinkingElement;
    };

    // --- Fetch Assistant Response ---
    const fetchAssistantResponse = async (userInput) => {
        const thinkingIndicator = addThinkingIndicator();

        try {
            // Mocking a delay and response
            await new Promise(resolve => setTimeout(resolve, 1500));

            const response = {
                success: true,
                reply: `هذا رد تجريبي على سؤالك: "${userInput}". أنا حاليًا في وضع التطوير.`
            };
            
            // In a real scenario, you would use fetch:
            /*
            const response = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ prompt: userInput })
            }).then(res => res.json());
            */

            thinkingIndicator.remove();
            if (response.success) {
                addMessage(response.reply, 'assistant');
            } else {
                addMessage('عذراً، حدث خطأ ما. يرجى المحاولة مرة أخرى.', 'assistant');
            }

        } catch (error) {
            console.error('AI Assistant Error:', error);
            thinkingIndicator.remove();
            addMessage('عذراً، لا يمكنني الاتصال بالخادم حالياً.', 'assistant');
        } finally {
            sendButton.disabled = chatInput.value.trim().length === 0;
        }
    };

    // --- Utility Functions ---
    const scrollToBottom = () => {
        chatBody.scrollTop = chatBody.scrollHeight;
    };

    // --- Initial Greeting ---
    setTimeout(() => {
        addMessage('مرحباً بك في منصة إبداع! كيف يمكنني مساعدتك اليوم؟', 'assistant');
    }, 1000);
});
