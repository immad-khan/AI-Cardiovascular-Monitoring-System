<?php
session_start();
include("../config/DB_Config.php");

if (!isset($_SESSION["user_type"]) || $_SESSION["user_type"] !== "patient") {
    header("Location: index.php?status=Access is denied&type=error");
    exit();
}

// Get patient info
try {
    $stmt = $conn->prepare("SELECT p.\"patientID\", p.name, p.\"assignedDoctorID\" FROM patients p WHERE p.email = ? LIMIT 1");
    $stmt->execute([$_SESSION["email"]]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$patient) { die("Patient not found."); }
    $patientId = $patient["patientID"];
    $patientName = $patient["name"];
} catch (PDOException $e) { die("DB Error"); }

// Get patient profile photo
$patientPhoto = '../assets/images/profile_av.jpg';
try {
    $p_stmt = $conn->prepare("SELECT COALESCE(profile_picture, '../assets/images/profile_av.jpg') as photo FROM patients WHERE email = ?");
    $p_stmt->execute([$_SESSION["email"]]);
    $p_row = $p_stmt->fetch(PDO::FETCH_ASSOC);
    if ($p_row) { $patientPhoto = $p_row['photo']; }
} catch (PDOException $e) {}

// Get assigned doctor info
$doctorId = $patient["assignedDoctorID"];
$doctorName = "Not Assigned";
$doctorRole = "Your Assigned Doctor";
$doctorPhoto = "../assets/images/sm/avatar1.jpg";
if ($doctorId) {
    try {
        $doc_stmt = $conn->prepare("SELECT dp.full_name, dp.specialization, COALESCE(dp.profile_picture, '../assets/images/sm/avatar1.jpg') as photo FROM \"doctorProfile\" dp WHERE dp.\"userID\" = ?");
        $doc_stmt->execute([$doctorId]);
        $doc = $doc_stmt->fetch(PDO::FETCH_ASSOC);
        if ($doc) {
            $doctorName = "Dr. " . $doc['full_name'];
            $doctorRole = !empty($doc['specialization']) ? $doc['specialization'] : "Your Assigned Doctor";
            $doctorPhoto = $doc['photo'];
        }
    } catch (PDOException $e) {}
}

