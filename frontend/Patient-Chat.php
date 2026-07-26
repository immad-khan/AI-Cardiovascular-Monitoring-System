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
$doctorPhoto = "../assets/images/sm/avatar1.jpg";
if ($doctorId) {
    try {
        $doc_stmt = $conn->prepare("SELECT dp.full_name, dp.specialization, COALESCE(dp.profile_picture, '../assets/images/sm/avatar1.jpg') as photo FROM \"doctorProfile\" dp WHERE dp.\"userID\" = ?");
        $doc_stmt->execute([$doctorId]);
        $doc = $doc_stmt->fetch(PDO::FETCH_ASSOC);
        if ($doc) {
            $doctorName = $doc['full_name'];
            $doctorPhoto = $doc['photo'];
        }
    } catch (PDOException $e) {}
}
?>
<!doctype html>
<html class="no-js" lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
<title>Chat with Doctor - CUST Digihealth</title>
<link rel="icon" href="favicon.ico" type="image/x-icon">
<link rel="stylesheet" href="../assets/plugins/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/color_skins.css">
<style>
    .content { padding: 0; margin: 0; height: 100vh; }
    .chat-page { display: flex; flex-direction: column; height: 100%; }
    .chat-header {
        background: linear-gradient(135deg, #1565c0, #0d47a1);
        color: #fff; padding: 14px 25px; display: flex; align-items: center; gap: 15px; flex-shrink: 0;
    }
    .chat-header img { width: 42px; height: 42px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.3); object-fit: cover; }
    .chat-header h4 { margin: 0; font-size: 1rem; }
    .chat-header small { opacity: 0.8; font-size: 12px; }
    .chat-body { flex: 1; overflow-y: auto; padding: 20px 25px; background: #f0f2f5; display: flex; flex-direction: column; gap: 6px; }
    .msg-row { display: flex; align-items: flex-end; gap: 8px; max-width: 75%; }
    .msg-row.sent { align-self: flex-end; flex-direction: row-reverse; }
    .msg-row.received { align-self: flex-start; }
    .msg-avatar { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
    .msg-bubble { padding: 10px 14px; border-radius: 16px; font-size: 14px; line-height: 1.5; word-wrap: break-word; }
    .msg-row.sent .msg-bubble { background: #1565c0; color: #fff; border-bottom-right-radius: 4px; }
    .msg-row.received .msg-bubble { background: #fff; color: #333; border-bottom-left-radius: 4px; border: 1px solid #e0e0e0; }
    .msg-sender { font-size: 11px; font-weight: 600; margin-bottom: 2px; }
    .msg-row.sent .msg-sender { color: rgba(255,255,255,0.8); text-align: right; }
    .msg-row.received .msg-sender { color: #1565c0; }
    .msg-time { font-size: 10px; margin-top: 3px; }
    .msg-row.sent .msg-time { text-align: right; color: rgba(255,255,255,0.7); }
    .msg-row.received .msg-time { color: #999; }
    .chat-date-divider { text-align: center; font-size: 11px; color: #999; margin: 10px 0; }
    .chat-date-divider span { background: #e0e0e0; padding: 3px 12px; border-radius: 10px; }
    .chat-input-area {
        padding: 14px 20px; border-top: 1px solid #e0e0e0; background: #fff;
        display: flex; gap: 10px; align-items: center; flex-shrink: 0;
    }
    .chat-input-area input {
        flex: 1; border: 2px solid #e0e0e0; border-radius: 25px;
        padding: 11px 20px; font-size: 14px; outline: none; transition: border-color 0.2s;
    }
    .chat-input-area input:focus { border-color: #1565c0; }
    .chat-input-area button {
        width: 44px; height: 44px; border-radius: 50%; border: none;
        background: #1565c0; color: #fff; cursor: pointer; font-size: 18px;
        display: flex; align-items: center; justify-content: center; transition: background 0.2s;
    }
    .chat-input-area button:hover { background: #0d47a1; }
    .chat-input-area button:disabled { background: #ccc; cursor: not-allowed; }
    .no-doctor { flex: 1; display: flex; align-items: center; justify-content: center; flex-direction: column; color: #999; }
    .no-doctor i { font-size: 4rem; margin-bottom: 15px; color: #ccc; }
</style>
</head>
<body class="theme-cyan">
<!-- Overlay For Sidebars -->
<div class="overlay"></div>
<!-- Top Bar -->
<nav class="navbar p-l-5 p-r-5">
    <div class="container-fluid">
        <div class="navbar-header">
            <a href="javascript:void(0);" class="navbar-brand"><img src="../assets/images/logo.svg" width="30" alt="CUST"> <span class="m-l-10">DigiHealth</span></a>
        </div>
        <?php include("top_nav.php"); ?>
    </div>
</nav>
<aside id="leftsidebar" class="sidebar">
    <?php include("patient_sidebar.php") ?>
</aside>

<section class="content">
<div class="chat-page">
    <?php if (!$doctorId): ?>
    <div class="no-doctor">
        <i class="zmdi zmdi-account-circle"></i>
        <h4>No Doctor Assigned</h4>
        <p>Please contact the administration to get a doctor assigned.</p>
    </div>
    <?php else: ?>
    <div class="chat-header">
        <img src="<?php echo htmlspecialchars($doctorPhoto); ?>" alt="Doctor">
        <div>
            <h4>Dr. <?php echo htmlspecialchars($doctorName); ?></h4>
            <small>Your Assigned Doctor</small>
        </div>
    </div>
    <div class="chat-body" id="chat-body">
        <div class="text-center p-t-30"><small class="text-muted">Loading messages...</small></div>
    </div>
    <div class="chat-input-area">
        <input type="text" id="chat-input" placeholder="Type your message..." onkeydown="if(event.key==='Enter' && !event.shiftKey){event.preventDefault();sendMessage();}">
        <button id="send-btn" onclick="sendMessage()"><i class="zmdi zmdi-send"></i></button>
    </div>
    <?php endif; ?>
</div>
</section>

<script src="../assets/bundles/libscripts.bundle.js"></script>
<script>
var patientId = <?php echo json_encode($patientId); ?>;
var doctorId = <?php echo json_encode($doctorId); ?>;
var doctorName = <?php echo json_encode($doctorName); ?>;
var doctorPhoto = <?php echo json_encode($doctorPhoto); ?>;
var patientName = <?php echo json_encode($patientName); ?>;
var patientPhoto = <?php echo json_encode($patientPhoto); ?>;
var lastMsgId = 0;
var sending = false;

function loadMessages() {
    fetch('../api/chat.php?action=fetch&patient_id=' + encodeURIComponent(patientId) + '&other_id=' + doctorId, { credentials: 'same-origin' })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success && data.messages.length > 0) {
            var container = document.getElementById('chat-body');
            container.innerHTML = '';
            var lastDate = '';
            data.messages.forEach(function(msg) {
                var msgDate = new Date(msg.created_at).toLocaleDateString();
                if (msgDate !== lastDate) {
                    container.insertAdjacentHTML('beforeend', '<div class="chat-date-divider"><span>' + msgDate + '</span></div>');
                    lastDate = msgDate;
                }
                var isSent = msg.sender_type === 'patient';
                var time = new Date(msg.created_at).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
                var avatar = isSent ? patientPhoto : doctorPhoto;
                var name = isSent ? 'You' : 'Dr. ' + doctorName;
                container.insertAdjacentHTML('beforeend',
                    '<div class="msg-row ' + (isSent ? 'sent' : 'received') + '">' +
                    '<img src="' + avatar + '" class="msg-avatar" alt="">' +
                    '<div class="msg-bubble">' +
                    '<div class="msg-sender">' + escapeHtml(name) + '</div>' +
                    '<div>' + escapeHtml(msg.message) + '</div>' +
                    '<div class="msg-time">' + time + (isSent ? (msg.is_read ? ' \u2713\u2713' : ' \u2713') : '') + '</div>' +
                    '</div></div>'
                );
                lastMsgId = Math.max(lastMsgId, parseInt(msg.id));
            });
            container.scrollTop = container.scrollHeight;
            markAsRead();
        } else {
            document.getElementById('chat-body').innerHTML = '<div class="text-center p-t-50"><i class="zmdi zmdi-chat" style="font-size:3rem;color:#ccc;"></i><p class="text-muted m-t-10">No messages yet. Start the conversation!</p></div>';
        }
    });
}

function sendMessage() {
    if (sending || !doctorId) return;
    var input = document.getElementById('chat-input');
    var msg = input.value.trim();
    if (!msg) return;

    sending = true;
    input.value = '';
    document.getElementById('send-btn').disabled = true;

    var container = document.getElementById('chat-body');
    var time = new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
    container.insertAdjacentHTML('beforeend',
        '<div class="msg-row sent">' +
        '<img src="' + patientPhoto + '" class="msg-avatar" alt="">' +
        '<div class="msg-bubble">' +
        '<div class="msg-sender">You</div>' +
        '<div>' + escapeHtml(msg) + '</div>' +
        '<div class="msg-time">' + time + ' \u2713</div>' +
        '</div></div>'
    );
    container.scrollTop = container.scrollHeight;

    fetch('../api/chat.php?action=send', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: msg, patient_id: patientId, receiver_id: doctorId }),
        credentials: 'same-origin'
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        sending = false;
        document.getElementById('send-btn').disabled = false;
        if (!data.success) { alert('Failed to send: ' + data.message); }
        setTimeout(loadMessages, 500);
    })
    .catch(function() {
        sending = false;
        document.getElementById('send-btn').disabled = false;
        alert('Network error');
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

function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}

loadMessages();
setInterval(loadMessages, 5000);
</script>
</body>
</html>
