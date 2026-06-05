<?php
$chat_icon = getSetting('chat_icon', 'bi-chat-dots');
$chat_title = getSetting('chat_title', 'FFB Concierge');
$chat_subtitle = getSetting('chat_subtitle', 'How can we help you today?');
$greeting_message = getSetting('chat_greeting', 'Welcome to FFB Hotel! How may we assist you?');
$widget_enabled = getSetting('live_chat_enabled', '1') === '1';

if (!$widget_enabled) {
    return;
}
?>
<div id="liveChatWidget" class="live-chat-widget" data-chat-enabled="1">
    <div class="chat-button" id="chatToggleBtn" role="button" tabindex="0" aria-label="Open live chat">
        <div class="chat-button-icon">
            <i class="bi <?php echo htmlspecialchars($chat_icon); ?>"></i>
        </div>
        <div class="chat-button-label">Chat</div>
        <div class="chat-notification-dot" id="chatNotifDot" style="display:none;"></div>
    </div>

    <div class="chat-popup" id="chatPopup">
        <div class="chat-popup-header">
            <div class="chat-header-info">
                <div class="chat-header-avatar">
                    <span class="online-indicator"></span>
                    <i class="bi bi-headset"></i>
                </div>
                <div class="chat-header-text">
                    <h5 class="chat-header-title"><?php echo htmlspecialchars($chat_title); ?></h5>
                    <span class="chat-header-status online">Online</span>
                </div>
            </div>
            <div class="chat-header-actions">
                <button type="button" class="chat-btn-icon" id="chatMinimizeBtn" aria-label="Minimize chat">
                    <i class="bi bi-dash"></i>
                </button>
                <button type="button" class="chat-btn-icon" id="chatCloseBtn" aria-label="Close chat">
                    <i class="bi bi-x"></i>
                </button>
            </div>
        </div>

        <div class="chat-popup-body">
            <div class="chat-loading" id="chatLoading" style="display:none;">
                <div class="chat-loading-spinner">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p>Connecting to concierge...</p>
                </div>
            </div>

            <div class="chat-preform" id="chatPreForm">
                <div class="chat-preform-content">
                    <div class="chat-welcome-message">
                        <p><?php echo htmlspecialchars($greeting_message); ?></p>
                    </div>
                    <form id="chatPreFormFields" class="chat-preform-fields">
                        <?php echo csrf_field(); ?>
                        <div class="mb-3">
                            <label for="chatName" class="form-label">Your Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="chatName" name="name" placeholder="John Doe" required>
                        </div>
                        <div class="mb-3">
                            <label for="chatEmail" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="chatEmail" name="email" placeholder="john@example.com" required>
                        </div>
                        <div class="mb-3">
                            <label for="chatPhone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="chatPhone" name="phone" placeholder="+1 234 567 8900">
                        </div>
                        <button type="submit" class="btn btn-primary w-100" id="chatStartBtn">
                            <i class="bi bi-chat-dots"></i> Start Chat
                        </button>
                    </form>
                </div>
            </div>

            <div class="chat-messages" id="chatMessages" style="display:none;">
                <div class="chat-messages-container" id="chatMessagesContainer">
                    <div class="chat-message chat-message-agent">
                        <div class="chat-message-avatar">
                            <i class="bi bi-headset"></i>
                        </div>
                        <div class="chat-message-content">
                            <p><?php echo htmlspecialchars($greeting_message); ?></p>
                            <span class="chat-message-time"><?php echo date('h:i A'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="chat-popup-footer" id="chatFooter" style="display:none;">
            <form id="chatSendForm" class="chat-send-form">
                <?php echo csrf_field(); ?>
                <div class="input-group">
                    <input type="text" class="form-control" id="chatMessageInput" name="message" placeholder="Type your message..." autocomplete="off" required>
                    <button type="submit" class="btn btn-primary" id="chatSendBtn" aria-label="Send message">
                        <i class="bi bi-send"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.live-chat-widget {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 9999;
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
}

.chat-button {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #d4af37, #b8962e);
    color: #fff;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 20px rgba(212, 175, 55, 0.4);
    transition: all 0.3s ease;
    position: relative;
}

.chat-button:hover {
    transform: scale(1.08);
    box-shadow: 0 6px 28px rgba(212, 175, 55, 0.5);
}

.chat-button-icon {
    font-size: 22px;
    line-height: 1;
}

.chat-button-label {
    font-size: 9px;
    font-weight: 600;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    margin-top: 1px;
}

.chat-notification-dot {
    width: 12px;
    height: 12px;
    background: #dc3545;
    border-radius: 50%;
    position: absolute;
    top: 4px;
    right: 4px;
    border: 2px solid #fff;
}

.chat-popup {
    position: absolute;
    bottom: 76px;
    right: 0;
    width: 380px;
    max-height: 560px;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 10px 60px rgba(0, 0, 0, 0.2);
    display: none;
    flex-direction: column;
    overflow: hidden;
    animation: chatSlideUp 0.3s ease;
}

@keyframes chatSlideUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.chat-popup.active {
    display: flex;
}

.chat-popup-header {
    background: linear-gradient(135deg, #1a1a2e, #16213e);
    color: #fff;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.chat-header-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.chat-header-avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    position: relative;
}

.online-indicator {
    width: 10px;
    height: 10px;
    background: #28a745;
    border-radius: 50%;
    border: 2px solid #1a1a2e;
    position: absolute;
    bottom: 0;
    right: 0;
}

.chat-header-title {
    font-size: 15px;
    font-weight: 600;
    margin: 0;
}

.chat-header-status {
    font-size: 12px;
    color: #28a745;
}

.chat-header-actions {
    display: flex;
    gap: 4px;
}

.chat-btn-icon {
    background: rgba(255, 255, 255, 0.1);
    border: none;
    color: #fff;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.2s;
}

.chat-btn-icon:hover {
    background: rgba(255, 255, 255, 0.2);
}

.chat-popup-body {
    flex: 1;
    overflow-y: auto;
    background: #f8f9fa;
    min-height: 300px;
    max-height: 360px;
}

.chat-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    min-height: 200px;
}

.chat-loading-spinner {
    text-align: center;
    color: #6c757d;
}

.chat-loading-spinner p {
    margin-top: 12px;
    font-size: 14px;
}

.chat-preform {
    padding: 24px 20px;
}

.chat-welcome-message {
    text-align: center;
    margin-bottom: 20px;
    color: #495057;
    font-size: 14px;
    line-height: 1.6;
}

.chat-preform-fields .form-label {
    font-size: 13px;
    font-weight: 500;
    color: #495057;
    margin-bottom: 4px;
}

.chat-preform-fields .form-control {
    border-radius: 10px;
    border: 1.5px solid #e0e0e0;
    padding: 10px 14px;
    font-size: 14px;
    transition: border-color 0.2s;
}

.chat-preform-fields .form-control:focus {
    border-color: #d4af37;
    box-shadow: 0 0 0 0.2rem rgba(212, 175, 55, 0.15);
}

.chat-messages {
    padding: 16px;
    display: none;
}

.chat-messages-container {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.chat-message {
    display: flex;
    gap: 10px;
    max-width: 85%;
}

.chat-message-agent {
    align-self: flex-start;
}

.chat-message-user {
    align-self: flex-end;
    flex-direction: row-reverse;
}

.chat-message-avatar {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
}

.chat-message-agent .chat-message-avatar {
    background: #1a1a2e;
    color: #d4af37;
}

.chat-message-user .chat-message-avatar {
    background: #d4af37;
    color: #fff;
}

.chat-message-content {
    background: #fff;
    padding: 10px 14px;
    border-radius: 12px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
}

.chat-message-user .chat-message-content {
    background: #1a1a2e;
    color: #fff;
}

.chat-message-content p {
    margin: 0;
    font-size: 14px;
    line-height: 1.5;
}

.chat-message-time {
    font-size: 11px;
    color: #adb5bd;
    display: block;
    margin-top: 4px;
}

.chat-message-user .chat-message-time {
    color: rgba(255, 255, 255, 0.6);
}

.chat-popup-footer {
    padding: 12px 16px;
    border-top: 1px solid #eee;
    background: #fff;
}

.chat-send-form .input-group {
    gap: 8px;
}

.chat-send-form .form-control {
    border-radius: 24px;
    border: 1.5px solid #e0e0e0;
    padding: 10px 16px;
    font-size: 14px;
}

.chat-send-form .form-control:focus {
    border-color: #d4af37;
    box-shadow: 0 0 0 0.2rem rgba(212, 175, 55, 0.15);
}

.chat-send-form .btn-primary {
    border-radius: 50%;
    width: 42px;
    height: 42px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #d4af37, #b8962e);
    border: none;
}

.chat-send-form .btn-primary:hover {
    background: linear-gradient(135deg, #c4a032, #a6851e);
}

@media (max-width: 480px) {
    .chat-popup {
        width: calc(100vw - 32px);
        right: -16px;
        bottom: 72px;
        max-height: 480px;
    }
}
</style>

<script>
(function() {
    'use strict';

    const widget = document.getElementById('liveChatWidget');
    if (!widget) return;

    const toggleBtn = document.getElementById('chatToggleBtn');
    const popup = document.getElementById('chatPopup');
    const closeBtn = document.getElementById('chatCloseBtn');
    const minimizeBtn = document.getElementById('chatMinimizeBtn');
    const preForm = document.getElementById('chatPreForm');
    const messagesEl = document.getElementById('chatMessages');
    const footer = document.getElementById('chatFooter');
    const loading = document.getElementById('chatLoading');
    const preFormFields = document.getElementById('chatPreFormFields');
    const sendForm = document.getElementById('chatSendForm');
    const messageInput = document.getElementById('chatMessageInput');
    const messagesContainer = document.getElementById('chatMessagesContainer');

    let chatSession = null;
    let pollingInterval = null;

    toggleBtn.addEventListener('click', function() {
        popup.classList.toggle('active');
        if (popup.classList.contains('active')) {
            scrollToBottom();
        }
    });

    closeBtn.addEventListener('click', function() {
        popup.classList.remove('active');
    });

    minimizeBtn.addEventListener('click', function() {
        popup.classList.remove('active');
    });

    if (preFormFields) {
        preFormFields.addEventListener('submit', function(e) {
            e.preventDefault();
            const name = document.getElementById('chatName').value.trim();
            const email = document.getElementById('chatEmail').value.trim();
            const phone = document.getElementById('chatPhone').value.trim();

            if (!name || !email) return;

            preForm.style.display = 'none';
            loading.style.display = 'flex';

            const formData = new FormData();
            formData.append('action', 'start_chat');
            formData.append('name', name);
            formData.append('email', email);
            formData.append('phone', phone);

            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            if (csrfMeta) {
                formData.append('csrf_token', csrfMeta.getAttribute('content'));
            }

            fetch('<?php echo BASE_URL; ?>modules/live-chat/start.php', {
                method: 'POST',
                body: formData
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                loading.style.display = 'none';
                if (data.success) {
                    chatSession = data.session_id;
                    messagesEl.style.display = 'block';
                    footer.style.display = 'block';
                    startPolling();
                } else {
                    preForm.style.display = 'block';
                }
            })
            .catch(function() {
                loading.style.display = 'none';
                preForm.style.display = 'block';
            });
        });
    }

    if (sendForm) {
        sendForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const message = messageInput.value.trim();
            if (!message || !chatSession) return;

            appendMessage(message, 'user');
            messageInput.value = '';

            const formData = new FormData();
            formData.append('action', 'send_message');
            formData.append('session_id', chatSession);
            formData.append('message', message);

            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            if (csrfMeta) {
                formData.append('csrf_token', csrfMeta.getAttribute('content'));
            }

            fetch('<?php echo BASE_URL; ?>modules/live-chat/send.php', {
                method: 'POST',
                body: formData
            })
            .then(function(r) { return r.json(); })
            .catch(function() {});
        });
    }

    function appendMessage(text, type) {
        const div = document.createElement('div');
        div.className = 'chat-message chat-message-' + type;

        const avatar = document.createElement('div');
        avatar.className = 'chat-message-avatar';
        avatar.innerHTML = type === 'agent' ? '<i class="bi bi-headset"></i>' : '<i class="bi bi-person"></i>';

        const content = document.createElement('div');
        content.className = 'chat-message-content';

        const p = document.createElement('p');
        p.textContent = text;
        content.appendChild(p);

        const time = document.createElement('span');
        time.className = 'chat-message-time';
        const now = new Date();
        time.textContent = now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
        content.appendChild(time);

        div.appendChild(avatar);
        div.appendChild(content);
        messagesContainer.appendChild(div);
        scrollToBottom();
    }

    function startPolling() {
        if (pollingInterval) clearInterval(pollingInterval);
        pollingInterval = setInterval(function() {
            if (!chatSession) return;
            const formData = new FormData();
            formData.append('action', 'poll_messages');
            formData.append('session_id', chatSession);

            fetch('<?php echo BASE_URL; ?>modules/live-chat/poll.php', {
                method: 'POST',
                body: formData
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.messages && data.messages.length) {
                    data.messages.forEach(function(msg) {
                        if (msg.sender === 'agent') {
                            appendMessage(msg.message, 'agent');
                        }
                    });
                }
            })
            .catch(function() {});
        }, 3000);
    }

    function scrollToBottom() {
        const body = popup.querySelector('.chat-popup-body');
        if (body) body.scrollTop = body.scrollHeight;
    }
})();
</script>
