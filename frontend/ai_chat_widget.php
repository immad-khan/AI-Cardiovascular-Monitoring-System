<?php
// frontend/ai_chat_widget.php
// Floating AI Assistant Chat Widget - Include this in pages that need the chat
// Only shown to patients
$show_ai_chat = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'patient';
?>
<?php if ($show_ai_chat): ?>
<style>
#ai-chat-fab {
    position: fixed; bottom: 30px; right: 30px; z-index: 9999;
    width: 60px; height: 60px; border-radius: 50%;
    background: linear-gradient(135deg, #00bcd4, #0097a7);
    color: #fff; border: none; cursor: pointer;
    box-shadow: 0 4px 15px rgba(0,188,212,0.4);
    display: flex; align-items: center; justify-content: center;
    font-size: 26px; transition: all 0.3s ease;
}
#ai-chat-fab:hover { transform: scale(1.1); box-shadow: 0 6px 20px rgba(0,188,212,0.6); }
#ai-chat-fab .pulse-ring {
    position: absolute; width: 100%; height: 100%; border-radius: 50%;
    border: 2px solid #00bcd4; animation: aiPulse 2s infinite; pointer-events: none;
}
@keyframes aiPulse { 0% { transform: scale(1); opacity: 1; } 100% { transform: scale(1.5); opacity: 0; } }

#ai-chat-window {
    position: fixed; bottom: 100px; right: 30px; z-index: 10000;
    width: 380px; height: 520px; max-height: 70vh;
    background: #fff; border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    display: none; flex-direction: column; overflow: hidden;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    border: 1px solid #e0e0e0;
}
#ai-chat-window.open { display: flex; animation: chatSlideUp 0.3s ease; }
@keyframes chatSlideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

