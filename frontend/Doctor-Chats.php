<?php
session_start();
include("../config/DB_Config.php");

if (!isset($_SESSION["user_type"]) || $_SESSION["user_type"] !== "doctor") {
    header("Location: index.php?status=Access is denied&type=error");
    exit();
}

$doctorId = $_SESSION['user_id'];
?>
<!doctype html>
<html class="no-js" lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
<title>Patient Chats - CUST Digihealth</title>
<link rel="icon" href="favicon.ico" type="image/x-icon">
<link rel="stylesheet" href="../assets/plugins/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/color_skins.css">
<style>
    .chat-list-item {
        display: flex; align-items: center; gap: 14px; padding: 14px 20px;
        border-bottom: 1px solid #f0f0f0; cursor: pointer; transition: background 0.15s;
        text-decoration: none; color: inherit;
    }
    .chat-list-item:hover { background: #e3f2fd; }
    .chat-list-item .avatar {
        width: 48px; height: 48px; border-radius: 50%; object-fit: cover;
        border: 2px solid #e0e0e0; flex-shrink: 0;
    }
    .chat-list-item .info { flex: 1; min-width: 0; }
    .chat-list-item .info h5 { margin: 0; font-size: 14px; font-weight: 600; }
    .chat-list-item .info .preview { font-size: 12.5px; color: #888; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px; }
    .chat-list-item .meta { text-align: right; flex-shrink: 0; }
    .chat-list-item .meta .time { font-size: 11px; color: #aaa; }
    .unread-badge {
        display: inline-flex; align-items: center; justify-content: center;
        background: #1565c0; color: #fff; border-radius: 50%; min-width: 22px; height: 22px;
        font-size: 11px; font-weight: 700; margin-top: 5px; padding: 0 6px;
    }
</style>
</head>
<body class="theme-cyan">
<nav class="navbar p-l-5 p-r-5">
    <?php include("top_nav.php") ?>
</nav>
<aside id="leftsidebar" class="sidebar">
    <?php include("doctor_sidebar.php") ?>
</aside>

<section class="content">
    <div class="block-header">
        <div class="row">
            <div class="col-lg-7 col-md-5 col-sm-12">
                <h2>Patient Chats <small class="text-muted">Messages from your assigned patients</small></h2>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row clearfix">
            <div class="col-lg-6 col-md-8 col-sm-12">
                <div class="card" style="padding:0;">
                    <div class="header" style="padding:15px 20px;border-bottom:1px solid #f0f0f0;">
                        <h2 style="margin:0;font-size:16px;"><strong>Conversations</strong></h2>
                    </div>
                    <div id="chat-list" style="max-height:calc(100vh - 250px);overflow-y:auto;">
                        <div class="text-center p-t-30 p-b-30 text-muted">Loading...</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-4 col-sm-12 d-none d-md-block">
                <div class="card">
                    <div class="body text-center" style="padding:60px 20px;">
                        <i class="zmdi zmdi-comments" style="font-size:4rem;color:#ccc;"></i>
                        <h4 class="m-t-20 text-muted">Select a conversation</h4>
                        <p class="text-muted" style="font-size:13px;">Click on a patient from the list to start chatting</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="../assets/bundles/libscripts.bundle.js"></script>
<script>
function loadConversations() {
    fetch('../api/chat.php?action=list_conversations', { credentials: 'same-origin' })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        var list = document.getElementById('chat-list');
        if (!data.success || data.conversations.length === 0) {
            list.innerHTML = '<div class="text-center p-t-30 p-b-30"><i class="zmdi zmdi-account-o" style="font-size:2.5rem;color:#ccc;"></i><p class="text-muted m-t-10">No patients assigned yet.</p></div>';
            return;
        }
        list.innerHTML = '';
        data.conversations.forEach(function(c) {
            var lastMsg = c.last_message ? escapeHtml(c.last_message) : 'No messages yet';
            var time = c.last_time ? formatTime(c.last_time) : '';
            var unread = parseInt(c.unread_count) || 0;
            var genderIcon = c.gender === 'Female' ? 'fa-venus' : 'fa-mars';
            var photo = c.profile_picture || '../assets/images/profile_av.jpg';

            list.insertAdjacentHTML('beforeend',
                '<a href="Doctor-Chat.php?patientId=' + encodeURIComponent(c.patientID) + '" class="chat-list-item">' +
                '<img src="' + photo + '" class="avatar" alt="">' +
                '<div class="info">' +
                '<h5>' + escapeHtml(c.name) + ' <small style="color:#aaa;font-weight:normal;font-size:11px;">(' + escapeHtml(c.patientID) + ')</small></h5>' +
                '<div class="preview">' + lastMsg + '</div>' +
                '</div>' +
                '<div class="meta">' +
                '<div class="time">' + time + '</div>' +
                (unread > 0 ? '<div class="unread-badge">' + unread + '</div>' : '') +
                '</div>' +
                '</a>'
            );
        });
    });
}

function formatTime(ts) {
    var d = new Date(ts);
    var now = new Date();
    var diffDays = Math.floor((now - d) / 86400000);
    if (diffDays === 0) return d.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
    if (diffDays === 1) return 'Yesterday';
    if (diffDays < 7) return d.toLocaleDateString([], {weekday:'short'});
    return d.toLocaleDateString([], {month:'short', day:'numeric'});
}

function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}

loadConversations();
setInterval(loadConversations, 8000);
</script>
</body>
</html>
