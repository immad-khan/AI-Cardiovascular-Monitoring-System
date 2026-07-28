<?php
session_start();
include("../config/DB_Config.php");

if (!isset($_SESSION["user_type"]) || $_SESSION["user_type"] !== "patient") {
    header("Location: index.php?status=Access is denied&type=error");
    exit();
}

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
<title>AI Assistant - Digihealth</title>
<link rel="icon" href="favicon.ico" type="image/x-icon">
<link rel="stylesheet" href="../assets/plugins/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/color_skins.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,500;1,500&display=swap" rel="stylesheet">
<style>
:root {
  --ink: #1a2d32;
  --muted: #687a7e;
  --teal: #0b9d9a;
  --bright-teal: #24c4bb;
  --pale: #eff6f3;
  --line: #dce9e5;
  --navy: #1b3539;
  --bg: #f8fbfa;
}

body.theme-cyan .content { margin-top: 0 !important; height: 100vh; display: flex; flex-direction: column; padding: 0; overflow: hidden; }
body.theme-cyan section.content::before { display: none !important; }
body.theme-cyan .sidebar { top: 0 !important; }

/* Remove global styling that conflicts with template */
.app-wrapper {
  display: flex; flex: 1; min-height: 0;
  font-family: 'DM Sans', Arial, sans-serif;
  color: var(--ink);
  background: var(--bg);
}

.app-wrapper button, .app-wrapper input, .app-wrapper textarea { font: inherit; }
.app-wrapper button { border: 0; cursor: pointer; background: none; }
.app-wrapper a { color: inherit; text-decoration: none; }
.app-wrapper button:focus-visible, .app-wrapper a:focus-visible, .app-wrapper input:focus-visible {
  outline: 3px solid rgba(36,196,187,.45);
  outline-offset: 3px;
}
.app-wrapper em {
  color: var(--teal);
  font-family: 'Playfair Display', Georgia, serif;
  font-weight: 500;
  font-style: italic;
}

/* LAYOUT */
.app-body { flex: 1; display: flex; flex-direction: column; min-width: 0; }

/* TOPBAR */
.topbar {
  display: flex; align-items: center; justify-content: space-between; gap: 20px;
  padding: 0 28px; height: 64px; flex-shrink: 0;
  background: #fff; border-bottom: 1px solid var(--line);
}
.topbar-title { display: flex; align-items: baseline; gap: 14px; }
.topbar-title h1 { margin: 0; font-size: 17px; font-weight: 600; letter-spacing: -.02em; }
.powered-note { color: var(--muted); font-size: 11px; letter-spacing: .02em; }
.powered-note strong { color: var(--teal); font-weight: 600; }
.topbar-actions { display: flex; align-items: center; gap: 14px; }
.back-link {
  display: inline-flex; align-items: center; gap: 8px;
  min-height: 36px; padding: 0 14px;
  border: 1px solid rgba(26,45,50,.25); border-radius: 4px;
  color: var(--ink); font-size: 12px; font-weight: 600;
  transition: color .2s, border-color .2s;
}
.back-link:hover { color: var(--teal); border-color: var(--teal); text-decoration: none; }

/* MAIN GRID */
.main { display: grid; grid-template-columns: 300px 1fr; flex: 1; min-height: 0; }