.ai-chat-header {
    background: linear-gradient(135deg, #00bcd4, #0097a7);
    color: #fff; padding: 16px 18px;
    display: flex; align-items: center; justify-content: space-between;
}
.ai-chat-header .ai-avatar {
    width: 36px; height: 36px; border-radius: 50%;
    background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center;
    font-size: 18px; margin-right: 12px;
}
.ai-chat-header .ai-info { flex: 1; }
.ai-chat-header .ai-info h4 { margin: 0; font-size: 15px; font-weight: 600; }
.ai-chat-header .ai-info small { opacity: 0.85; font-size: 11px; }
.ai-chat-header .close-chat {
    background: rgba(255,255,255,0.2); border: none; color: #fff;
    width: 30px; height: 30px; border-radius: 50%; cursor: pointer;
    font-size: 16px; display: flex; align-items: center; justify-content: center;
}

.ai-chat-messages {
    flex: 1; overflow-y: auto; padding: 16px; display: flex;
    flex-direction: column; gap: 12px; background: #f8f9fa;
}
.ai-msg {
    max-width: 85%; padding: 10px 14px; border-radius: 14px;
    font-size: 13.5px; line-height: 1.5; word-wrap: break-word;
}
.ai-msg.user {
    background: #00bcd4; color: #fff; align-self: flex-end;
    border-bottom-right-radius: 4px;
}
.ai-msg.bot {
    background: #fff; color: #333; align-self: flex-start;
    border-bottom-left-radius: 4px;
    border: 1px solid #e8e8e8;
}
.ai-msg.typing { color: #999; font-style: italic; }
.ai-msg.bot strong { color: #0097a7; }

.ai-chat-input {
    display: flex; padding: 12px; gap: 8px;
    border-top: 1px solid #eee; background: #fff;
}
.ai-chat-input input {
    flex: 1; border: 1px solid #ddd; border-radius: 20px;
    padding: 10px 16px; font-size: 13px; outline: none;
    transition: border-color 0.2s;
}
.ai-chat-input input:focus { border-color: #00bcd4; }
.ai-chat-input button {
    width: 40px; height: 40px; border-radius: 50%; border: none;
    background: #00bcd4; color: #fff; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; transition: background 0.2s;
}
.ai-chat-input button:hover { background: #0097a7; }
.ai-chat-input button:disabled { background: #ccc; cursor: not-allowed; }

.ai-suggestions {
    display: flex; flex-wrap: wrap; gap: 6px;
    padding: 0 16px 10px; background: #f8f9fa;
}
.ai-suggestion {
    background: #fff; border: 1px solid #00bcd4; color: #0097a7;
    border-radius: 16px; padding: 5px 12px; font-size: 11.5px;
    cursor: pointer; transition: all 0.2s; white-space: nowrap;
}
.ai-suggestion:hover { background: #00bcd4; color: #fff; }
</style>

<!-- Chat FAB -->
<button id="ai-chat-fab" onclick="toggleAiChat()" title="DigiHealth AI Assistant">
    <div class="pulse-ring"></div>
    <i class="zmdi zmdi-robot"></i>
</button>

<!-- Chat Window -->
<div id="ai-chat-window">
    <div class="ai-chat-header">
        <div class="ai-avatar"><i class="zmdi zmdi-robot"></i></div>
        <div class="ai-info">
            <h4>DigiHealth AI</h4>
            <small>Your personal health assistant</small>
        </div>
        <button class="close-chat" onclick="toggleAiChat()"><i class="zmdi zmdi-close"></i></button>
    </div>
    <div class="ai-chat-messages" id="ai-chat-messages">
        <div class="ai-msg bot">Hello! I'm your DigiHealth AI assistant. I can see your health records, ECG data, and vital signs. Ask me anything about your health data!</div>
    </div>
    <div class="ai-suggestions" id="ai-suggestions">
        <span class="ai-suggestion" onclick="sendAiSuggestion(this)">How are my vitals?</span>
        <span class="ai-suggestion" onclick="sendAiSuggestion(this)">Explain my ECG results</span>
        <span class="ai-suggestion" onclick="sendAiSuggestion(this)">What is my heart risk?</span>
        <span class="ai-suggestion" onclick="sendAiSuggestion(this)">What do my HRV numbers mean?</span>
    </div>
    <div class="ai-chat-input">
        <input type="text" id="ai-chat-input" placeholder="Ask about your health..." 
               onkeydown="if(event.key==='Enter')sendAiMessage()">
        <button id="ai-send-btn" onclick="sendAiMessage()"><i class="zmdi zmdi-send"></i></button>
    </div>
</div>

<script>
var aiChatOpen = false;
var aiChatHistory = [];
var aiChatSending = false;

function toggleAiChat() {
    aiChatOpen = !aiChatOpen;
    var win = document.getElementById('ai-chat-window');
    var fab = document.getElementById('ai-chat-fab');
    if (aiChatOpen) {
        win.classList.add('open');
        fab.innerHTML = '<i class="zmdi zmdi-close"></i>';
        document.getElementById('ai-chat-input').focus();
    } else {
        win.classList.remove('open');
        fab.innerHTML = '<div class="pulse-ring"></div><i class="zmdi zmdi-robot"></i>';
    }
}

function sendAiSuggestion(el) {
    document.getElementById('ai-chat-input').value = el.textContent;
    sendAiMessage();
}

function sendAiMessage() {
    if (aiChatSending) return;
    var input = document.getElementById('ai-chat-input');
    var msg = input.value.trim();
    if (!msg) return;

    aiChatSending = true;
    input.value = '';
    document.getElementById('ai-send-btn').disabled = true;

    // Hide suggestions after first message
    var sugDiv = document.getElementById('ai-suggestions');
    if (sugDiv) sugDiv.style.display = 'none';

    // Add user message
    appendChat('user', msg);
    aiChatHistory.push({ role: 'user', content: msg });

    // Show typing
    var typingEl = appendChat('bot', 'Thinking...', true);

    fetch('../api/ai_chat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: msg, history: aiChatHistory.slice(-10) }),
        credentials: 'same-origin'
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        typingEl.remove();
        if (data.success) {
            appendChat('bot', data.reply);
            aiChatHistory.push({ role: 'assistant', content: data.reply });
        } else {
            appendChat('bot', data.message || 'Sorry, I encountered an error. Please try again.');
        }
        aiChatSending = false;
        document.getElementById('ai-send-btn').disabled = false;
    })
    .catch(function() {
        typingEl.remove();
        appendChat('bot', 'Network error. Please check your connection.');
        aiChatSending = false;
        document.getElementById('ai-send-btn').disabled = false;
    });
}

function appendChat(type, text, isTyping) {
    var container = document.getElementById('ai-chat-messages');
    var div = document.createElement('div');
    div.className = 'ai-msg ' + type + (isTyping ? ' typing' : '');
    
    if (isTyping) {
        div.textContent = text;
    } else {
        // Basic markdown-like formatting
        var formatted = text
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\n\n/g, '<br><br>')
            .replace(/\n/g, '<br>')
            .replace(/- (.*?)(<br>|$)/g, '&bull; $1$2');
        div.innerHTML = formatted;
    }
    
    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
    return div;
}
</script>
<?php endif; ?>
