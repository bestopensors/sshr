/**
 * Live Chat Widget
 */
(function() {
    const API_URL = 'chat-api.php';
    let conversationId = null;
    let lastMessageId = 0;
    let pollInterval = null;
    let visitorId = null;

    // Generate or get visitor ID
    function getVisitorId() {
        let id = localStorage.getItem('chat_visitor_id');
        if (!id) {
            id = 'v_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            localStorage.setItem('chat_visitor_id', id);
        }
        return id;
    }

    // DOM Elements
    const widget = document.getElementById('chat-widget');
    const toggle = document.getElementById('chat-toggle');
    const chatBox = document.getElementById('chat-box');
    const closeBtn = document.getElementById('chat-close');
    const messagesContainer = document.getElementById('chat-messages');
    const form = document.getElementById('chat-form');
    const input = document.getElementById('chat-input');
    const badge = document.querySelector('.chat-badge');
    const iconOpen = document.querySelector('.chat-icon-open');
    const iconClose = document.querySelector('.chat-icon-close');

    if (!widget) return;

    visitorId = getVisitorId();

    // Toggle chat
    toggle.addEventListener('click', function() {
        const isOpen = chatBox.style.display !== 'none';
        if (isOpen) {
            closeChat();
        } else {
            openChat();
        }
    });

    closeBtn.addEventListener('click', closeChat);

    function openChat() {
        chatBox.style.display = 'flex';
        iconOpen.style.display = 'none';
        iconClose.style.display = 'block';
        badge.style.display = 'none';
        input.focus();
        
        if (!conversationId) {
            startConversation();
        } else {
            startPolling();
        }
    }

    function closeChat() {
        chatBox.style.display = 'none';
        iconOpen.style.display = 'block';
        iconClose.style.display = 'none';
        stopPolling();
    }

    // Start conversation
    async function startConversation() {
        try {
            const formData = new FormData();
            formData.append('action', 'start');
            formData.append('visitor_id', visitorId);

            const response = await fetch(API_URL, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                conversationId = data.conversation_id;
                localStorage.setItem('chat_conversation_id', conversationId);
                loadAllMessages();
                startPolling();
            }
        } catch (error) {
            console.error('Chat error:', error);
            showSystemMessage('Nije moguće povezati se s chatom. Pokušajte kasnije.');
        }
    }

    // Load messages
    async function loadMessages() {
        if (!conversationId) return;

        try {
            const response = await fetch(`${API_URL}?action=get&conversation_id=${conversationId}&last_id=${lastMessageId}`);
            const data = await response.json();

            if (data.success && data.messages.length > 0) {
                data.messages.forEach(msg => {
                    // Only append admin messages during polling (visitor messages added on send)
                    if (msg.sender_type === 'admin') {
                        appendMessage(msg);
                    }
                    lastMessageId = Math.max(lastMessageId, parseInt(msg.id));
                });
                scrollToBottom();
            }
        } catch (error) {
            console.error('Load messages error:', error);
        }
    }
    
    // Initial load - get all messages
    async function loadAllMessages() {
        if (!conversationId) return;

        try {
            const response = await fetch(`${API_URL}?action=get&conversation_id=${conversationId}&last_id=0`);
            const data = await response.json();

            if (data.success && data.messages.length > 0) {
                data.messages.forEach(msg => {
                    appendMessage(msg);
                    lastMessageId = Math.max(lastMessageId, parseInt(msg.id));
                });
                scrollToBottom();
            }
        } catch (error) {
            console.error('Load messages error:', error);
        }
    }

    // Append message to chat
    function appendMessage(msg) {
        const div = document.createElement('div');
        div.className = `chat-message chat-message--${msg.sender_type}`;
        div.innerHTML = `
            <div class="chat-message-content">${escapeHtml(msg.message)}</div>
            <div class="chat-message-time">${formatTime(msg.created_at)}</div>
        `;
        messagesContainer.appendChild(div);
    }

    function showSystemMessage(text) {
        const div = document.createElement('div');
        div.className = 'chat-message chat-message--system';
        div.innerHTML = `<div class="chat-message-content">${escapeHtml(text)}</div>`;
        messagesContainer.appendChild(div);
        scrollToBottom();
    }

    // Send message
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const message = input.value.trim();
        if (!message) {
            input.classList.add('error');
            setTimeout(() => input.classList.remove('error'), 500);
            return;
        }
        if (!conversationId) return;

        input.value = '';
        input.disabled = true;

        try {
            const formData = new FormData();
            formData.append('action', 'send');
            formData.append('conversation_id', conversationId);
            formData.append('message', message);
            formData.append('sender_type', 'visitor');

            const response = await fetch(API_URL, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                // Update lastMessageId to skip this message in polling
                lastMessageId = Math.max(lastMessageId, data.message_id);
                
                appendMessage({
                    id: data.message_id,
                    sender_type: 'visitor',
                    message: message,
                    created_at: new Date().toISOString()
                });
                scrollToBottom();
            }
        } catch (error) {
            console.error('Send error:', error);
            showSystemMessage('Poruka nije poslana. Pokušajte ponovno.');
        }

        input.disabled = false;
        input.focus();
    });

    // Polling for new messages
    function startPolling() {
        stopPolling();
        pollInterval = setInterval(loadMessages, 3000);
    }

    function stopPolling() {
        if (pollInterval) {
            clearInterval(pollInterval);
            pollInterval = null;
        }
    }

    // Helper functions
    function scrollToBottom() {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatTime(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleTimeString('hr-HR', { hour: '2-digit', minute: '2-digit' });
    }

    // Check for existing conversation on load
    const savedConversationId = localStorage.getItem('chat_conversation_id');
    if (savedConversationId) {
        conversationId = parseInt(savedConversationId);
    }

    // Background polling for unread messages (when chat is closed)
    async function checkUnread() {
        if (!conversationId || chatBox.style.display !== 'none') return;
        
        try {
            const response = await fetch(`${API_URL}?action=get&conversation_id=${conversationId}&last_id=${lastMessageId}`);
            const data = await response.json();
            
            if (data.success && data.messages.length > 0) {
                const adminMessages = data.messages.filter(m => m.sender_type === 'admin');
                if (adminMessages.length > 0) {
                    badge.textContent = adminMessages.length;
                    badge.style.display = 'flex';
                }
            }
        } catch (error) {
            // Silent fail
        }
    }

    // Check for unread messages periodically
    setInterval(checkUnread, 10000);
})();