/* QUICK-QUESTIONS SIDEBAR */
.q-sidebar {
  display: flex; flex-direction: column; min-height: 0; overflow-y: auto;
  background: var(--pale); border-right: 1px solid var(--line);
  padding: 24px 20px 18px;
}
.q-sidebar-eyebrow {
  margin: 0 0 12px; color: var(--teal);
  font-size: 10px; font-weight: 700; letter-spacing: .18em; text-transform: uppercase;
}
.q-list { display: grid; gap: 8px; }
.q-item {
  display: flex; align-items: center; gap: 10px; width: 100%;
  padding: 11px 13px; text-align: left;
  background: #fff; border: 1px solid #d5e5df; border-radius: 5px;
  color: var(--ink); font-size: 13px; line-height: 1.35;
  transition: border-color .2s, transform .2s, box-shadow .2s;
}
.q-item svg { flex-shrink: 0; color: var(--teal); }
.q-item:hover { border-color: var(--teal); transform: translateY(-1px); box-shadow: 0 6px 16px rgba(11,157,154,.08); }
.q-item.active { border-color: var(--teal); background: #e4f4f0; }
.q-about {
  margin-top: 24px; padding-top: 20px; border-top: 1px solid #cfe0da;
}
.q-about h2 {
  display: flex; align-items: center; gap: 7px;
  margin: 0 0 10px; color: var(--muted);
  font-size: 11px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase;
}
.q-about p { margin: 0; color: var(--muted); font-size: 12.5px; line-height: 1.7; }
.q-foot { margin-top: auto; padding-top: 20px; color: #8fa5a1; font-size: 10.5px; }
.q-foot strong { color: var(--teal); font-weight: 600; }

/* CHAT PANEL */
.chat-panel { display: flex; flex-direction: column; min-height: 0; min-width: 0; background: var(--bg); }
.chat-scroll { flex: 1; min-height: 0; overflow-y: auto; padding: 30px 36px 18px; }
.chat-day {
  display: flex; align-items: center; gap: 14px;
  margin: 0 0 22px; color: #93a7a4;
  font-size: 10px; letter-spacing: .14em; text-transform: uppercase;
}
.chat-day::before, .chat-day::after { content: ''; flex: 1; height: 1px; background: var(--line); }

.msg-row { display: flex; gap: 12px; margin-bottom: 6px; max-width: 700px; }
.msg-row.user { margin-left: auto; flex-direction: row-reverse; max-width: 600px; }
.msg-avatar {
  display: flex; align-items: center; justify-content: center;
  width: 34px; height: 34px; flex-shrink: 0; border-radius: 50%;
  background: var(--pale); border: 1px solid #c8ded8; color: var(--teal);
}
.msg-row.user .msg-avatar { background: var(--teal); border-color: var(--teal); color: #fff; }
.msg-bubble {
  padding: 14px 17px; background: #fff; border: 1px solid var(--line);
  border-radius: 3px 12px 12px 12px; font-size: 14px; line-height: 1.65; color: #33494d;
}
.msg-row.user .msg-bubble { background: var(--teal); border-color: var(--teal); border-radius: 12px 3px 12px 12px; color: #fff; }
.msg-bubble strong { color: var(--ink); font-weight: 700; }
.msg-row.user .msg-bubble strong { color: #fff; }
.msg-time { margin: 5px 0 18px 46px; color: #9db0ad; font-size: 10.5px; }
.msg-row.user + .msg-time { text-align: right; margin-right: 46px; margin-left: 0; }

.typing { display: inline-flex; align-items: center; gap: 5px; padding: 4px 2px; }
.typing i { width: 6px; height: 6px; border-radius: 50%; background: var(--teal); opacity: .4; animation: blink 1.2s infinite; font-style: normal; }
.typing i:nth-child(2) { animation-delay: .2s; }
.typing i:nth-child(3) { animation-delay: .4s; }
@keyframes blink { 0%,100% { opacity: .25; } 50% { opacity: 1; } }

/* DISCLAIMER + COMPOSER */
.disclaimer {
  flex-shrink: 0; padding: 10px 36px; border-top: 1px solid var(--line);
  background: #fdfefd; color: #8a9c9a; font-size: 11px; text-align: center; margin: 0;
}
.composer {
  flex-shrink: 0; display: flex; align-items: center; gap: 12px;
  padding: 14px 36px 20px; background: #fdfefd;
}
.composer-input {
  flex: 1; min-height: 50px; padding: 0 20px;
  border: 1px solid #cbdcd7; border-radius: 999px; background: #fff;
  color: var(--ink); font-size: 14px; outline: 0;
  transition: border-color .2s, box-shadow .2s;
}
.composer-input:focus { border-color: var(--teal); box-shadow: 0 0 0 3px rgba(11,157,154,.1); }
.composer-input::placeholder { color: #9eafad; }
.send-btn {
  display: flex; align-items: center; justify-content: center;
  width: 50px; height: 50px; flex-shrink: 0; border-radius: 50%;
  background: var(--teal); color: #fff;
  transition: background .25s, transform .25s, box-shadow .25s;
}
.send-btn:hover { background: #087d7d; transform: translateY(-2px); box-shadow: 0 10px 25px rgba(11,157,154,.22); }
.send-btn:disabled { opacity: .55; cursor: not-allowed; transform: none; box-shadow: none; }

/* RESPONSIVE */
@media (max-width: 900px) {
  .main { grid-template-columns: 1fr; }
  .q-sidebar { display: none; }
  .q-sidebar.open { display: flex; position: fixed; inset: 64px 0 0 0; z-index: 30; }
  .menu-btn { display: inline-flex !important; }
}
.menu-btn {
  display: none; align-items: center; justify-content: center;
  width: 38px; height: 38px; border: 1px solid var(--line); border-radius: 4px; color: var(--ink);
}

@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after { animation-duration: .01ms !important; transition-duration: .01ms !important; }
}
</style>
<!-- Mobile CSS Fix --><link rel="stylesheet" href="../assets/css/mobile.css">
</head>
<body class="theme-cyan">

<aside id="leftsidebar" class="sidebar">
    <?php include("patient_sidebar.php") ?>
</aside>

<section class="content">
<div class="app-wrapper">

  <div class="app-body">
    <!-- Top bar -->
    <header class="topbar">
      <div class="topbar-title">
        <h1>AI <em>Assistant</em></h1>
        <span class="powered-note">Powered by <strong>LLaMA 3.3 70B</strong> &mdash; analyzes your ECG, vitals &amp; HRV data</span>
      </div>
      <div class="topbar-actions">
        <button class="menu-btn" id="menuBtn" aria-label="Toggle quick questions">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>
        <a class="back-link" href="Patient-Dashboard.php">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H6"/><path d="m11 6-6 6 6 6"/></svg>
          Dashboard
        </a>
      </div>
    </header>

    <div class="main">

      <!-- Quick questions sidebar -->
      <aside class="q-sidebar" id="qSidebar">
        <p class="q-sidebar-eyebrow">Quick questions</p>
        <div class="q-list">
          <button class="q-item" data-question="How are my heart rate and vitals?">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M19.5 12.572l-7.5 7.428l-7.5 -7.428a5 5 0 1 1 7.5 -6.566a5 5 0 1 1 7.5 6.572"/></svg>
            How are my heart rate and vitals?
          </button>
          <button class="q-item" data-question="Explain my HRV metrics">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h3l2-4 3.3 8 2.3-5 1.4 2H21"/></svg>
            Explain my HRV metrics
          </button>
          <button class="q-item" data-question="What do my ECG results mean?">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Z"/><path d="M14 3v5h5M9 13h6M9 17h4"/></svg>
            What do my ECG results mean?
          </button>
          <button class="q-item" data-question="What is my cardiac risk level?">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 19 6v5c0 4.8-3 8.1-7 10-4-1.9-7-5.2-7-10V6l7-3Z"/></svg>
            What is my cardiac risk level?
          </button>
          <button class="q-item" data-question="Explain my arrhythmia flags">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>
            Explain my arrhythmia flags
          </button>
          <button class="q-item" data-question="Give me health tips">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M6 21c0-6 3-13 13-15 0 0 1 12-8 14-3 .7-5 1-5 1Z"/><path d="M6 21c2-5 5-8 9-10"/></svg>
            Give me health tips
          </button>
          <button class="q-item" data-question="Should I see a doctor?">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.8a2 2 0 0 1-.4 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.9.6 2.8.7a2 2 0 0 1 1.7 2Z"/></svg>
            Should I see a doctor?
          </button>
        </div>

        <div class="q-about">
          <h2>
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
            About
          </h2>
          <p>I can see your real-time ECG data, vital signs (HR, respiration), HRV metrics, AI rhythm predictions, and alert history. Ask me questions about any of your health data.</p>
        </div>

        <p class="q-foot">Powered by <strong>LLaMA 3.3 70B</strong> via Groq</p>
      </aside>

      <!-- Chat panel -->
      <section class="chat-panel">
        <div class="chat-scroll" id="chatScroll">
          <p class="chat-day">Today</p>
          <div class="msg-row">
            <span class="msg-avatar">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M19.5 12.572l-7.5 7.428l-7.5 -7.428a5 5 0 1 1 7.5 -6.566a5 5 0 1 1 7.5 6.572"/></svg>
            </span>
            <div class="msg-bubble">
              Hello <strong><?php echo htmlspecialchars($patientName); ?></strong>! I'm your digihealth AI health assistant. I can analyze your ECG readings, vital signs, HRV metrics, and monitoring data.
              <br><br>
              How can I help you today?
            </div>
          </div>
          <p class="msg-time">Just now</p>
        </div>

        <p class="disclaimer">This AI provides informational analysis only and is not a substitute for professional medical advice, diagnosis, or treatment.</p>

        <form class="composer" id="composer" autocomplete="off">
          <input class="composer-input" id="chatInput" type="text" placeholder="Type your health question..." aria-label="Type your health question">
          <button class="send-btn" id="sendBtn" type="submit" aria-label="Send message">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h13"/><path d="m13 6 6 6-6 6"/></svg>
          </button>
        </form>
      </section>

    </div>
  </div>
</div>
</section>

<script src="../assets/bundles/libscripts.bundle.js"></script>
<script src="../assets/bundles/vendorscripts.bundle.js"></script>
<script src="../assets/bundles/mainscripts.bundle.js"></script>

<script>
(function () {
  var scrollEl  = document.getElementById('chatScroll');
  var composer  = document.getElementById('composer');
  var input     = document.getElementById('chatInput');
  var sendBtn   = document.getElementById('sendBtn');
  var qSidebar  = document.getElementById('qSidebar');
  var menuBtn   = document.getElementById('menuBtn');

  var chatHistory = [];
  var sending = false;

  var heartIcon = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M19.5 12.572l-7.5 7.428l-7.5 -7.428a5 5 0 1 1 7.5 -6.566a5 5 0 1 1 7.5 6.572"/></svg>';
  var userIcon  = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.1"/><path d="M5 20c0-3.5 3.1-6 7-6s7 2.5 7 6"/></svg>';

  function timeNow() {
    var d = new Date();
    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  }

  function escapeHtml(s) {
    var div = document.createElement('div');
    div.textContent = s;
    return div.innerHTML;
  }

  function formatText(text) {
    return escapeHtml(text)
      .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
      .replace(/\n\n/g, '<br><br>')
      .replace(/\n/g, '<br>')
      .replace(/- (.*?)(<br>|$)/g, '&bull; $1$2');
  }

  function addMessage(text, who) {
    var row = document.createElement('div');
    row.className = 'msg-row' + (who === 'user' ? ' user' : '');
    row.innerHTML =
      '<span class="msg-avatar">' + (who === 'user' ? userIcon : heartIcon) + '</span>' +
      '<div class="msg-bubble">' + formatText(text) + '</div>';
    scrollEl.appendChild(row);

    var t = document.createElement('p');
    t.className = 'msg-time';
    t.textContent = timeNow();
    scrollEl.appendChild(t);

    scrollEl.scrollTop = scrollEl.scrollHeight;
    return row;
  }

  function addTyping() {
    var row = document.createElement('div');
    row.className = 'msg-row';
    row.id = 'typingRow';
    row.innerHTML =
      '<span class="msg-avatar">' + heartIcon + '</span>' +
      '<div class="msg-bubble"><span class="typing"><i></i><i></i><i></i></span></div>';
    scrollEl.appendChild(row);
    scrollEl.scrollTop = scrollEl.scrollHeight;
    return row;
  }

  function removeTyping() {
    var row = document.getElementById('typingRow');
    if (row) row.remove();
  }

  function send(message) {
    if (!message.trim() || sending) return;
    sending = true;
    addMessage(message, 'user');
    chatHistory.push({ role: 'user', content: message });
    input.value = '';
    sendBtn.disabled = true;
    var typingRow = addTyping();

    fetch('../api/ai_chat.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ message: message, history: chatHistory.slice(-10) }),
      credentials: 'same-origin'
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      removeTyping();
      if (data.success) {
        addMessage(data.reply, 'ai');
        chatHistory.push({ role: 'assistant', content: data.reply });
      } else {
        addMessage(data.message || 'Sorry, something went wrong. Please try again.', 'ai');
      }
    })
    .catch(function () {
      removeTyping();
      addMessage('Sorry, I could not reach the server. Please try again.', 'ai');
    })
    .finally(function () {
      sending = false;
      sendBtn.disabled = false;
      input.focus();
    });
  }

  composer.addEventListener('submit', function (e) {
    e.preventDefault();
    send(input.value);
  });

  document.querySelectorAll('.q-item').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.q-item').forEach(function (b) { b.classList.remove('active'); });
      btn.classList.add('active');
      send(btn.getAttribute('data-question'));
      if (window.innerWidth <= 900) qSidebar.classList.remove('open');
    });
  });

  if (menuBtn) {
    menuBtn.addEventListener('click', function () {
      qSidebar.classList.toggle('open');
    });
  }
})();
</script>
</body>
</html>
