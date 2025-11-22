<!-- "Ask Abdullah" Chatbot UI -->
<style>
    #chatbot-bubble {
        position: fixed;
        bottom: 25px;
        right: 25px;
        width: 60px;
        height: 60px;
        background-color: #0ea5e9; /* sky-500 */
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        z-index: 9998;
        transition: transform 0.2s ease;
    }
    #chatbot-bubble:hover {
        transform: scale(1.1);
    }
    #chatbot-window {
        position: fixed;
        bottom: 100px;
        right: 25px;
        width: 370px;
        max-width: 90vw;
        height: 500px;
        max-height: 70vh;
        background-color: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        z-index: 9999;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        transform: scale(0.95);
        opacity: 0;
        visibility: hidden;
        transition: transform 0.2s ease, opacity 0.2s ease;
    }
    #chatbot-window.open {
        transform: scale(1);
        opacity: 1;
        visibility: visible;
    }
    .chat-header {
        background: linear-gradient(to right, #0ea5e9, #0284c7);
        color: white;
        padding: 1rem;
        text-align: center;
        font-weight: bold;
    }
    .chat-body {
        flex-grow: 1;
        padding: 1rem;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    .chat-message {
        padding: 0.75rem 1rem;
        border-radius: 10px;
        max-width: 80%;
        line-height: 1.5;
    }
    .chat-message.user {
        background-color: #f0f9ff; /* sky-50 */
        color: #0369a1; /* sky-800 */
        align-self: flex-end;
        border-bottom-right-radius: 2px;
    }
    .chat-message.bot {
        background-color: #f1f5f9; /* slate-100 */
        color: #1e293b; /* slate-800 */
        align-self: flex-start;
        border-bottom-left-radius: 2px;
    }
    .chat-input-area {
        border-top: 1px solid #e2e8f0; /* slate-200 */
        padding: 0.75rem;
        display: flex;
        gap: 0.5rem;
    }
    .chat-input-area input {
        flex-grow: 1;
        border: 1px solid #cbd5e1; /* slate-300 */
        border-radius: 8px;
        padding: 0.75rem;
        outline: none;
    }
    .chat-input-area input:focus {
        border-color: #0ea5e9; /* sky-500 */
        box-shadow: 0 0 0 2px rgba(14, 165, 233, 0.2);
    }
    .chat-input-area button {
        background-color: #0ea5e9; /* sky-500 */
        color: white;
        border: none;
        border-radius: 8px;
        padding: 0 1rem;
        cursor: pointer;
    }
</style>

<div id="chatbot-bubble">
    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20.94c1.5 0 2.75 1.06 4 1.06 3 0 6-8 6-12.5A4.5 4.5 0 0 0 17.5 5c-.55 0-1 .45-1 1v2c0 .55.45 1 1 1h.5c1.38 0 2.5 1.12 2.5 2.5S21.38 14 20 14h-2.5c-.28 0-.5.22-.5.5v.5c0 .28.22.5.5.5H18c1.38 0 2.5 1.12 2.5 2.5s-1.12 2.5-2.5 2.5-2.5-1.12-2.5-2.5v-.5c0-.28-.22-.5-.5-.5h-1c-.28 0-.5.22-.5.5v.5c0 .28.22.5.5.5H14c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1v-2c0-.55-.45-1-1-1h-.5c-1.38 0-2.5-1.12-2.5-2.5S6.62 10 8 10h2.5c.28 0 .5-.22.5-.5v-.5c0-.28-.22-.5-.5-.5H8c-1.38 0-2.5-1.12-2.5-2.5S6.62 4 8 4s2.5 1.12 2.5 2.5v.5c0 .28.22.5.5.5h1c.28 0 .5-.22.5-.5v-.5c0-.28-.22-.5-.5-.5H12c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1v2c0 .55.45 1 1 1h.5c1.38 0 2.5 1.12 2.5 2.5s-1.12 2.5-2.5 2.5H12a.5.5 0 0 0-.5.5v.5c0 .28.22.5.5.5h.5c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1z"></path></svg>
</div>

<div id="chatbot-window">
    <div class="chat-header">اسأل عبدالله</div>
    <div class="chat-body" id="chat-body">
        <div class="chat-message bot">مرحباً بك في منصة إبداع تعز! كيف يمكنني مساعدتك اليوم؟</div>
    </div>
    <div class="chat-input-area">
        <input type="text" id="chat-input" placeholder="اكتب سؤالك هنا..." autocomplete="off">
        <button id="chat-send-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
        </button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const bubble = document.getElementById('chatbot-bubble');
    const chatbotWindow = document.getElementById('chatbot-window');
    const chatBody = document.getElementById('chat-body');
    const chatInput = document.getElementById('chat-input');
    const sendBtn = document.getElementById('chat-send-btn');

    bubble.addEventListener('click', () => {
        chatbotWindow.classList.toggle('open');
    });

    function sendMessage() {
        const messageText = chatInput.value.trim();
        if (messageText === '') return;

        // Display user message
        const userMessage = document.createElement('div');
        userMessage.className = 'chat-message user';
        userMessage.textContent = messageText;
        chatBody.appendChild(userMessage);

        chatInput.value = '';
        chatBody.scrollTop = chatBody.scrollHeight;

        // Show typing indicator and get bot response
        const typingIndicator = document.createElement('div');
        typingIndicator.className = 'chat-message bot';
        typingIndicator.textContent = 'يكتب...';
        chatBody.appendChild(typingIndicator);
        chatBody.scrollTop = chatBody.scrollHeight;

        fetch('/Ibdaa-Taiz/api/ask_abdullah.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `message=${encodeURIComponent(messageText)}`
        })
        .then(response => response.json())
        .then(data => {
            typingIndicator.textContent = data.reply || 'عفواً، لم أتمكن من فهم ذلك.';
        })
        .catch(error => {
            console.error('Chatbot Error:', error);
            typingIndicator.textContent = 'عفواً، حدث خطأ ما.';
        });
    }

    sendBtn.addEventListener('click', sendMessage);
    chatInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });
});
</script>