$pageTitle = 'Doctor Chat — DigiHealth';
function e($v) { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html class="no-js" lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo e($pageTitle); ?></title>
  <link rel="icon" href="favicon.ico" type="image/x-icon">
  <link rel="stylesheet" href="../assets/plugins/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/css/main.css">
  <link rel="stylesheet" href="../assets/css/color_skins.css">
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,500;0,600;1,500;1,600&display=swap" rel="stylesheet" />
  <style>
    :root {
      --ink: #1a2d32; --muted: #687a7e;
      --teal: #0b9d9a; --bright-teal: #24c4bb;
      --pale: #eff6f3; --line: #dce9e5;
      --bg: #f8fbfa;
    }
    body.theme-cyan .content { margin-top: 0 !important; height: 100vh; display: flex; flex-direction: column; padding: 0; overflow: hidden; }
    body.theme-cyan section.content::before { display: none !important; }
    body.theme-cyan .sidebar { top: 0 !important; }

    /* Scope original resets to .app-wrapper */
    .app-wrapper { display: flex; flex: 1; flex-direction: column; min-height: 0; font-family: 'DM Sans', Arial, sans-serif; color: var(--ink); background: var(--bg); -webkit-font-smoothing: antialiased; }
    .app-wrapper button, .app-wrapper input { font: inherit; border: 0; outline: 0; }
    .app-wrapper button { cursor: pointer; background: none; }
    .app-wrapper a { color: inherit; text-decoration: none; }

    .app { display: flex; flex-direction: column; flex: 1; min-height: 0; overflow: hidden; }

    /* Top bar */
    .topbar {
      flex-shrink: 0; display: flex; align-items: center; justify-content: space-between;
      height: 64px; padding: 0 28px;
      background: #fff; border-bottom: 1px solid var(--line); z-index: 10;
    }
    .brand { display: inline-flex; align-items: center; gap: 10px; }
    .brand-symbol { position: relative; display: inline-block; width: 26px; height: 26px; transform: rotate(45deg); }
    .brand-symbol span { position: absolute; display: block; border: 2px solid currentColor; border-radius: 50% 0 50% 50%; }
    .brand-symbol span:nth-child(1) { top: 0; left: 8px; width: 11px; height: 18px; }
    .brand-symbol span:nth-child(2) { top: 8px; left: 0; width: 18px; height: 11px; }
    .brand-symbol span:nth-child(3) { top: 5px; left: 5px; width: 15px; height: 15px; border-color: var(--bright-teal); border-radius: 50%; }
    .brand-word { font-size: 18px; font-weight: 600; letter-spacing: -.04em; }

    .topbar-center { display: flex; align-items: center; gap: 10px; color: var(--muted); font-size: 12px; font-weight: 500; }
    .live-dot {
      width: 6px; height: 6px; border-radius: 50%; background: var(--bright-teal);
      box-shadow: 0 0 0 3px rgba(36,196,187,.15);
      animation: pulse-dot 2s ease-in-out infinite;
    }
    @keyframes pulse-dot {
      0%,100% { box-shadow: 0 0 0 3px rgba(36,196,187,.15); }
      50% { box-shadow: 0 0 0 6px rgba(36,196,187,.06); }
    }
    .topbar-right { display: flex; align-items: center; gap: 16px; }
    .top-link { display: inline-flex; align-items: center; gap: 6px; color: var(--muted); font-size: 12px; font-weight: 600; transition: color .2s; }
    .top-link:hover { color: var(--teal); }
    .top-link svg { width: 16px; height: 16px; }

    /* Doctor band */
    .doctor-band {
      flex-shrink: 0; display: flex; align-items: center; gap: 14px;
      padding: 14px 40px;
      background: linear-gradient(90deg, var(--teal), var(--bright-teal)); color: #fff;
    }
    .doctor-avatar {
      position: relative; display: flex; align-items: center; justify-content: center;
      width: 46px; height: 46px; border-radius: 50%;
      background: rgba(255,255,255,.18); border: 2px solid rgba(255,255,255,.5); overflow: hidden;
    }
    .doctor-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .doctor-avatar svg { width: 22px; height: 22px; }
    .online-dot {
      position: absolute; right: 0; bottom: 1px; width: 11px; height: 11px;
      border-radius: 50%; background: #3ee08f; border: 2px solid var(--teal);
    }
    .doctor-meta { flex: 1; min-width: 0; }
    .doctor-meta strong { display: block; font-size: 16px; font-weight: 700; letter-spacing: -.02em; }
    .doctor-meta span { display: block; margin-top: 2px; font-size: 11.5px; opacity: .85; }
    .doctor-meta em { font-style: normal; color: #b9ffe0; font-weight: 600; }
    .secure-chip {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 6px 12px; border: 1px solid rgba(255,255,255,.35); border-radius: 999px;
      font-size: 10.5px; font-weight: 600; letter-spacing: .03em;
    }
    .secure-chip svg { width: 13px; height: 13px; }

    /* Messages */
    .messages {
      flex: 1; overflow-y: auto;
      padding: 32px 40px 24px;
      display: flex; flex-direction: column; gap: 20px;
      background: var(--bg);
    }
    .day-divider {
      display: flex; align-items: center; gap: 14px; max-width: 720px;
      color: #9aafab; font-size: 10px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase;
      margin: 10px 0;
    }
    .day-divider::before { content: ''; flex: 1; height: 1px; background: var(--line); }

    .msg { display: flex; gap: 14px; max-width: 720px; animation: msg-in .35s cubic-bezier(.2,.8,.2,1) both; }
    @keyframes msg-in { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
    .msg.patient { align-self: flex-end; flex-direction: row-reverse; }

    .msg-avatar {
      flex-shrink: 0; display: flex; align-items: center; justify-content: center;
      width: 36px; height: 36px; border-radius: 50%; font-size: 13px; font-weight: 700; overflow: hidden;
    }
    .msg-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .msg.doctor .msg-avatar { background: var(--pale); border: 1.5px solid #c8ded8; color: var(--teal); }
    .msg.patient .msg-avatar { background: var(--teal); color: #fff; }
    .msg-avatar svg { width: 16px; height: 16px; }

    .msg-body { min-width: 0; }
    .sender { margin-bottom: 5px; font-size: 10.5px; font-weight: 700; color: var(--teal); letter-spacing: .02em; }
    .msg.patient .sender { text-align: right; color: #7c8f8e; }

    .bubble { padding: 16px 20px; border-radius: 12px; font-size: 14px; line-height: 1.65; word-wrap: break-word; }
    .msg.doctor .bubble {
      background: #fff; border: 1px solid var(--line);
      border-top-left-radius: 4px; box-shadow: 0 2px 8px rgba(11,157,154,.06);
    }
    .msg.patient .bubble { background: var(--teal); color: #fff; border-top-right-radius: 4px; }

    .msg-time { margin-top: 6px; font-size: 10px; color: #9aafab; font-weight: 500; }
    .msg.patient .msg-time { text-align: right; }
    .check { color: var(--bright-teal); font-weight: 700; }

    /* Typing */
    .typing { display: none; align-items: flex-start; gap: 14px; max-width: 720px; }
    .typing.visible { display: flex; }
    .typing-dots {
      display: flex; gap: 5px; padding: 16px 20px;
      background: #fff; border: 1px solid var(--line);
      border-radius: 12px; border-top-left-radius: 4px;
    }
    .typing-dots span { width: 7px; height: 7px; border-radius: 50%; background: var(--bright-teal); animation: bounce 1.2s ease-in-out infinite; }
    .typing-dots span:nth-child(2) { animation-delay: .15s; }
    .typing-dots span:nth-child(3) { animation-delay: .3s; }
    @keyframes bounce { 0%,60%,100% { transform: translateY(0); opacity: .4; } 30% { transform: translateY(-5px); opacity: 1; } }

    /* Footer */
    .chat-footer { flex-shrink: 0; padding: 0 40px 24px; background: var(--bg); }
    .disclaimer {
      text-align: center; padding: 10px 16px; margin-bottom: 14px;
      border-top: 1px solid var(--line); color: #9aafab; font-size: 11px; line-height: 1.5;
    }
    .input-row { display: flex; align-items: center; gap: 12px; max-width: 720px; }
    .input-wrap {
      flex: 1; display: flex; align-items: center;
      background: #fff; border: 1.5px solid var(--line); border-radius: 999px;
      padding: 4px 4px 4px 22px; transition: border-color .2s, box-shadow .2s;
    }
    .input-wrap:focus-within { border-color: var(--teal); box-shadow: 0 0 0 3px rgba(11,157,154,.1); }
    .input-wrap input { flex: 1; min-height: 44px; background: transparent; color: var(--ink); font-size: 13.5px; }
    .input-wrap input::placeholder { color: #9eafad; }
    .send-btn {
      flex-shrink: 0; display: flex; align-items: center; justify-content: center;
      width: 44px; height: 44px; border-radius: 50%;
      background: var(--teal); color: #fff;
      transition: background .2s, transform .2s, box-shadow .2s;
    }
    .send-btn:hover { background: #087d7d; transform: translateY(-1px); box-shadow: 0 6px 16px rgba(11,157,154,.25); }
    .send-btn:disabled { background: #c4d8d3; cursor: not-allowed; transform: none; box-shadow: none; }
    .send-btn svg { width: 18px; height: 18px; }

    /* Scrollbar */
    .messages::-webkit-scrollbar { width: 5px; }
    .messages::-webkit-scrollbar-track { background: transparent; }
    .messages::-webkit-scrollbar-thumb { background: #c4d8d3; border-radius: 3px; }

    .no-doctor-msg { text-align: center; color: var(--muted); padding: 40px; font-size: 14px; }
    .no-doctor-msg svg { display: block; margin: 0 auto 16px; width: 48px; height: 48px; opacity: 0.3; }

    @media (max-width: 680px) {
      .topbar { padding: 0 16px; }
      .topbar-center { display: none; }
      .doctor-band { padding: 12px 16px; }
      .secure-chip { display: none; }
      .messages { padding: 20px 16px; }
      .chat-footer { padding: 0 16px 16px; }
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

<div class="app">

  <!-- Top bar -->
  <header class="topbar">
    <a href="Patient-Dashboard.php" class="brand" aria-label="digihealth home">
      <span class="brand-symbol" aria-hidden="true"><span></span><span></span><span></span></span>
      <span class="brand-word">digihealth</span>
    </a>
    <div class="topbar-center"><span class="live-dot"></span> Doctor Chat</div>
    <div class="topbar-right">
      <a class="top-link" href="Patient-Profile.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.1"/><path d="M5 20c.7-3.5 3-5.3 7-5.3s6.3 1.8 7 5.3"/></svg>
        My Profile
      </a>
      <a class="top-link" href="Patient-Dashboard.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>
        Dashboard
      </a>
    </div>
  </header>

  <?php if (!$doctorId): ?>
  <div class="messages" style="justify-content: center;">
      <div class="no-doctor-msg">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.1"/><path d="M5 20c.7-3.5 3-5.3 7-5.3s6.3 1.8 7 5.3"/></svg>
          <h3>No Doctor Assigned</h3>
          <p style="margin-top: 8px;">Please contact the administration to get a doctor assigned to your profile.</p>
      </div>
  </div>
  <?php else: ?>
  
  <!-- Doctor band -->
  <div class="doctor-band">
    <div class="doctor-avatar">
      <?php if ($doctorPhoto && $doctorPhoto !== '../assets/images/sm/avatar1.jpg'): ?>
        <img src="<?php echo e($doctorPhoto); ?>" alt="<?php echo e($doctorName); ?>" />
      <?php else: ?>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.1"/><path d="M5 20c.7-3.5 3-5.3 7-5.3s6.3 1.8 7 5.3"/></svg>
      <?php endif; ?>
      <span class="online-dot"></span>
    </div>
    <div class="doctor-meta">
      <strong><?php echo e($doctorName); ?></strong>
      <span><?php echo e($doctorRole); ?> · <em>Online</em></span>
    </div>
    <span class="secure-chip">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
      Private &amp; encrypted
    </span>
  </div>

  <!-- Messages -->
  <div class="messages" id="messages">
    <!-- Messages injected via JS -->
    <div class="text-center" style="font-size:12px;color:var(--muted);text-align:center;padding:20px;">Loading messages...</div>
    
    <!-- Typing indicator -->
    <div class="typing" id="typing">
      <div class="msg-avatar" style="background:var(--pale);border:1.5px solid #c8ded8;color:var(--teal);">
        <?php if ($doctorPhoto && $doctorPhoto !== '../assets/images/sm/avatar1.jpg'): ?>
          <img src="<?php echo e($doctorPhoto); ?>" alt="<?php echo e($doctorName); ?>" />
        <?php else: ?>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.1"/><path d="M5 20c.7-3.5 3-5.3 7-5.3s6.3 1.8 7 5.3"/></svg>
        <?php endif; ?>
      </div>
      <div class="typing-dots"><span></span><span></span><span></span></div>
    </div>
  </div>

  <!-- Footer -->
  <div class="chat-footer">
    <p class="disclaimer">Messages are shared securely with your assigned doctor. For emergencies, call your local emergency number.</p>
    <form class="input-row" id="chatForm" autocomplete="off">
      <div class="input-wrap">
        <input type="text" id="chatInput" name="message" placeholder="Type your message…" maxlength="1000" required />
      </div>
      <button class="send-btn" type="submit" id="sendBtn" aria-label="Send message">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h13"/><path d="m13 6 6 6-6 6"/></svg>
      </button>
    </form>
  </div>
  <?php endif; ?>

</div>

<script>
(function () {
  'use strict';

  var patientId   = <?php echo json_encode($patientId); ?>;
  var doctorId    = <?php echo json_encode($doctorId); ?>;
  var doctorName  = <?php echo json_encode($doctorName); ?>;
  var doctorPhoto = <?php echo json_encode($doctorPhoto); ?>;
  var patientName = <?php echo json_encode($patientName); ?>;
  var patientPhoto= <?php echo json_encode($patientPhoto); ?>;
  
  if (!doctorId) return; // No doctor assigned

  var messagesEl  = document.getElementById('messages');
  var chatForm    = document.getElementById('chatForm');
  var chatInput   = document.getElementById('chatInput');
  var sendBtn     = document.getElementById('sendBtn');
  var typingEl    = document.getElementById('typing');
  
  var lastMsgId = 0;
  var sending = false;

  function timeNow(dateStr) {
    var d = dateStr ? new Date(dateStr) : new Date();
    var h = d.getHours(), m = d.getMinutes();
    var ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    return h + ':' + (m < 10 ? '0' : '') + m + ' ' + ampm;
  }

  function formatDate(dateStr) {
      return new Date(dateStr).toLocaleDateString();
  }

  function scrollBottom() { 
      messagesEl.scrollTop = messagesEl.scrollHeight; 
  }

  function escapeHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
  }

  function showTyping(on) {
    typingEl.classList.toggle('visible', on);
    if (on) scrollBottom();
  }

  function getAvatarHtml(role) {
      var photo = role === 'patient' ? patientPhoto : doctorPhoto;
      var defaultIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.1"/><path d="M5 20c.7-3.5 3-5.3 7-5.3s6.3 1.8 7 5.3"/></svg>';
      
      if (photo && !photo.includes('avatar1.jpg') && !photo.includes('profile_av.jpg')) {
          return '<img src="' + photo + '" alt="Avatar" />';
      }
      
      if (role === 'patient') {
          return patientName.charAt(0).toUpperCase();
      }
      
      return defaultIcon;
  }

  function renderMessages(messages) {
      var html = '';
      var lastDate = '';
      
      messages.forEach(function(msg) {
          var msgDate = formatDate(msg.created_at);
          if (msgDate !== lastDate) {
              html += '<div class="day-divider"><span>' + msgDate + '</span></div>';
              lastDate = msgDate;
          }
          
          var isPatient = msg.sender_type === 'patient';
          var role = isPatient ? 'patient' : 'doctor';
          
          html += '<div class="msg ' + role + '">';
          html += '<div class="msg-avatar">' + getAvatarHtml(role) + '</div>';
          html += '<div class="msg-body">';
          html += '<div class="sender">' + (isPatient ? 'You' : doctorName) + '</div>';
          html += '<div class="bubble">' + escapeHtml(msg.message) + '</div>';
          html += '<div class="msg-time">' + timeNow(msg.created_at) + (isPatient ? (msg.is_read ? ' <span class="check">✓✓</span>' : ' <span class="check">✓</span>') : '') + '</div>';
          html += '</div></div>';
          
          lastMsgId = Math.max(lastMsgId, parseInt(msg.id));
      });
      
      return html;
  }

  function loadMessages() {
      fetch('../api/chat.php?action=fetch&patient_id=' + encodeURIComponent(patientId) + '&other_id=' + doctorId, { credentials: 'same-origin' })
      .then(function(r) { return r.json(); })
      .then(function(data) {
          if (data.success && data.messages.length > 0) {
              // We replace everything except the typing indicator if we're rendering for the first time
              // For a simple implementation, let's just re-render all to keep date dividers correct
              var typingHtml = typingEl.outerHTML;
              messagesEl.innerHTML = renderMessages(data.messages) + typingHtml;
              
              // Re-bind typingEl since we replaced innerHTML
              typingEl = document.getElementById('typing');
              
              scrollBottom();
              markAsRead();
          } else if (lastMsgId === 0) {
              messagesEl.innerHTML = '<div class="text-center" style="font-size:12px;color:var(--muted);text-align:center;padding:20px;">No messages yet. Send a message to start the conversation!</div>' + typingEl.outerHTML;
              typingEl = document.getElementById('typing');
          }
      });
  }

  function markAsRead() {
      fetch('../api/chat.php?action=mark_read', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ patient_id: patientId, sender_id: doctorId }),
          credentials: 'same-origin'
      });
  }

  chatForm.addEventListener('submit', function (e) {
    e.preventDefault();
    var text = chatInput.value.trim();
    if (!text || busy || sending) return;
    sending = true;
    sendBtn.disabled = true;

    // Optimistic append
    var el = document.createElement('div');
    el.className = 'msg patient';
    el.innerHTML =
      '<div class="msg-avatar">' + getAvatarHtml('patient') + '</div>' +
      '<div class="msg-body">' +
        '<div class="sender">You</div>' +
        '<div class="bubble">' + escapeHtml(text) + '</div>' +
        '<div class="msg-time">' + timeNow() + ' <span class="check">✓</span></div>' +
      '</div>';
    
    messagesEl.insertBefore(el, typingEl);
    chatInput.value = '';
    scrollBottom();

    // Send to API
    fetch('../api/chat.php?action=send', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: text, patient_id: patientId, receiver_id: doctorId }),
        credentials: 'same-origin'
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        sending = false;
        sendBtn.disabled = false;
        if (!data.success) { alert('Failed to send: ' + data.message); }
        loadMessages(); // reload to get proper IDs and state
        chatInput.focus();
    })
    .catch(function() {
        sending = false;
        sendBtn.disabled = false;
        alert('Network error');
    });
  });

  chatInput.focus();
  loadMessages();
  setInterval(loadMessages, 5000);
})();
</script>

</div>
</div>
</section>

<script src="../assets/bundles/libscripts.bundle.js"></script>
<script src="../assets/bundles/vendorscripts.bundle.js"></script>
<script src="../assets/bundles/mainscripts.bundle.js"></script>
</body>
</html>
