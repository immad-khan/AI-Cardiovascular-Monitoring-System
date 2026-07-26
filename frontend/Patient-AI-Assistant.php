<?php
session_start();
include("../config/DB_Config.php");

if (!isset($_SESSION["user_type"]) || $_SESSION["user_type"] !== "patient") {
    header("Location: index.php?status=Access is denied&type=error");
    exit();
}

// Fetch patient name for greeting
$patientName = $_SESSION['username'] ?? 'Patient';
try {
    $stmt = $conn->prepare("SELECT name FROM patients WHERE email = ? LIMIT 1");
    $stmt->execute([$_SESSION["email"]]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) $patientName = explode(' ', $row['name'])[0];
} catch (PDOException $e) {}
?>
<!doctype html>
<html class="no-js" lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
<title>AI Assistant - CUST Digihealth</title>
<link rel="icon" href="favicon.ico" type="image/x-icon">
<link rel="stylesheet" href="../assets/plugins/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/color_skins.css">
<style>
    .content { padding: 0; margin: 0; height: 100vh; }
    .ai-page { display: flex; flex-direction: column; height: 100%; }
    .ai-page-header {
        background: linear-gradient(135deg, #00bcd4, #0097a7);
        color: #fff; padding: 18px 25px; display: flex; align-items: center; gap: 15px;
        flex-shrink: 0;
    }
    .ai-page-header .ai-icon { font-size: 2rem; }
    .ai-page-header h3 { margin: 0; font-size: 1.2rem; font-weight: 600; }
    .ai-page-header small { opacity: 0.85; font-size: 12px; }
    .ai-page-body { flex: 1; display: flex; overflow: hidden; }
    .ai-sidebar-panel {
        width: 280px; background: #f8f9fa; border-right: 1px solid #e0e0e0;
        display: flex; flex-direction: column; flex-shrink: 0; overflow-y: auto;
    }
    .ai-sidebar-panel .panel-section { padding: 18px; border-bottom: 1px solid #eee; }
    .ai-sidebar-panel .panel-title { font-size: 12px; font-weight: 700; color: #999; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px; }
    .ai-quick-q {
        display: block; width: 100%; text-align: left; background: #fff; border: 1px solid #e0e0e0;
        border-radius: 8px; padding: 8px 12px; font-size: 12.5px; color: #333;
        margin-bottom: 6px; cursor: pointer; transition: all 0.2s;
    }
    .ai-quick-q:hover { border-color: #00bcd4; background: #e0f7fa; color: #0097a7; }
    .ai-quick-q i { color: #00bcd4; margin-right: 6px; font-size: 14px; }
    .ai-chat-main { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
    .ai-messages {
        flex: 1; overflow-y: auto; padding: 25px;
        display: flex; flex-direction: column; gap: 16px;
        background: #fafafa;
    }
    .ai-msg-row { display: flex; gap: 10px; max-width: 75%; }
    .ai-msg-row.user { align-self: flex-end; flex-direction: row-reverse; }
    .ai-msg-row.bot { align-self: flex-start; }
    .ai-msg-avatar {
        width: 34px; height: 34px; border-radius: 50%; display: flex;
        align-items: center; justify-content: center; flex-shrink: 0; font-size: 16px;
    }
    .ai-msg-row.bot .ai-msg-avatar { background: #e0f7fa; color: #0097a7; }
    .ai-msg-row.user .ai-msg-avatar { background: #00bcd4; color: #fff; }
    .ai-msg-bubble {
        padding: 12px 16px; border-radius: 14px; font-size: 14px;
        line-height: 1.6; word-wrap: break-word;
    }
    .ai-msg-row.bot .ai-msg-bubble { background: #fff; border: 1px solid #e8e8e8; color: #333; border-bottom-left-radius: 4px; }
    .ai-msg-row.user .ai-msg-bubble { background: #00bcd4; color: #fff; border-bottom-right-radius: 4px; }
    .ai-msg-row .ai-msg-time { font-size: 10px; color: #aaa; margin-top: 4px; }
    .ai-msg-row.user .ai-msg-time { text-align: right; }
    .ai-typing { display: flex; gap: 4px; padding: 8px 0; }
    .ai-typing span { width: 8px; height: 8px; background: #bbb; border-radius: 50%; animation: aiType 1.2s infinite; }
    .ai-typing span:nth-child(2) { animation-delay: 0.2s; }
    .ai-typing span:nth-child(3) { animation-delay: 0.4s; }
    @keyframes aiType { 0%,60%,100% { transform: translateY(0); background: #bbb; } 30% { transform: translateY(-6px); background: #00bcd4; } }

    .ai-input-area {
        padding: 16px 25px; border-top: 1px solid #e0e0e0; background: #fff;
        display: flex; gap: 12px; align-items: center; flex-shrink: 0;
    }
    .ai-input-area input {
        flex: 1; border: 2px solid #e0e0e0; border-radius: 25px;
        padding: 12px 20px; font-size: 14px; outline: none; transition: border-color 0.2s;
    }
    .ai-input-area input:focus { border-color: #00bcd4; }
    .ai-input-area button {
        width: 46px; height: 46px; border-radius: 50%; border: none;
        background: #00bcd4; color: #fff; cursor: pointer; font-size: 20px;
        display: flex; align-items: center; justify-content: center;
        transition: background 0.2s; flex-shrink: 0;
    }
    .ai-input-area button:hover { background: #0097a7; }
    .ai-input-area button:disabled { background: #ccc; cursor: not-allowed; }
    .ai-disclaimer { text-align: center; font-size: 11px; color: #aaa; padding: 8px; background: #fff; border-top: 1px solid #f0f0f0; flex-shrink: 0; }

    @media (max-width: 768px) {
        .ai-sidebar-panel { display: none; }
        .ai-msg-row { max-width: 90%; }
    }
</style>
</head>
<body class="theme-cyan">
<aside id="leftsidebar" class="sidebar">
    <?php include("patient_sidebar.php") ?>
</aside>

<section class="content">
<div class="ai-page">
    <div class="ai-page-header">
        <i class="zmdi zmdi-robot ai-icon"></i>
        <div>
            <h3>DigiHealth AI Assistant</h3>
            <small>Ask me anything about your health data, vitals, ECG results, or cardiovascular wellness</small>
        </div>
    </div>
    <div class="ai-page-body">
        <!-- Sidebar: Quick Questions -->
        <div class="ai-sidebar-panel">
            <div class="panel-section">
                <div class="panel-title"><i class="zmdi zmdi-flash m-r-5"></i>Quick Questions</div>
                <button class="ai-quick-q" onclick="sendQuick(this)"><i class="zmdi zmdi-favorite"></i>How are my heart rate and vitals?</button>
                <button class="ai-quick-q" onclick="sendQuick(this)"><i class="zmdi zmdi-chart-line"></i>Explain my HRV metrics</button>
                <button class="ai-quick-q" onclick="sendQuick(this)"><i class="zmdi zmdi-assignment"></i>What do my ECG results mean?</button>
                <button class="ai-quick-q" onclick="sendQuick(this)"><i class="zmdi zmdi-shield-security"></i>What is my cardiac risk level?</button>
                <button class="ai-quick-q" onclick="sendQuick(this)"><i class="zmdi zmdi-alert-circle"></i>Explain my arrhythmia flags</button>
                <button class="ai-quick-q" onclick="sendQuick(this)"><i class="zmdi zmdi-lightbulb"></i>Give me health tips</button>
                <button class="ai-quick-q" onclick="sendQuick(this)"><i class="zmdi zmdi-phone-in-talk"></i>Should I see a doctor?</button>
            </div>
            <div class="panel-section">
                <div class="panel-title"><i class="zmdi zmdi-info-outline m-r-5"></i>About</div>
                <p style="font-size:12px;color:#888;line-height:1.5;">
                    I can see your real-time ECG data, vital signs (HR, respiration), HRV metrics, AI rhythm predictions, and alert history. Ask me questions about any of your health data.
                </p>
                <p style="font-size:11px;color:#bbb;margin:0;">
                    Powered by LLaMA 3.3 70B via Groq
                </p>
            </div>
        </div>

        <!-- Main Chat Area -->
        <div class="ai-chat-main">
            <div class="ai-messages" id="ai-messages">
                <div class="ai-msg-row bot">
                    <div class="ai-msg-avatar"><i class="zmdi zmdi-robot"></i></div>
                    <div>
                        <div class="ai-msg-bubble">
                            Hello <strong><?php echo htmlspecialchars($patientName); ?></strong>! I'm your DigiHealth AI health assistant. I can analyze your ECG readings, vital signs, HRV metrics, and monitoring data.<br><br>
                            How can I help you today?
                        </div>
                        <div class="ai-msg-time">Just now</div>
                    </div>
                </div>
            </div>
            <div class="ai-disclaimer">
                <i class="zmdi zmdi-info-circle m-r-5"></i>
                This AI provides informational analysis only and is not a substitute for professional medical advice, diagnosis, or treatment.
            </div>
            <div class="ai-input-area">
                <input type="text" id="ai-input" placeholder="Type your health question..."
                       onkeydown="if(event.key==='Enter' && !event.shiftKey){event.preventDefault();sendAiMsg();}">
                <button id="ai-send" onclick="sendAiMsg()"><i class="zmdi zmdi-send"></i></button>
            </div>
        </div>
    </div>
</div>
</section>

<script src="../assets/bundles/libscripts.bundle.js"></script>
<script>
var chatHistory = [];
var sending = false;

function sendQuick(el) {
    document.getElementById('ai-input').value = el.textContent.replace(/^\s+/, '');
    sendAiMsg();
}

function sendAiMsg() {
    if (sending) return;
    var input = document.getElementById('ai-input');
    var msg = input.value.trim();
    if (!msg) return;

    sending = true;
    input.value = '';
    document.getElementById('ai-send').disabled = true;

    addMessage('user', msg);
    chatHistory.push({ role: 'user', content: msg });

    var typingRow = addTyping();

    fetch('../api/ai_chat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: msg, history: chatHistory.slice(-10) }),
        credentials: 'same-origin'
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        typingRow.remove();
        if (data.success) {
            addMessage('bot', data.reply);
            chatHistory.push({ role: 'assistant', content: data.reply });
        } else {
            addMessage('bot', data.message || 'Sorry, I encountered an error. Please try again.');
        }
        sending = false;
        document.getElementById('ai-send').disabled = false;
        input.focus();
    })
    .catch(function() {
        typingRow.remove();
        addMessage('bot', 'Network error. Please check your connection and try again.');
        sending = false;
        document.getElementById('ai-send').disabled = false;
    });
}

function addMessage(type, text) {
    var container = document.getElementById('ai-messages');
    var now = new Date();
    var time = now.getHours().toString().padStart(2,'0') + ':' + now.getMinutes().toString().padStart(2,'0');

    var formatted = text
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/\n\n/g, '<br><br>')
        .replace(/\n/g, '<br>')
        .replace(/- (.*?)(<br>|$)/g, '&bull; $1$2');

    var icon = type === 'bot' ? '<i class="zmdi zmdi-robot"></i>' : '<i class="zmdi zmdi-account"></i>';

    var html = '<div class="ai-msg-row ' + type + '">' +
        '<div class="ai-msg-avatar">' + icon + '</div>' +
        '<div><div class="ai-msg-bubble">' + formatted + '</div><div class="ai-msg-time">' + time + '</div></div></div>';

    container.insertAdjacentHTML('beforeend', html);
    container.scrollTop = container.scrollHeight;
}

function addTyping() {
    var container = document.getElementById('ai-messages');
    var html = '<div class="ai-msg-row bot" id="typing-indicator">' +
        '<div class="ai-msg-avatar"><i class="zmdi zmdi-robot"></i></div>' +
        '<div class="ai-msg-bubble"><div class="ai-typing"><span></span><span></span><span></span></div></div></div>';
    container.insertAdjacentHTML('beforeend', html);
    container.scrollTop = container.scrollHeight;
    return document.getElementById('typing-indicator');
}
</script>
</body>
</html>
