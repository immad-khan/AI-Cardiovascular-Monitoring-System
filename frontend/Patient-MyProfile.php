<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
include("../config/DB_Config.php");
session_start();

if (!isset($_SESSION["user_type"]) || $_SESSION["user_type"] !== "patient") {
    header("Location: index.php?status=Access is denied&type=error");
    exit();
}

// Fetch patient by email
try {
    $stmt = $conn->prepare("SELECT *, COALESCE(profile_picture, '') as profile_picture FROM patients WHERE email = ? LIMIT 1");
    $stmt->execute([$_SESSION["email"]]);
    $patientData = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$patientData) {
        die("<div class='container mt-5 alert alert-warning'>Patient profile not found. Contact Administrator.</div>");
    }
    $patientId = $patientData["patientID"];
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// Fetch latest reading for vitals snapshot
$latestReading = null;
try {
    $lr_stmt = $conn->prepare("
        SELECT vr.*, md.model
        FROM vital_sign_readings vr
        JOIN monitoring_devices md ON vr.\"deviceID\" = md.\"deviceID\"
        WHERE md.\"patientID\" = ?
        ORDER BY vr.timestamp DESC LIMIT 1
    ");
    $lr_stmt->execute([$patientId]);
    $latestReading = $lr_stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// Fetch assigned doctor info
$doctorData = null;
try {
    $doc_stmt = $conn->prepare("
        SELECT dp.full_name, dp.specialization, dp.phone_number, dp.profile_picture as doc_photo
        FROM patients p
        LEFT JOIN \"doctorProfile\" dp ON p.\"assignedDoctorID\" = dp.\"userID\"
        WHERE p.\"patientID\" = ?
    ");
    $doc_stmt->execute([$patientId]);
    $doctorData = $doc_stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// Fetch device info
$deviceData = null;
try {
    $dev_stmt = $conn->prepare("SELECT model, serialNo, status, last_heartbeat FROM monitoring_devices WHERE \"patientID\" = ? LIMIT 1");
    $dev_stmt->execute([$patientId]);
    $deviceData = $dev_stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}
?>
<!doctype html>
<html class="no-js" lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
<title>My Profile - DigiHealth</title>
<link rel="icon" href="favicon.ico" type="image/x-icon">
<link rel="stylesheet" href="../assets/plugins/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/color_skins.css">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,500;0,600;1,500;1,600&display=swap" rel="stylesheet">
<style>
    body.theme-cyan .content { margin-top: 0 !important; }
    body.theme-cyan section.content::before { display: none !important; }
    body { font-family: 'DM Sans', Arial, sans-serif; }

    .profile-cover {
        background: linear-gradient(135deg, #1a2d32, #0b9d9a);
        padding: 40px 0 30px;
        color: #fff;
        text-align: center;
    }
    .avatar-wrapper { position: relative; display: inline-block; }
    .profile-avatar {
        width: 130px;
        height: 130px;
        border-radius: 50%;
        border: 5px solid rgba(255,255,255,0.3);
        background: linear-gradient(145deg, #1a2d32, #6b7778);
        object-fit: cover;
        margin-top: -65px;
        position: relative;
        z-index: 2;
        box-shadow: 0 18px 42px rgba(11,157,154,.18);
    }
    .avatar-upload-btn {
        position: absolute;
        bottom: 6px;
        right: 6px;
        background: #0b9d9a;
        border-radius: 50%;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        border: 2px solid #fff;
        transition: background 0.2s;
    }
    .avatar-upload-btn:hover { background: #087a78; }

    .info-label { font-size: 10px; text-transform: uppercase; color: #8a9c9a; margin-bottom: 4px; font-weight: 700; letter-spacing: 0.1em; }
    .info-value { font-size: 15px; color: #333; margin-bottom: 14px; line-height: 1.35; word-break: break-word; }

    .stat-mini {
        text-align: center;
        padding: 20px 14px;
        border: 1px solid #dce9e5;
        border-radius: 8px;
        background: #f8fbfa;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .stat-mini:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(11,157,154,.08); }
    .stat-mini h4 { margin: 8px 0 4px; font-size: 1.8rem; font-weight: 500; letter-spacing: -0.04em; }
    .stat-mini small { color: #687a7e; font-size: 11px; }
    .stat-mini .stat-label { color: #687a7e; font-size: 11px; display: block; margin-top: 2px; }

    .vital-card { color: #e44747; }
    .vital-card.info-card { color: #0b9d9a; }
    .vital-card.purple-card { color: #7f5cc8; }

    .flag-badge {
        display: inline-block;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
        margin: 4px 6px 4px 0;
        background: #fff9ed;
        color: #d87216;
        border: 1px solid #f1a51a;
    }

    .analysis-box {
        border-left: 3px solid #0b9d9a;
        background: #edf6f2;
        padding: 18px 22px;
        border-radius: 8px;
        margin-bottom: 22px;
    }
    .analysis-box .label { color: #0b9d9a; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em; margin-bottom: 6px; }
    .analysis-box h4 { margin: 0; font-size: clamp(22px, 3vw, 30px); font-weight: 500; letter-spacing: -0.04em; }
    .analysis-box .confidence { color: #687a7e; font-size: 14px; margin-left: 8px; }

    .hrv-card {
        text-align: center;
        padding: 18px 12px;
        border: 1px solid #dce9e5;
        border-radius: 8px;
        background: #f8fbfa;
    }
    .hrv-card .label { font-size: 10px; color: #8a9c9a; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 6px; font-weight: 700; }
    .hrv-card h4 { margin: 0; font-size: 24px; color: #0b9d9a; font-weight: 500; letter-spacing: -0.03em; }
    .hrv-card .status { font-size: 12px; margin-top: 4px; }
    .hrv-card .status.normal { color: #4cae69; }
    .hrv-card .status.low { color: #e44747; }

    .doctor-card-mini { text-align: center; padding: 25px 20px; }
    .doctor-card-mini .avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        border: 3px solid #0b9d9a;
        margin: 0 auto 14px;
        overflow: hidden;
        background: #eff6f3;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #0b9d9a;
        font-size: 30px;
    }
    .doctor-card-mini .avatar img { width: 100%; height: 100%; object-fit: cover; }
    .doctor-card-mini h5 { margin: 0 0 4px; font-size: 18px; font-weight: 600; letter-spacing: -0.03em; }
    .doctor-card-mini p { margin: 0; color: #687a7e; font-size: 13px; }
    .doctor-card-mini .phone-line { font-size: 13px; margin-top: 5px; color: #687a7e; }
    .doctor-chat-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 16px;
        padding: 10px 20px;
        border: 1px solid #0b9d9a;
        border-radius: 4px;
        color: #0b9d9a;
        font-size: 13px;
        font-weight: 600;
        transition: all 0.2s;
    }
    .doctor-chat-link:hover { background: #0b9d9a; color: #fff; text-decoration: none; }

    .device-info-value { font-size: 14px; color: #333; margin-bottom: 10px; }
    .device-badge { display: inline-block; padding: 3px 10px; border-radius: 4px; font-size: 11px; font-weight: 600; }
    .device-badge.online { background: rgba(76,174,105,.12); color: #4cae69; }
    .device-badge.assigned { background: rgba(11,157,154,.12); color: #0b9d9a; }
    .device-badge.offline { background: rgba(136,136,136,.12); color: #888; }

    .vitals-panel-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 24px;
    }
    .vitals-panel-header h2 {
        margin: 8px 0 0;
        font-size: clamp(28px, 4vw, 44px);
        line-height: 1.05;
        letter-spacing: -0.05em;
    }
    .vitals-panel-header h2 em {
        font-family: 'Playfair Display', Georgia, serif;
        color: #0b9d9a;
        font-style: italic;
        font-weight: 500;
    }
    .reading-time-badge {
        padding: 7px 12px;
        border: 1px solid #dce9e5;
        border-radius: 999px;
        color: #687a7e;
        font-size: 11px;
        font-weight: 600;
        white-space: nowrap;
    }

    .eyebrow { margin: 0; color: #0b9d9a; font-size: 11px; font-weight: 700; letter-spacing: 0.18em; text-transform: uppercase; }

    .hrv-subhead { display: flex; align-items: end; justify-content: space-between; gap: 20px; margin-bottom: 16px; }
    .hrv-subhead h5 { margin: 0; font-size: 22px; font-weight: 600; letter-spacing: -0.03em; }
    .hrv-subhead p { max-width: 300px; margin: 0; color: #687a7e; font-size: 12px; line-height: 1.5; }

    .flags-heading {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 14px;
    }
    .flags-heading h5 { margin: 0; font-size: 20px; font-weight: 600; letter-spacing: -0.03em; }
    .flags-heading .warn-circle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: #f1a51a;
        color: #fff;
        font-size: 13px;
        font-weight: 700;
    }

    .section-title-custom {
        font-size: 20px;
        font-weight: 700;
        color: #1a2d32;
        letter-spacing: -0.03em;
    }
    .section-title-custom strong { font-weight: 700; }
    .section-title-custom em {
        font-family: 'Playfair Display', Georgia, serif;
        color: #0b9d9a;
        font-style: italic;
        font-weight: 500;
    }

    .ai-insights-table th {
        background: #f8fbfa;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #8a9c9a;
        font-weight: 700;
        border-bottom: 1px solid #dce9e5;
    }
    .ai-insights-table td {
        font-size: 13px;
        vertical-align: middle;
    }

    .action-card-custom {
        border: 1px solid #dce9e5;
        border-radius: 8px;
        background: #fff;
        text-decoration: none;
        color: inherit;
        transition: transform 0.25s, box-shadow 0.25s, border-color 0.25s;
    }
    .action-card-custom:hover {
        transform: translateY(-4px);
        box-shadow: 0 15px 40px rgba(11,157,154,.08);
        border-color: #0b9d9a;
        color: inherit;
    }
    .action-card-custom .card-body { padding: 28px 24px; }
    .action-card-custom h5 { font-size: 20px; font-weight: 600; margin-bottom: 6px; letter-spacing: -0.03em; }
    .action-card-custom p { margin: 0; color: #687a7e; font-size: 13px; }
    .action-card-custom .arrow-link { display: inline-block; margin-top: 14px; color: #0b9d9a; font-size: 12px; font-weight: 700; }

    .task-card {
        border-left: 4px solid;
        padding: 16px;
        border-radius: 6px;
        background: #f8fbfa;
        margin-bottom: 14px;
    }
    .task-card.pending { border-color: #e44747; }
    .task-card.escalated { border-color: #f1a51a; }
    .task-card.reviewed { border-color: #4cae69; }
    .task-badge-custom {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        color: #fff;
    }
    .task-badge-custom.pending { background: #e44747; }
    .task-badge-custom.escalated { background: #f1a51a; }
    .task-badge-custom.reviewed { background: #4cae69; }

    .summary-btn {
        padding: 12px 28px;
        border: 1px solid #0b9d9a;
        border-radius: 4px;
        background: none;
        color: #0b9d9a;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }
    .summary-btn:hover { background: #0b9d9a; color: #fff; }
</style>
<!-- Mobile CSS Fix --><link rel="stylesheet" href="../assets/css/mobile.css">
</head>
<body class="theme-cyan">
<div class="overlay"></div>
<nav class="navbar p-l-5 p-r-5"><?php include("top_nav.php") ?></nav>

<aside id="leftsidebar" class="sidebar">
    <?php include("patient_sidebar.php") ?>
</aside>

<section class="content">
    <!-- Profile Cover -->
    <div class="profile-cover">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <?php
                    $initials = '';
                    $nameParts = explode(' ', $patientData['name']);
                    foreach ($nameParts as $part) { $initials .= strtoupper(substr($part, 0, 1)); }
                    $initials = substr($initials, 0, 2);
                    ?>
                    <div class="avatar-wrapper">
                        <img id="my-profile-img"
                             src="<?php echo !empty($patientData['profile_picture']) ? htmlspecialchars($patientData['profile_picture']) : '../assets/images/profile_av.jpg'; ?>"
                             class="profile-avatar" alt="Profile">
                        <label for="my-profile-input" class="avatar-upload-btn" title="Change Photo">
                            <i class="zmdi zmdi-camera" style="color:#fff;font-size:16px;"></i>
                        </label>
                        <input type="file" id="my-profile-input" accept="image/*" style="display:none;">
                    </div>
                    <h3 style="font-weight:600;margin-top:15px;letter-spacing:-0.04em;"><?php echo htmlspecialchars($patientData['name']); ?></h3>
                    <p style="opacity:0.85;margin:0;font-size:14px;">Patient ID: <strong style="color:#24c4bb;"><?php echo htmlspecialchars($patientId); ?></strong></p>
                    <div id="my-profile-status" style="font-size:12px;margin-top:5px;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid" style="padding-top: 25px; padding-bottom: 40px;">
        <?php if(isset($_GET['status'])): ?>
            <div class="alert alert-<?php echo (isset($_GET['type']) && $_GET['type'] == 'error') ? 'danger' : 'success'; ?>">
                <?php echo htmlspecialchars($_GET['status']); ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Left Column: Personal Info + Doctor -->
            <div class="col-lg-4 col-md-12">
                <!-- Personal Information -->
                <div class="card">
                    <div class="header"><h2><strong>Personal</strong> Information</h2></div>
                    <div class="body">
                        <div class="info-label">Full Name</div>
                        <div class="info-value"><?php echo htmlspecialchars($patientData['name']); ?></div>

                        <div class="info-label">Patient ID</div>
                        <div class="info-value"><?php echo htmlspecialchars($patientId); ?></div>

                        <div class="info-label">Age</div>
                        <div class="info-value"><?php echo htmlspecialchars($patientData['age']); ?> years</div>

                        <div class="info-label">Gender</div>
                        <div class="info-value"><?php echo htmlspecialchars($patientData['gender']); ?></div>

                        <div class="info-label">Phone</div>
                        <div class="info-value"><?php echo htmlspecialchars($patientData['phone_no']); ?></div>

                        <div class="info-label">Email</div>
                        <div class="info-value"><?php echo htmlspecialchars($patientData['email']); ?></div>

                        <div class="info-label">Ward / Staff</div>
                        <div class="info-value"><?php echo !empty($patientData['ward_no']) ? htmlspecialchars($patientData['ward_no']) : 'N/A'; ?></div>

                        <hr style="border-color:#dce9e5;">
                        <div class="info-label">Medical History</div>
                        <div class="info-value" style="font-size:13px;line-height:1.6;">
                            <?php echo nl2br(htmlspecialchars($patientData['medical_history'] ?? 'No medical history added yet.')); ?>
                        </div>
                    </div>
                </div>

                <!-- Assigned Doctor -->
                <div class="card">
                    <div class="header" style="background:var(--pale,#eff6f3);"><h2 style="color:#0b9d9a;"><strong>My</strong> Doctor</h2></div>
                    <div class="body doctor-card-mini">
                        <div class="avatar">
                            <?php if (!empty($doctorData['doc_photo'])): ?>
                                <img src="<?php echo htmlspecialchars($doctorData['doc_photo']); ?>" alt="Doctor">
                            <?php else: ?>
                                <i class="zmdi zmdi-account" style="font-size:32px;color:#0b9d9a;"></i>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($doctorData['full_name'])): ?>
                            <h5>Dr. <?php echo htmlspecialchars($doctorData['full_name']); ?></h5>
                            <p><?php echo htmlspecialchars($doctorData['specialization']); ?></p>
                            <?php if (!empty($doctorData['phone_number'])): ?>
                                <p class="phone-line"><i class="zmdi zmdi-phone" style="font-size:12px;"></i> <?php echo htmlspecialchars($doctorData['phone_number']); ?></p>
                            <?php endif; ?>
                            <a href="Patient-Chat.php" class="doctor-chat-link">
                                Chat with doctor
                                <i class="zmdi zmdi-arrow-right" style="font-size:14px;"></i>
                            </a>
                        <?php else: ?>
                            <h5>No doctor assigned</h5>
                            <p>Contact administrator</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Connected Device -->
                <?php if (!empty($deviceData)): ?>
                <div class="card">
                    <div class="header" style="background:var(--pale,#eff6f3);"><h2 style="color:#0b9d9a;"><strong>IoT</strong> Device</h2></div>
                    <div class="body">
                        <div class="info-label">Model</div>
                        <div class="device-info-value"><?php echo htmlspecialchars($deviceData['model'] ?? 'N/A'); ?></div>
                        <div class="info-label">Serial</div>
                        <div class="device-info-value" style="font-size:13px;"><?php echo htmlspecialchars($deviceData['serialNo'] ?? 'N/A'); ?></div>
                        <div class="info-label">Status</div>
                        <div class="device-info-value">
                            <span class="device-badge <?php echo strtolower($deviceData['status']); ?>">
                                <?php echo htmlspecialchars($deviceData['status']); ?>
                            </span>
                        </div>
                        <div class="info-label">Last Heartbeat</div>
                        <div class="device-info-value" style="font-size:13px;"><?php echo $deviceData['last_heartbeat'] ? date('M d, Y H:i', strtotime($deviceData['last_heartbeat'])) : 'N/A'; ?></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right Column: Vitals Snapshot + HRV + Flags -->
            <div class="col-lg-8 col-md-12">
                <!-- Vitals Snapshot -->
                <div class="card">
                    <div class="header" style="background:linear-gradient(135deg,#1a2d32,#0b9d9a);color:#fff;">
                        <div class="vitals-panel-header" style="margin-bottom:0;">
                            <div>
                                <p class="eyebrow" style="color:rgba(255,255,255,.7);">Latest vitals snapshot</p>
                                <h2 style="color:#fff;">Your current <em style="color:#24c4bb;">heart signal.</em></h2>
                            </div>
                            <?php if ($latestReading): ?>
                                <span class="reading-time-badge" style="border-color:rgba(255,255,255,.25);color:rgba(255,255,255,.8);"><?php echo date('M d, H:i', strtotime($latestReading['timestamp'])); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="body">
                        <?php if ($latestReading): ?>
                        <div class="row" style="margin-bottom:22px;">
                            <div class="col-lg-4 col-md-4 col-sm-4">
                                <div class="stat-mini vital-card">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M20.8 8.8c0 5-8.8 10-8.8 10s-8.8-5-8.8-10A4.7 4.7 0 0 1 12 6.3a4.7 4.7 0 0 1 8.8 2.5Z"/></svg>
                                    <h4 style="color:#e44747;"><?php echo $latestReading['heartRate'] ?? '--'; ?></h4>
                                    <small>Heart Rate (BPM)</small>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-4 col-sm-4">
                                <div class="stat-mini info-card">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h3l2-4 3.3 8 2.3-5 1.4 2H21"/></svg>
                                    <h4 style="color:#0b9d9a;"><?php echo $latestReading['RespirationImpedance'] ?? '--'; ?></h4>
                                    <small>Respiration</small>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-4 col-sm-4">
                                <div class="stat-mini purple-card">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 19 6v5c0 4.8-3 8.1-7 10-4-1.9-7-5.2-7-10V6l7-3Z"/></svg>
                                    <h4 style="color:#7f5cc8;"><?php echo $latestReading['signal_quality'] ?? '--'; ?><small style="font-size:14px;color:#687a7e;">/100</small></h4>
                                    <small>Signal Quality</small>
                                </div>
                            </div>
                        </div>

                        <!-- AI Rhythm Analysis -->
                        <?php if (!empty($latestReading['final_prediction'])): ?>
                        <div class="analysis-box">
                            <div class="label">AI Rhythm Analysis</div>
                            <h4><?php echo htmlspecialchars($latestReading['final_prediction']); ?>
                                <span class="confidence">Confidence: <?php echo round(($latestReading['confidenceScore'] ?? 0) * 100, 1); ?>%</span>
                            </h4>
                        </div>
                        <?php endif; ?>

                        <!-- HRV Metrics -->
                        <div class="hrv-subhead">
                            <h5>HRV Metrics</h5>
                            <p>Longitudinal markers that help explain nervous-system balance.</p>
                        </div>
                        <div class="row" style="margin-bottom:22px;">
                            <div class="col-md-4 col-sm-4">
                                <div class="hrv-card">
                                    <div class="label">SDNN</div>
                                    <h4><?php echo $latestReading['hrv_sdnn'] !== null ? round($latestReading['hrv_sdnn'], 1) . ' ms' : '--'; ?></h4>
                                    <div class="status <?php echo ($latestReading['hrv_sdnn'] !== null && $latestReading['hrv_sdnn'] > 50) ? 'normal' : 'low'; ?>">
                                        <?php echo ($latestReading['hrv_sdnn'] !== null && $latestReading['hrv_sdnn'] > 50) ? 'Normal' : 'Low'; ?> &gt;50ms
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-4">
                                <div class="hrv-card">
                                    <div class="label">RMSSD</div>
                                    <h4><?php echo $latestReading['hrv_rmssd'] !== null ? round($latestReading['hrv_rmssd'], 1) . ' ms' : '--'; ?></h4>
                                    <div class="status <?php echo ($latestReading['hrv_rmssd'] !== null && $latestReading['hrv_rmssd'] > 20) ? 'normal' : 'low'; ?>">
                                        <?php echo ($latestReading['hrv_rmssd'] !== null && $latestReading['hrv_rmssd'] > 20) ? 'Normal' : 'Low'; ?> &gt;20ms
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-4">
                                <div class="hrv-card">
                                    <div class="label">Reading Time</div>
                                    <h4 style="font-size:18px;"><?php echo date('M d', strtotime($latestReading['timestamp'])); ?></h4>
                                    <div class="normal" style="font-size:12px;color:#687a7e;"><?php echo date('H:i', strtotime($latestReading['timestamp'])); ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Arrhythmia Flags -->
                        <?php if (!empty($latestReading['arrhythmia_flags'])): ?>
                        <div class="flags-heading">
                            <span class="warn-circle">!</span>
                            <h5>Detected Flags</h5>
                        </div>
                        <div style="margin-bottom:15px;">
                            <?php foreach(explode('; ', $latestReading['arrhythmia_flags']) as $flag): ?>
                                <span class="flag-badge"><?php echo htmlspecialchars($flag); ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <?php else: ?>
                        <div style="text-align:center;padding:50px 20px;color:#687a7e;">
                            <p style="font-size:15px;">No vital signs data available yet.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row">
                    <div class="col-md-4">
                        <a href="Patient-Profile.php?patientId=<?php echo urlencode($patientId); ?>" class="action-card-custom" style="display:block;">
                            <div class="card-body text-center">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#0b9d9a" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h3l2-4 3.3 8 2.3-5 1.4 2H21"/></svg>
                                <h5>My Health Profile</h5>
                                <p>View ECG, vitals trends & AI summary</p>
                                <span class="arrow-link">Open profile <i class="zmdi zmdi-arrow-right" style="font-size:13px;"></i></span>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="Patient-Chat.php" class="action-card-custom" style="display:block;">
                            <div class="card-body text-center">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#0b9d9a" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.11 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92Z"/></svg>
                                <h5>Doctor Chat</h5>
                                <p>Message your assigned physician</p>
                                <span class="arrow-link">Open chat <i class="zmdi zmdi-arrow-right" style="font-size:13px;"></i></span>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="Patient-AI-Assistant.php" class="action-card-custom" style="display:block;">
                            <div class="card-body text-center">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#0b9d9a" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M9.5 4.5A3.5 3.5 0 0 0 6 8v.3A3.2 3.2 0 0 0 4 11.2a3.2 3.2 0 0 0 2 2.9v.4a3.5 3.5 0 0 0 5 3.1"/><path d="M14.5 4.5A3.5 3.5 0 0 1 18 8v.3a3.2 3.2 0 0 1 2 2.9 3.2 3.2 0 0 1-2 2.9v.4a3.5 3.5 0 0 1-5 3.1"/><path d="M12 5v14"/></svg>
                                <h5>AI Assistant</h5>
                                <p>Chat with AI about your health</p>
                                <span class="arrow-link">Open assistant <i class="zmdi zmdi-arrow-right" style="font-size:13px;"></i></span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="../assets/bundles/libscripts.bundle.js"></script>
<script>
document.getElementById('my-profile-input').addEventListener('change', function(e) {
    var file = e.target.files[0];
    if (!file) return;
    var status = document.getElementById('my-profile-status');
    var preview = document.getElementById('my-profile-img');
    if (file.size > 5 * 1024 * 1024) { status.innerHTML = '<span style="color:#e44747;">Max 5MB</span>'; return; }
    var reader = new FileReader();
    reader.onload = function(ev) { preview.src = ev.target.result; };
    reader.readAsDataURL(file);
    var formData = new FormData();
    formData.append('profile_image', file);
    status.innerHTML = '<span style="color:#0b9d9a;">Uploading...</span>';
    fetch('../api/upload_profile_image.php', { method: 'POST', body: formData, credentials: 'same-origin' })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) { preview.src = data.url; status.innerHTML = '<span style="color:#4cae69;">Updated!</span>'; setTimeout(function(){ status.innerHTML=''; }, 3000); }
        else { status.innerHTML = '<span style="color:#e44747;">' + (data.message || 'Failed') + '</span>'; }
    })
    .catch(function() { status.innerHTML = '<span style="color:#e44747;">Network error</span>'; });
});
</script>
</body>
</html>
