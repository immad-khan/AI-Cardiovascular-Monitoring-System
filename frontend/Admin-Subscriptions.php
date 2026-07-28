<?php
session_start();
include("../config/DB_Config.php");

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$subscriptions = [];
$total = 0; $pending = 0; $approved = 0; $rejected = 0;

$filter = $_GET['filter'] ?? 'all';
$allowed = ['all', 'pending', 'approved', 'rejected'];
if (!in_array($filter, $allowed)) $filter = 'all';

try {
    $counts = $conn->query('SELECT
        COUNT(*) as total,
        COUNT(*) FILTER (WHERE status = \'pending\') as pending,
        COUNT(*) FILTER (WHERE status = \'approved\') as approved,
        COUNT(*) FILTER (WHERE status = \'rejected\') as rejected
        FROM subscriptions')->fetch(PDO::FETCH_ASSOC);
    $total    = $counts['total'];
    $pending  = $counts['pending'];
    $approved = $counts['approved'];
    $rejected = $counts['rejected'];

    if ($filter === 'all') {
        $stmt = $conn->query('SELECT * FROM subscriptions ORDER BY created_at DESC');
    } else {
        $stmt = $conn->prepare('SELECT * FROM subscriptions WHERE status = ? ORDER BY created_at DESC');
        $stmt->execute([$filter]);
    }
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <title>Subscriptions — DigiHealth Admin</title>
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="../assets/plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/color_skins.css">
    <style>
        .sub-card { border-radius: 14px; padding: 22px; background: #fff; box-shadow: 0 2px 12px rgba(0,0,0,0.07); margin-bottom: 16px; border-left: 5px solid #e0e0e0; transition: box-shadow 0.2s; }
        .sub-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.12); }
        .sub-card.status-pending  { border-left-color: #ff9800; }
        .sub-card.status-approved { border-left-color: #4caf50; }
        .sub-card.status-rejected { border-left-color: #f44336; }
        .sub-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 8px; }
        .sub-name { font-size: 17px; font-weight: 700; color: #333; }
        .sub-date { font-size: 12px; color: #999; }
        .sub-details { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 6px 24px; font-size: 13px; color: #555; margin-bottom: 12px; }
        .sub-details .lbl { font-weight: 700; color: #333; margin-right: 4px; }
        .sub-note { margin-top: 10px; padding: 10px 14px; background: #f8f9fa; border-radius: 8px; font-size: 13px; color: #555; border-left: 3px solid #dee2e6; }
        .sub-screenshot { margin-top: 12px; }
        .sub-screenshot img { max-width: 180px; max-height: 180px; border-radius: 10px; border: 1px solid #e0e0e0; cursor: zoom-in; transition: transform 0.2s, box-shadow 0.2s; }
        .sub-screenshot img:hover { transform: scale(1.04); box-shadow: 0 4px 16px rgba(0,0,0,0.15); }
        .badge-pending  { background: #ff9800; color:#fff; padding:4px 12px; border-radius:20px; font-size:11px; font-weight:700; }
        .badge-approved { background: #4caf50; color:#fff; padding:4px 12px; border-radius:20px; font-size:11px; font-weight:700; }
        .badge-rejected { background: #f44336; color:#fff; padding:4px 12px; border-radius:20px; font-size:11px; font-weight:700; }
        .stat-card { border-radius: 14px; padding: 22px; color: #fff; text-align: center; transition: transform 0.2s; cursor: pointer; }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-card h3 { font-size: 32px; margin: 6px 0; font-weight: 800; }
        .stat-card p { font-size: 13px; margin: 0; opacity: 0.9; }
        .stat-card i { font-size: 26px; }
        .action-btn { padding: 5px 14px; border: none; border-radius: 20px; font-size: 12px; font-weight: 600; cursor: pointer; transition: opacity 0.2s; }
        .action-btn:hover { opacity: 0.85; }
        .btn-approve { background: #4caf50; color: #fff; }
        .btn-reject  { background: #f44336; color: #fff; }
        .btn-pending { background: #ff9800; color: #fff; }
        .filter-tabs { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px; }
        .filter-tabs a { padding: 7px 18px; border-radius: 20px; font-size: 13px; font-weight: 600; text-decoration: none; background: #f1f3f4; color: #555; transition: all 0.2s; }
        .filter-tabs a.active, .filter-tabs a:hover { background: #1565c0; color: #fff; }
        .img-modal { display:none; position:fixed; top:0;left:0;width:100%;height:100%; background:rgba(0,0,0,0.85); z-index:9999; justify-content:center; align-items:center; cursor:zoom-out; }
        .img-modal.active { display:flex; }
        .img-modal img { max-width:90%; max-height:90vh; border-radius:10px; box-shadow: 0 8px 40px rgba(0,0,0,0.5); }
        .toast-notif { position:fixed; bottom:24px; right:24px; background:#333; color:#fff; padding:12px 22px; border-radius:10px; font-size:14px; z-index:10000; opacity:0; transition:opacity 0.3s; pointer-events:none; }
        .toast-notif.show { opacity:1; }

        /* ── Reject Reason Modal ─────────────────────────────── */
        .reject-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:10001; justify-content:center; align-items:center; }
        .reject-overlay.active { display:flex; }
        .reject-dialog { background:#fff; border-radius:16px; padding:32px; max-width:480px; width:92%; box-shadow:0 20px 60px rgba(0,0,0,0.3); animation: slideIn .25s ease; }
        @keyframes slideIn { from{transform:translateY(-20px);opacity:0} to{transform:translateY(0);opacity:1} }
        .reject-dialog h3 { margin:0 0 8px; font-size:20px; color:#c62828; }
        .reject-dialog p  { margin:0 0 18px; font-size:14px; color:#666; }
        .reject-dialog textarea { width:100%; border:1px solid #ddd; border-radius:8px; padding:12px; font-size:14px; resize:vertical; min-height:110px; outline:none; transition:border-color .2s; }
        .reject-dialog textarea:focus { border-color:#e52d27; }
        .reject-actions { display:flex; gap:10px; justify-content:flex-end; margin-top:18px; }
        .reject-actions .btn-cancel-rej { background:#f1f3f4; color:#555; border:none; border-radius:20px; padding:8px 20px; font-weight:600; cursor:pointer; font-size:13px; }
        .reject-actions .btn-submit-rej { background:linear-gradient(135deg,#e52d27,#b31217); color:#fff; border:none; border-radius:20px; padding:8px 24px; font-weight:700; cursor:pointer; font-size:13px; transition:opacity .2s; }
        .reject-actions .btn-submit-rej:hover { opacity:.88; }
        .reject-icon { width:56px; height:56px; background:#fff5f5; border-radius:50%; display:flex; align-items:center; justify-content:center; margin-bottom:14px; }
        .reject-icon i { font-size:28px; color:#e52d27; }
    </style>
</head>
<body class="theme-cyan">
<div class="overlay"></div>
<nav class="navbar p-l-5 p-r-5">
    <div class="container-fluid">
        <div class="navbar-header">
            <a href="javascript:void(0);" class="navbar-brand"><img src="../assets/images/logo.svg" width="30" alt=""> <span class="m-l-10">DigiHealth</span></a>
        </div>
        <?php include('top_nav.php'); ?>
    </div>
</nav>

<aside id="leftsidebar" class="sidebar">
    <?php include('admin_sidebar.php'); ?>
</aside>

<section class="content">
    <div class="block-header">
        <div class="row">
            <div class="col-lg-7 col-md-6 col-sm-12">
                <h2>Subscriptions <small>Landing page membership requests</small></h2>
            </div>
        </div>
    </div>
    <div class="container-fluid">

        <!-- Stat Cards -->
        <div class="row clearfix m-b-20">
            <div class="col-lg-3 col-md-6 col-sm-6">
                <a href="?filter=all" style="text-decoration:none;">
                    <div class="stat-card" style="background:linear-gradient(135deg,#667eea,#764ba2);">
                        <i class="zmdi zmdi-accounts"></i>
                        <h3><?= $total ?></h3><p>Total Requests</p>
                    </div>
                </a>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6">
                <a href="?filter=pending" style="text-decoration:none;">
                    <div class="stat-card" style="background:linear-gradient(135deg,#f7971e,#ffd200);">
                        <i class="zmdi zmdi-time"></i>
                        <h3><?= $pending ?></h3><p>Pending Review</p>
                    </div>
                </a>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6">
                <a href="?filter=approved" style="text-decoration:none;">
                    <div class="stat-card" style="background:linear-gradient(135deg,#11998e,#38ef7d);">
                        <i class="zmdi zmdi-check-circle"></i>
                        <h3><?= $approved ?></h3><p>Approved</p>
                    </div>
                </a>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6">
                <a href="?filter=rejected" style="text-decoration:none;">
                    <div class="stat-card" style="background:linear-gradient(135deg,#e52d27,#b31217);">
                        <i class="zmdi zmdi-close-circle"></i>
                        <h3><?= $rejected ?></h3><p>Rejected</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- Subscriptions List -->
        <div class="card">
            <div class="header d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
                <h2><strong>Subscription</strong> Requests</h2>
                <div class="filter-tabs">
                    <a href="?filter=all"      class="<?= $filter==='all'      ?'active':'' ?>">All (<?= $total ?>)</a>
                    <a href="?filter=pending"  class="<?= $filter==='pending'  ?'active':'' ?>">Pending (<?= $pending ?>)</a>
                    <a href="?filter=approved" class="<?= $filter==='approved' ?'active':'' ?>">Approved (<?= $approved ?>)</a>
                    <a href="?filter=rejected" class="<?= $filter==='rejected' ?'active':'' ?>">Rejected (<?= $rejected ?>)</a>
                </div>
            </div>
            <div class="body">
                <?php if (empty($subscriptions)): ?>
                    <div class="text-center" style="padding:50px;color:#bbb;">
                        <i class="zmdi zmdi-inbox" style="font-size:52px;display:block;margin-bottom:12px;"></i>
                        <p>No subscription requests yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($subscriptions as $sub): ?>
                    <div class="sub-card status-<?= htmlspecialchars($sub['status']) ?>" id="sub-<?= $sub['id'] ?>">
                        <div class="sub-header">
                            <span class="sub-name">
                                <i class="zmdi zmdi-account m-r-5" style="color:#1565c0;"></i>
                                <?= htmlspecialchars($sub['name']) ?>
                            </span>
                            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                                <span class="badge-<?= htmlspecialchars($sub['status']) ?>" id="badge-<?= $sub['id'] ?>"><?= ucfirst($sub['status']) ?></span>
                                <span class="sub-date"><i class="zmdi zmdi-calendar m-r-3"></i><?= date('M d, Y  H:i', strtotime($sub['created_at'])) ?></span>
                            </div>
                        </div>

                        <div class="sub-details">
                            <span><span class="lbl">Email:</span> <?= htmlspecialchars($sub['email']) ?></span>
                            <span><span class="lbl">Phone:</span> <?= htmlspecialchars($sub['phone']) ?></span>
                            <span><span class="lbl">Age:</span> <?= intval($sub['age']) ?></span>
                            <span><span class="lbl">Gender:</span> <?= htmlspecialchars($sub['gender']) ?></span>
                        </div>

                        <?php if (!empty($sub['note'])): ?>
                            <div class="sub-note"><strong>Note:</strong> <?= nl2br(htmlspecialchars($sub['note'])) ?></div>
                        <?php endif; ?>

                        <?php if ($sub['status'] === 'rejected' && !empty($sub['rejection_reason'])): ?>
                            <div class="sub-note" style="border-left-color:#f44336;background:#fff5f5;margin-top:8px;">
                                <strong style="color:#c62828;"><i class="zmdi zmdi-close-circle m-r-4"></i>Rejection Reason:</strong>
                                <?= nl2br(htmlspecialchars($sub['rejection_reason'])) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($sub['status'] === 'approved' && !empty($sub['created_patient_id'])): ?>
                            <div class="sub-note" style="border-left-color:#4caf50;background:#f1f8e9;margin-top:8px;">
                                <strong style="color:#2e7d32;"><i class="zmdi zmdi-check-circle m-r-4"></i>Patient Account Created:</strong>
                                Patient ID: <a href="patients.php" style="color:#1b5e20;font-weight:700;"><?= htmlspecialchars($sub['created_patient_id']) ?></a>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($sub['payment_screenshot_url'])): ?>
                            <div class="sub-screenshot">
                                <strong style="font-size:13px;color:#666;">Payment Screenshot:</strong><br>
                                <img src="<?= htmlspecialchars($sub['payment_screenshot_url']) ?>" alt="Payment" onclick="openModal(this.src)">
                            </div>
                        <?php else: ?>
                            <p style="color:#bbb;font-size:12px;margin-top:8px;"><i class="zmdi zmdi-image-o m-r-3"></i>No payment screenshot uploaded</p>
                        <?php endif; ?>

                        <div style="margin-top:14px;display:flex;gap:8px;flex-wrap:wrap;">
                            <?php if ($sub['status'] !== 'approved'): ?>
                                <!-- APPROVE: redirects to add-patient, pre-filled -->
                                <button class="action-btn btn-approve" onclick="approveSubscription(<?= $sub['id'] ?>)">
                                    <i class="zmdi zmdi-check m-r-4"></i>Approve &amp; Create Patient
                                </button>
                            <?php endif; ?>
                            <?php if ($sub['status'] !== 'rejected'): ?>
                                <!-- REJECT: shows reason popup -->
                                <button class="action-btn btn-reject" onclick="openRejectModal(<?= $sub['id'] ?>, '<?= addslashes(htmlspecialchars($sub['name'])) ?>')">
                                    <i class="zmdi zmdi-close m-r-4"></i>Reject
                                </button>
                            <?php endif; ?>
                            <?php if ($sub['status'] !== 'pending'): ?>
                                <button class="action-btn btn-pending" onclick="resetPending(<?= $sub['id'] ?>)">
                                    <i class="zmdi zmdi-time m-r-4"></i>Reset to Pending
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Image Zoom Modal -->
<div class="img-modal" id="imgModal" onclick="closeModal()">
    <img id="modalImg" src="" alt="Payment screenshot">
</div>

<!-- ── Rejection Reason Modal ──────────────────────────────────────── -->
<div class="reject-overlay" id="rejectOverlay">
    <div class="reject-dialog">
        <div class="reject-icon"><i class="zmdi zmdi-close-circle-o"></i></div>
        <h3>Reject Subscription</h3>
        <p id="rejectSubName" style="font-weight:600;color:#333;margin-bottom:4px;"></p>
        <p>Please provide a reason. This will be <strong>emailed to the applicant</strong> so they understand why their request was declined.</p>
        <textarea id="rejectReason" placeholder="e.g. Payment proof is unclear. Please resubmit a valid screenshot..." maxlength="1000"></textarea>
        <div class="reject-actions">
            <button class="btn-cancel-rej" onclick="closeRejectModal()">Cancel</button>
            <button class="btn-submit-rej" id="btnSubmitReject" onclick="submitReject()">
                <i class="zmdi zmdi-send m-r-4"></i>Send &amp; Reject
            </button>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="toast-notif" id="toast"></div>

<script src="../assets/bundles/libscripts.bundle.js"></script>
<script src="../assets/bundles/mainscripts.bundle.js"></script>
<script>
var _rejectId = null;

/* ── Image Modal ────────────────────────────── */
function openModal(src) {
    document.getElementById('modalImg').src = src;
    document.getElementById('imgModal').classList.add('active');
}
function closeModal() {
    document.getElementById('imgModal').classList.remove('active');
}

/* ── Toast ──────────────────────────────────── */
function showToast(msg, color) {
    var t = document.getElementById('toast');
    t.textContent = msg;
    t.style.background = color || '#333';
    t.classList.add('show');
    setTimeout(function() { t.classList.remove('show'); }, 3500);
}

/* ── APPROVE → redirect to add-patient ──────── */
function approveSubscription(id) {
    showToast('⏳ Approving… redirecting to Add Patient form', '#1565c0');
    var fd = new FormData();
    fd.append('id', id);
    fd.append('status', 'approved');

    fetch('../api/update_subscription.php', { method:'POST', body:fd, credentials:'same-origin' })
    .then(function(r){ return r.json(); })
    .then(function(data){
        if (data.success && data.redirect) {
            window.location.href = data.redirect;
        } else {
            showToast('❌ ' + (data.message || 'Error'), '#f44336');
        }
    })
    .catch(function(){ showToast('Network error', '#f44336'); });
}

/* ── REJECT MODAL ───────────────────────────── */
function openRejectModal(id, name) {
    _rejectId = id;
    document.getElementById('rejectSubName').textContent = 'Applicant: ' + name;
    document.getElementById('rejectReason').value = '';
    document.getElementById('rejectOverlay').classList.add('active');
    setTimeout(function(){ document.getElementById('rejectReason').focus(); }, 200);
}
function closeRejectModal() {
    document.getElementById('rejectOverlay').classList.remove('active');
    _rejectId = null;
}
function submitReject() {
    var reason = document.getElementById('rejectReason').value.trim();
    if (!reason) {
        document.getElementById('rejectReason').style.borderColor = '#e52d27';
        document.getElementById('rejectReason').placeholder = '⚠ Please enter a reason before rejecting.';
        return;
    }

    var btn = document.getElementById('btnSubmitReject');
    btn.disabled = true;
    btn.textContent = 'Sending…';

    var fd = new FormData();
    fd.append('id', _rejectId);
    fd.append('status', 'rejected');
    fd.append('reason', reason);

    fetch('../api/update_subscription.php', { method:'POST', body:fd, credentials:'same-origin' })
    .then(function(r){ return r.json(); })
    .then(function(data){
        btn.disabled = false;
        btn.innerHTML = '<i class="zmdi zmdi-send m-r-4"></i>Send &amp; Reject';
        closeRejectModal();
        if (data.success) {
            showToast('✅ ' + data.message, '#e52d27');
            setTimeout(function(){ location.reload(); }, 1800);
        } else {
            showToast('❌ ' + data.message, '#f44336');
        }
    })
    .catch(function(){
        btn.disabled = false;
        btn.innerHTML = '<i class="zmdi zmdi-send m-r-4"></i>Send &amp; Reject';
        showToast('Network error', '#f44336');
    });
}

/* ── RESET TO PENDING ───────────────────────── */
function resetPending(id) {
    var fd = new FormData();
    fd.append('id', id);
    fd.append('status', 'pending');

    fetch('../api/update_subscription.php', { method:'POST', body:fd, credentials:'same-origin' })
    .then(function(r){ return r.json(); })
    .then(function(data){
        if (data.success) {
            showToast('🔄 ' + data.message, '#ff9800');
            setTimeout(function(){ location.reload(); }, 1000);
        } else {
            showToast('❌ ' + data.message, '#f44336');
        }
    })
    .catch(function(){ showToast('Network error', '#f44336'); });
}

/* ── Keyboard shortcuts ─────────────────────── */
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') { closeModal(); closeRejectModal(); }
});
</script>
</body>
</html>
