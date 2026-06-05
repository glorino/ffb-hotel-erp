/**
 * LIVE-CHAT.JS — Luxury Hospitality ERP | Live Chat Widget
 * Handles the floating chat widget with polling-based messaging.
 */

'use strict';

const ChatApp = (function () {
  // ── Config ──
  const CONFIG = {
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    baseUrl: document.querySelector('base')?.getAttribute('href') || '/',
    endpoints: {
      start: 'ajax/chat-start.php',
      send: 'ajax/chat-send.php',
      poll: 'ajax/chat-poll.php',
      close: 'ajax/chat-close.php',
      typing: 'ajax/chat-typing.php'
    },
    pollInterval: 3000,
    maxPollInterval: 10000,
    typingTimeout: 1500,
    soundEnabled: true,
    mobileBreakpoint: 576
  };

  // ── State ──
  let state = {
    sessionId: null,
    isOpen: false,
    isMinimized: false,
    isFullscreen: false,
    isPolling: false,
    lastMessageId: 0,
    isTyping: false,
    typingTimer: null,
    reconnectAttempts: 0,
    maxReconnectAttempts: 5,
    soundEnabled: CONFIG.soundEnabled
  };

  // ── Cache DOM ──
  let els = {};

  function cacheElements () {
    els = {
      button: document.querySelector('.chat-button'),
      popup: document.querySelector('.chat-popup'),
      messages: document.querySelector('.chat-messages'),
      inputArea: document.querySelector('.chat-input-area'),
      textarea: document.querySelector('.chat-input-area textarea'),
      sendBtn: document.querySelector('.chat-send-btn'),
      preForm: document.querySelector('.chat-pre-form'),
      minimizeBtn: document.querySelector('[data-chat-minimize]'),
      closeBtn: document.querySelector('[data-chat-close]'),
      fullscreenBtn: document.querySelector('[data-chat-fullscreen]'),
      unreadBadge: document.querySelector('.chat-button .unread-badge'),
      typingIndicator: document.querySelector('.chat-messages .typing-indicator'),
      chatHeader: document.querySelector('.chat-header'),
      statusDot: document.querySelector('.chat-status-dot'),
      statusText: document.querySelector('.chat-status .status-text')
    };
  }

  // ── Init ──
  function init () {
    cacheElements();
    if (!els.button) return;

    initToggle();
    initPreChatForm();
    initSendMessage();
    initHeaderActions();
    initTextareaAutoResize();
    initKeyboardShortcuts();
    initSoundToggle();
    checkExistingSession();
  }

  // ── Toggle Chat ──
  function initToggle () {
    els.button.addEventListener('click', function () {
      if (state.isOpen) {
        closeChat();
      } else {
        openChat();
      }
    });
  }

  function openChat () {
    state.isOpen = true;
    els.button?.classList.add('active');
    els.popup?.classList.add('open');

    // Hide unread badge
    if (els.unreadBadge) els.unreadBadge.style.display = 'none';

    // If we have a session, start polling
    if (state.sessionId) {
      startPolling();
    }

    // Scroll to bottom
    scrollToBottom();
  }

  function closeChat () {
    state.isOpen = false;
    els.button?.classList.remove('active');
    els.popup?.classList.remove('open');
    stopPolling();

    // Notify backend if session exists
    if (state.sessionId) {
      navigator.sendBeacon(
        CONFIG.baseUrl + CONFIG.endpoints.close,
        'session_id=' + encodeURIComponent(state.sessionId) +
        '&csrf_token=' + encodeURIComponent(CONFIG.csrfToken)
      );
    }

    // Reset fullscreen
    if (state.isFullscreen) {
      exitFullscreen();
    }
  }

  // ── Pre-Chat Form ──
  function initPreChatForm () {
    if (!els.preForm) return;

    const form = els.preForm.querySelector('form');
    if (!form) return;

    form.addEventListener('submit', function (e) {
      e.preventDefault();

      const name = this.querySelector('[name="name"]');
      const email = this.querySelector('[name="email"]');

      if (!name?.value?.trim()) {
        highlightField(name);
        return;
      }

      if (email?.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
        highlightField(email);
        return;
      }

      const btn = this.querySelector('button');
      btn.disabled = true;
      btn.textContent = 'Connecting...';

      const formData = new FormData(this);

      fetch(CONFIG.baseUrl + CONFIG.endpoints.start, {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-Token': CONFIG.csrfToken
        }
      })
        .then(function (res) { return res.json(); })
        .then(function (data) {
          if (data.session_id) {
            state.sessionId = data.session_id;
            els.preForm.style.display = 'none';
            els.inputArea.style.display = 'flex';
            els.messages.style.display = 'flex';

            // Set hidden session field
            if (els.textarea) els.textarea.dataset.sessionId = data.session_id;

            // Add system welcome message
            addMessage('receptionist', data.welcome_message || 'Welcome! How can we help you today?', 'system');

            // Start polling
            startPolling();

            // Dispatch event
            document.dispatchEvent(new CustomEvent('chat:started', { detail: { sessionId: state.sessionId } }));
          } else {
            showError(data.message || 'Could not start chat');
            btn.disabled = false;
            btn.textContent = 'Start Chat';
          }
        })
        .catch(function () {
          showError('Connection error. Please try again.');
          btn.disabled = false;
          btn.textContent = 'Start Chat';
        });
    });
  }

  // ── Send Message ──
  function initSendMessage () {
    if (!els.sendBtn || !els.textarea) return;

    els.sendBtn.addEventListener('click', sendMessage);

    els.textarea.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
      }
    });

    // Typing indicator
    els.textarea.addEventListener('input', function () {
      if (!state.sessionId) return;

      clearTimeout(state.typingTimer);
      if (!state.isTyping) {
        state.isTyping = true;
        notifyTyping(true);
      }

      state.typingTimer = setTimeout(function () {
        state.isTyping = false;
        notifyTyping(false);
      }, CONFIG.typingTimeout);
    });
  }

  function sendMessage () {
    if (!els.textarea) return;
    const text = els.textarea.value.trim();
    if (!text) return;

    const sessionId = state.sessionId || els.textarea.dataset.sessionId;
    if (!sessionId) {
      showError('No active chat session');
      return;
    }

    // Disable send
    els.sendBtn.disabled = true;
    els.textarea.disabled = true;

    // Add customer message immediately
    addMessage('customer', text);
    els.textarea.value = '';
    autoResizeTextarea();

    const formData = new FormData();
    formData.append('session_id', sessionId);
    formData.append('message', text);

    fetch(CONFIG.baseUrl + CONFIG.endpoints.send, {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-Token': CONFIG.csrfToken
      }
    })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (data.success) {
          state.lastMessageId = data.message_id || state.lastMessageId;
        } else {
          showError(data.message || 'Failed to send');
        }
      })
      .catch(function () {
        // Message was already displayed, so just log
        console.warn('Chat: message sent but server confirmation failed');
      })
      .finally(function () {
        els.sendBtn.disabled = false;
        els.textarea.disabled = false;
        els.textarea.focus();
      });
  }

  // ── Poll for New Messages ──
  function startPolling () {
    if (state.isPolling) return;
    state.isPolling = true;

    function poll () {
      if (!state.isPolling || !state.sessionId) return;

      fetch(CONFIG.baseUrl + CONFIG.endpoints.poll + '?session_id=' + encodeURIComponent(state.sessionId) + '&after=' + state.lastMessageId, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-Token': CONFIG.csrfToken
        }
      })
        .then(function (res) {
          if (!res.ok) throw new Error('Poll failed');
          return res.json();
        })
        .then(function (data) {
          state.reconnectAttempts = 0;

          if (data.messages && data.messages.length) {
            var hasNew = false;
            data.messages.forEach(function (msg) {
              // Only add if not our own message (already displayed)
              if (msg.sender !== 'customer' || msg.id > state.lastMessageId) {
                addMessage(msg.sender === 'customer' ? 'customer' : 'receptionist', msg.message);
                hasNew = true;
              }
              if (msg.id > state.lastMessageId) {
                state.lastMessageId = msg.id;
              }
            });

            // Play sound for new messages when chat is open but not focused
            if (hasNew && !document.hasFocus()) {
              playNotificationSound();
            }
          }

          // Update online status
          if (data.online !== undefined) {
            updateStatus(data.online);
          }

          // Check if chat was closed by admin
          if (data.closed) {
            addMessage('receptionist', 'This chat has been closed. Thank you!');
            stopPolling();
            els.textarea.disabled = true;
            els.sendBtn.disabled = true;
            return;
          }

          // Schedule next poll
          if (state.isPolling) {
            setTimeout(poll, CONFIG.pollInterval);
          }
        })
        .catch(function () {
          state.reconnectAttempts++;
          if (state.reconnectAttempts < state.maxReconnectAttempts) {
            var backoff = Math.min(CONFIG.pollInterval * Math.pow(1.5, state.reconnectAttempts), CONFIG.maxPollInterval);
            setTimeout(poll, backoff);
          } else {
            addMessage('receptionist', 'Connection lost. Please refresh to reconnect.', 'system');
            stopPolling();
          }
        });
    }

    // Start first poll after a short delay
    setTimeout(poll, 1000);
  }

  function stopPolling () {
    state.isPolling = false;
  }

  // ── Display Messages ──
  function addMessage (sender, text, type) {
    if (!els.messages) return;

    const msgDiv = document.createElement('div');
    msgDiv.className = 'chat-message ' + sender;

    const bubble = document.createElement('div');
    bubble.className = 'bubble';

    if (type === 'system') {
      msgDiv.style.justifyContent = 'center';
      msgDiv.style.maxWidth = '100%';
      bubble.style.background = 'transparent';
      bubble.style.border = 'none';
      bubble.style.textAlign = 'center';
      bubble.style.fontSize = '0.8rem';
      bubble.style.color = '#9ca3af';
      bubble.style.padding = '8px 0';
    } else {
      const senderLabel = document.createElement('div');
      senderLabel.className = 'message-sender';
      senderLabel.textContent = sender === 'customer' ? 'You' : 'Reception';
      msgDiv.appendChild(senderLabel);
    }

    bubble.textContent = text;

    if (type !== 'system') {
      const time = document.createElement('span');
      time.className = 'message-time';
      time.textContent = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
      bubble.appendChild(time);
    }

    msgDiv.appendChild(bubble);
    els.messages.appendChild(msgDiv);

    scrollToBottom();
  }

  // ── Auto-scroll ──
  function scrollToBottom () {
    if (els.messages) {
      els.messages.scrollTop = els.messages.scrollHeight;
    }
  }

  // ── Header Actions ──
  function initHeaderActions () {
    // Minimize
    els.minimizeBtn?.addEventListener('click', function () {
      if (state.isFullscreen) {
        exitFullscreen();
      }
      els.popup?.classList.remove('open');
      state.isOpen = false;
      els.button?.classList.remove('active');
    });

    // Close
    els.closeBtn?.addEventListener('click', function () {
      closeChat();
    });

    // Fullscreen (mobile)
    els.fullscreenBtn?.addEventListener('click', function () {
      if (state.isFullscreen) {
        exitFullscreen();
      } else {
        enterFullscreen();
      }
    });
  }

  function enterFullscreen () {
    state.isFullscreen = true;
    els.popup?.classList.add('chat-fullscreen');
    els.fullscreenBtn.innerHTML = '&#8722;'; // Minimize icon
  }

  function exitFullscreen () {
    state.isFullscreen = false;
    els.popup?.classList.remove('chat-fullscreen');
    els.fullscreenBtn.innerHTML = '&#9974;'; // Fullscreen icon
  }

  // ── Textarea Auto-resize ──
  function initTextareaAutoResize () {
    if (!els.textarea) return;
    els.textarea.addEventListener('input', autoResizeTextarea);
  }

  function autoResizeTextarea () {
    if (!els.textarea) return;
    els.textarea.style.height = 'auto';
    els.textarea.style.height = Math.min(els.textarea.scrollHeight, 100) + 'px';
  }

  // ── Keyboard Shortcuts ──
  function initKeyboardShortcuts () {
    document.addEventListener('keydown', function (e) {
      // Escape to close
      if (e.key === 'Escape' && state.isOpen) {
        if (state.isFullscreen) {
          exitFullscreen();
        } else {
          closeChat();
        }
      }
    });
  }

  // ── Sound ──
  function initSoundToggle () {
    // Sound toggle can be triggered by data attribute
    document.querySelector('[data-chat-sound]')?.addEventListener('click', function () {
      state.soundEnabled = !state.soundEnabled;
      this.classList.toggle('muted');
    });
  }

  function playNotificationSound () {
    if (!state.soundEnabled) return;
    try {
      const ctx = new (window.AudioContext || window.webkitAudioContext)();
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.frequency.value = 800;
      osc.type = 'sine';
      gain.gain.setValueAtTime(0.3, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.2);
      osc.start(ctx.currentTime);
      osc.stop(ctx.currentTime + 0.2);
    } catch (e) {
      // Audio not supported, silently fail
    }
  }

  // ── Status Update ──
  function updateStatus (online) {
    const statusEl = els.chatHeader?.querySelector('.chat-status');
    if (statusEl) {
      statusEl.className = 'chat-status ' + (online ? 'online' : 'offline');
      if (els.statusText) els.statusText.textContent = online ? 'Online' : 'Offline';
    }
  }

  // ── Typing Notification ──
  function notifyTyping (isTyping) {
    if (!state.sessionId) return;

    // Show/hide typing indicator
    if (els.typingIndicator) {
      els.typingIndicator.style.display = isTyping ? 'flex' : 'none';
      scrollToBottom();
    }

    // Send typing status to server
    fetch(CONFIG.baseUrl + CONFIG.endpoints.typing, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-Token': CONFIG.csrfToken
      },
      body: 'session_id=' + encodeURIComponent(state.sessionId) + '&typing=' + (isTyping ? '1' : '0')
    }).catch(function () { /* silent */ });
  }

  // ── Check Existing Session ──
  function checkExistingSession () {
    const sessionInput = document.querySelector('[name="chat_session_id"]');
    if (sessionInput?.value) {
      state.sessionId = sessionInput.value;
      els.preForm.style.display = 'none';
      els.inputArea.style.display = 'flex';
      els.messages.style.display = 'flex';
    }
  }

  // ── Utility ──
  function highlightField (field) {
    field?.classList.add('error');
    field?.focus();
    setTimeout(function () { field?.classList.remove('error'); }, 2000);
  }

  function showError (message) {
    const errorEl = document.querySelector('.chat-error');
    if (errorEl) {
      errorEl.textContent = message;
      errorEl.style.display = 'block';
      setTimeout(function () { errorEl.style.display = 'none'; }, 4000);
    }
  }

  // ── Public API ──
  return {
    init: init,
    openChat: openChat,
    closeChat: closeChat,
    sendMessage: sendMessage,
    state: state
  };
})();

// ── Bootstrap ──
document.addEventListener('DOMContentLoaded', ChatApp.init);
