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
<title>My Profile - CUST Digihealth</title>
<link rel="icon" href="favicon.ico" type="image/x-icon">
<link rel="stylesheet" href="../assets/plugins/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/color_skins.css">
<style>
    .profile-cover { background: linear-gradient(135deg, #00bcd4, #0097a7); padding: 40px 0 20px; color: #fff; text-align: center; }
    .profile-avatar { width: 140px; height: 140px; border-radius: 50%; border: 5px solid #fff; object-fit: cover; margin-top: -70px; position: relative; z-index: 2; box-shadow: 0 4px 15px rgba(0,0,0,0.15); }
    .avatar-wrapper { position: relative; display: inline-block; }
    .avatar-upload-btn { position: absolute; bottom: 8px; right: 8px; background: #00bcd4; border-radius: 50%; width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; cursor: pointer; border: 2px solid #fff; transition: background 0.2s; }
    .avatar-upload-btn:hover { background: #0097a7; }
    .info-label { font-size: 11px; text-transform: uppercase; color: #999; margin-bottom: 2px; font-weight: 600; letter-spacing: 0.5px; }
    .info-value { font-size: 15px; color: #333; margin-bottom: 12px; }
    .stat-mini { text-align: center; padding: 15px 10px; background: #f8f9fa; border-radius: 10px; }
    .stat-mini h4 { margin: 0; font-size: 1.4rem; }
    .stat-mini small { color: #999; font-size: 11px; }
    .flag-badge { display: inline-block; padding: 5px 10px; border-radius: 6px; font-size: 12px; margin: 2px 4px 2px 0; }
</style>
</head>
<body class="theme-cyan">
<aside id="leftsidebar" class="sidebar">
    <?php include("patient_sidebar.php") ?>
</aside>

<section class="content">
    <div class="profile-cover">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="avatar-wrapper">
                        <img id="my-profile-img"
                             src="<?php echo !empty($patientData['profile_picture']) ? htmlspecialchars($patientData['profile_picture']) : '../assets/images/profile_av.jpg'; ?>"
                             class="profile-avatar" alt="Profile">
                        <label for="my-profile-input" class="avatar-upload-btn" title="Change Photo">
                            <i class="zmdi zmdi-camera" style="color:#fff;font-size:16px;"></i>
                        </label>
                        <input type="file" id="my-profile-input" accept="image/*" style="display:none;">
                    </div>
                    <h3 class="m-t-15" style="font-weight:600;"><?php echo htmlspecialchars($patientData['name']); ?></h3>
                    <p style="opacity:0.85;margin:0;">Patient ID: <strong><?php echo htmlspecialchars($patientId); ?></strong></p>
                    <div id="my-profile-status" style="font-size:12px;" class="m-t-5"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid m-t-20">
        <?php if(isset($_GET['status'])): ?>
            <div class="alert alert-<?php echo (isset($_GET['type']) && $_GET['type'] == 'error') ? 'danger' : 'success'; ?>">
                <?php echo htmlspecialchars($_GET['status']); ?>
            </div>
        <?php endif; ?>

        <div class="row clearfix">
            <!-- Left Column: Personal Info + Doctor -->
            <div class="col-lg-4 col-md-12">
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

                        <hr>
                        <div class="info-label">Medical History</div>
                        <div class="info-value" style="font-size:13px;line-height:1.6;">
                            <?php echo nl2br(htmlspecialchars($patientData['medical_history'] ?? 'No history recorded.')); ?>
                        </div>
                    </div>
                </div>

                <!-- Assigned Doctor -->
                <div class="card">
                    <div class="header" style="background:#e3f2fd;"><h2 style="color:#1565c0;"><strong>My</strong> Doctor</h2></div>
                    <div class="body text-center">
                        <?php if (!empty($doctorData['full_name'])): ?>
                            <img src="<?php echo !empty($doctorData['doc_photo']) ? htmlspecialchars($doctorData['doc_photo']) : '../assets/images/sm/avatar1.jpg'; ?>"
                                 style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid #1565c0;" alt="Doctor">
                            <h5 class="m-t-10" style="color:#333;">Dr. <?php echo htmlspecialchars($doctorData['full_name']); ?></h5>
                            <p class="text-muted" style="font-size:13px;"><?php echo htmlspecialchars($doctorData['specialization']); ?></p>
                            <p style="font-size:13px;"><i class="zmdi zmdi-phone m-r-5"></i><?php echo htmlspecialchars($doctorData['phone_number']); ?></p>
                        <?php else: ?>
                            <i class="zmdi zmdi-account-o" style="font-size:3rem;color:#ccc;"></i>
                            <p class="text-muted m-t-10">No doctor assigned yet</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Connected Device -->
                <?php if (!empty($deviceData)): ?>
                <div class="card">
                    <div class="header"><h2><strong>IoT</strong> Device</h2></div>
                    <div class="body">
                        <div class="info-label">Model</div>
                        <div class="info-value"><?php echo htmlspecialchars($deviceData['model'] ?? 'N/A'); ?></div>
                        <div class="info-label">Serial</div>
                        <div class="info-value" style="font-size:13px;"><?php echo htmlspecialchars($deviceData['serialNo'] ?? 'N/A'); ?></div>
                        <div class="info-label">Status</div>
                        <div class="info-value">
                            <span class="badge badge-<?php echo ($deviceData['status'] === 'Online') ? 'success' : (($deviceData['status'] === 'Assigned') ? 'info' : 'secondary'); ?>">
                                <?php echo htmlspecialchars($deviceData['status']); ?>
                            </span>
                        </div>
                        <div class="info-label">Last Heartbeat</div>
                        <div class="info-value" style="font-size:13px;"><?php echo $deviceData['last_heartbeat'] ? date('M d, Y H:i', strtotime($deviceData['last_heartbeat'])) : 'N/A'; ?></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right Column: Vitals Snapshot + HRV + Flags -->
            <div class="col-lg-8 col-md-12">
                <!-- Vitals Snapshot -->
                <div class="card">
                    <div class="header" style="background:linear-gradient(135deg,#e53935,#c62828);color:#fff;">
                        <h2><i class="zmdi zmdi-favorite m-r-10"></i><strong>Latest</strong> Vitals Snapshot</h2>
                    </div>
                    <div class="body">
                        <?php if ($latestReading): ?>
                        <div class="row m-b-20">
                            <div class="col-lg-4 col-md-6 col-sm-6">
                                <div class="stat-mini">
                                    <i class="zmdi zmdi-favorite text-danger" style="font-size:1.5rem;"></i>
                                    <h4 class="text-danger m-t-5"><?php echo $latestReading['heartRate'] ?? '--'; ?></h4>
                                    <small>Heart Rate (BPM)</small>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6 col-sm-6">
                                <div class="stat-mini">
                                    <i class="zmdi zmdi-air" style="font-size:1.5rem;color:#ff9800;"></i>
                                    <h4 class="m-t-5" style="color:#ff9800;"><?php echo $latestReading['RespirationImpedance'] ?? '--'; ?></h4>
                                    <small>Respiration</small>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6 col-sm-6">
                                <?php
                                $sqi = $latestReading['signal_quality'] ?? null;
                                $sqi_class = 'secondary';
                                if ($sqi !== null) {
                                    if ($sqi >= 80) $sqi_class = 'success';
                                    elseif ($sqi >= 50) $sqi_class = 'warning';
                                    else $sqi_class = 'danger';
                                }
                                ?>
                                <div class="stat-mini">
                                    <i class="zmdi zmdi-signal" style="font-size:1.5rem;color:#9c27b0;"></i>
                                    <h4 class="m-t-5" style="color:#9c27b0;"><?php echo $sqi !== null ? $sqi : '--'; ?><small>/100</small></h4>
                                    <small>Signal Quality</small>
                                </div>
                            </div>
                        </div>

                        <!-- AI Rhythm Analysis -->
                        <?php if (!empty($latestReading['final_prediction'])): ?>
                        <div style="background:#e3f2fd;border-radius:10px;padding:15px 20px;margin-bottom:20px;">
                            <div class="info-label" style="color:#1565c0;">AI Rhythm Analysis</div>
                            <h4 style="color:#1565c0;margin:5px 0;">
                                <?php echo htmlspecialchars($latestReading['final_prediction']); ?>
                                <small style="font-weight:normal;color:#666;">
                                    (Confidence: <?php echo round(($latestReading['confidenceScore'] ?? 0) * 100, 1); ?>%)
                                </small>
                            </h4>
                        </div>
                        <?php endif; ?>

                        <!-- HRV Metrics -->
                        <h5 class="m-b-15"><i class="zmdi zmdi-chart-line m-r-5"></i>HRV Metrics</h5>
                        <div class="row m-b-20">
                            <div class="col-md-4">
                                <div class="stat-mini">
                                    <small style="color:#999;">SDNN</small>
                                    <h4 style="color:#5c6bc0;">
                                        <?php echo $latestReading['hrv_sdnn'] !== null ? round($latestReading['hrv_sdnn'], 1) . ' ms' : '--'; ?>
                                    </h4>
                                    <small style="color:<?php echo ($latestReading['hrv_sdnn'] !== null && $latestReading['hrv_sdnn'] > 50) ? '#4caf50' : '#f44336'; ?>;">Normal &gt;50ms</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stat-mini">
                                    <small style="color:#999;">RMSSD</small>
                                    <h4 style="color:#5c6bc0;">
                                        <?php echo $latestReading['hrv_rmssd'] !== null ? round($latestReading['hrv_rmssd'], 1) . ' ms' : '--'; ?>
                                    </h4>
                                    <small style="color:<?php echo ($latestReading['hrv_rmssd'] !== null && $latestReading['hrv_rmssd'] > 20) ? '#4caf50' : '#f44336'; ?>;">Normal &gt;20ms</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stat-mini">
                                    <small style="color:#999;">Reading Time</small>
                                    <h4 style="color:#333;font-size:1rem;">
                                        <?php echo date('M d', strtotime($latestReading['timestamp'])); ?>
                                        <br><small style="color:#666;"><?php echo date('H:i', strtotime($latestReading['timestamp'])); ?></small>
                                    </h4>
                                </div>
                            </div>
                        </div>

                        <!-- Arrhythmia Flags -->
                        <?php if (!empty($latestReading['arrhythmia_flags'])): ?>
                        <h5 class="m-b-10"><i class="zmdi zmdi-alert-circle m-r-5 text-warning"></i>Detected Flags</h5>
                        <div style="margin-bottom:15px;">
                            <?php foreach(explode('; ', $latestReading['arrhythmia_flags']) as $flag): ?>
                                <span class="flag-badge" style="background:#fff3e0;color:#e65100;border:1px solid #ffcc02;">
                                    <?php echo htmlspecialchars($flag); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <?php else: ?>
                        <div class="text-center p-t-30 p-b-30">
                            <i class="zmdi zmdi-chart-donut" style="font-size:3rem;color:#ccc;"></i>
                            <p class="text-muted m-t-10">No vital signs data available yet.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row clearfix">
                    <div class="col-md-6">
                        <a href="Patient-Profile.php?patientId=<?php echo urlencode($patientId); ?>" style="text-decoration:none;">
                            <div class="card" style="cursor:pointer;transition:transform 0.2s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='none'">
                                <div class="body text-center" style="padding:25px;">
                                    <i class="zmdi zmdi-heart-pulse" style="font-size:2.5rem;color:#e53935;"></i>
                                    <h5 class="m-t-10" style="color:#333;">My Health Profile</h5>
                                    <p class="text-muted" style="font-size:12px;">View ECG, vitals trends & AI summary</p>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="Patient-AI-Assistant.php" style="text-decoration:none;">
                            <div class="card" style="cursor:pointer;transition:transform 0.2s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='none'">
                                <div class="body text-center" style="padding:25px;">
                                    <i class="zmdi zmdi-robot" style="font-size:2.5rem;color:#00bcd4;"></i>
                                    <h5 class="m-t-10" style="color:#333;">AI Assistant</h5>
                                    <p class="text-muted" style="font-size:12px;">Chat with AI about your health</p>
                                </div>
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
    if (file.size > 5 * 1024 * 1024) { status.innerHTML = '<span class="text-danger">Max 5MB</span>'; return; }
    var reader = new FileReader();
    reader.onload = function(ev) { preview.src = ev.target.result; };
    reader.readAsDataURL(file);
    var formData = new FormData();
    formData.append('profile_image', file);
    status.innerHTML = '<span class="text-info">Uploading...</span>';
    fetch('../api/upload_profile_image.php', { method: 'POST', body: formData, credentials: 'same-origin' })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) { preview.src = data.url; status.innerHTML = '<span class="text-success">Updated!</span>'; setTimeout(function(){ status.innerHTML=''; }, 3000); }
        else { status.innerHTML = '<span class="text-danger">' + (data.message || 'Failed') + '</span>'; }
    })
    .catch(function() { status.innerHTML = '<span class="text-danger">Network error</span>'; });
});
</script>
</body>
</html>
