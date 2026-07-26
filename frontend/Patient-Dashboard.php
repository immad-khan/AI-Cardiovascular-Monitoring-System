<?php
include("../config/DB_Config.php");
session_start();

if (!isset($_SESSION["user_type"]) || $_SESSION["user_type"] !== "patient") {
    header("Location: index.php?status=Unauthorized Access&type=error");
    exit();
}

$userID = $_SESSION["user_id"];

try {
    $stmt = $conn->prepare("
        SELECT p.\"patientID\", p.name, p.age, p.gender, p.medical_history,
               dp.full_name as doctor_name, dp.specialization as doctor_spec, dp.phone_number as doctor_phone
        FROM patients p
        LEFT JOIN \"doctorProfile\" dp ON p.\"assignedDoctorID\" = dp.\"userID\"
        WHERE p.email = ? LIMIT 1
    ");
    $stmt->execute([$_SESSION["email"]]);
    $patientData = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$patientData) {
        die("<div class='container mt-5 alert alert-warning'>Patient profile not linked. Contact Administrator.</div>");
    }
    $patientId = $patientData["patientID"];

    $vitals_query = "SELECT vr.*, md.model
                    FROM vital_sign_readings vr
                    LEFT JOIN monitoring_devices md ON vr.\"deviceID\" = md.\"deviceID\"
                    WHERE vr.\"patientID\" = ? OR (vr.\"patientID\" IS NULL AND md.\"patientID\" = ?)
                    ORDER BY vr.timestamp DESC LIMIT 15";
    $stmt = $conn->prepare($vitals_query);
    $stmt->execute([$patientId, $patientId]);
    $readings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $alert_query = "SELECT a.* FROM \"CRITICAL_ALERT\" a
                   LEFT JOIN monitoring_devices md ON a.\"deviceID\" = md.\"deviceID\"
                   WHERE md.\"patientID\" = ? ORDER BY a.timestamp DESC LIMIT 5";
    $stmt = $conn->prepare($alert_query);
    $stmt->execute([$patientId]);
    $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Data Connection Error: " . $e->getMessage());
}

$latest = $readings[0] ?? null;
?>
<!doctype html>
<html class="no-js" lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <title>Dashboard - CUST Digihealth</title>
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="../assets/plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/color_skins.css">
    <script src="../assets/js/canvasjs.min.js"></script>
    <style>
        .dash-stat {
            text-align: center;
            padding: 20px 10px;
            border-radius: 10px;
            transition: transform 0.2s;
        }

        .dash-stat:hover {
            transform: translateY(-3px);
        }

        .dash-stat i {
            font-size: 2rem;
            margin-bottom: 8px;
        }

        .dash-stat h3 {
            margin: 5px 0;
            font-size: 1.6rem;
        }

        .dash-stat small {
            color: #888;
        }

        .quick-link {
            text-decoration: none;
        }

        .quick-link .card {
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }

        .quick-link .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        }

        body.theme-cyan .content {
            margin-top: 0 !important;
        }

        body.theme-cyan .sidebar {
            top: 0 !important;
        }
    </style>
</head>

<body class="theme-cyan">
    <aside id="leftsidebar" class="sidebar">
        <?php include("patient_sidebar.php") ?>
    </aside>

    <section class="content home">
        <div class="block-header">
            <div class="row">
                <div class="col-lg-7 col-md-6 col-sm-12">
                    <h2>Dashboard <small>Welcome, <?php echo explode(' ', $patientData['name'])[0]; ?>!</small></h2>
                </div>
            </div>
        </div>
        <div class="container-fluid">
            <!-- Monitoring Session Control Banner -->
            <div class="card p-3 m-b-20" id="session-card"
                style="background: #ffffff; border-left: 5px solid #dc3545; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div class="row align-items-center">
                    <div class="col-md-8 col-sm-12 d-flex align-items-center" style="display:flex; align-items:center;">
                        <div id="session-indicator"
                            style="width: 14px; height: 14px; min-width: 14px; border-radius: 50%; background-color: #dc3545; box-shadow: 0 0 8px #dc3545; margin-right: 15px;">
                        </div>
                        <div>
                            <h5 class="m-t-0 m-b-5" id="session-status-text" style="font-weight: 700; color: #222;">ECG
                                Monitoring Session Stopped (Idle)</h5>
                            <small class="text-muted" style="display:block; line-height:1.4;">Turn session ON when
                                electrodes are connected to record vitals. Turn OFF when disconnected to prevent
                                recording idle noise.</small>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-12 text-md-right m-t-10 m-md-t-0" style="text-align: right;">
                        <button id="toggle-session-btn" onclick="toggleSession()"
                            class="btn btn-success btn-round waves-effect px-4"
                            style="font-weight:600; padding: 10px 24px;">
                            <i class="zmdi zmdi-play m-r-5"></i> Start Monitoring Session
                        </button>
                    </div>
                </div>
            </div>

            <!-- Vitals Stat Cards -->
            <div class="row clearfix">
                <div class="col-lg-3 col-md-6 col-sm-6">
                    <div class="card dash-stat" style="background:linear-gradient(135deg,#e53935,#c62828);color:#fff;">
                        <i class="zmdi zmdi-favorite"></i>
                        <h3><?php echo ($latest && isset($latest['heartRate'])) ? $latest['heartRate'] : '--'; ?> <small
                                style="color:rgba(255,255,255,0.8);">BPM</small></h3>
                        <small style="color:rgba(255,255,255,0.7);">Heart Rate</small>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6">
                    <div class="card dash-stat" style="background:linear-gradient(135deg,#1e88e5,#1565c0);color:#fff;">
                        <i class="zmdi zmdi-chart-line"></i>
                        <h3><?php echo ($latest && isset($latest['hrv_sdnn']) && $latest['hrv_sdnn'] !== null) ? round($latest['hrv_sdnn'], 0) : '--'; ?>
                            <small style="color:rgba(255,255,255,0.8);">ms</small></h3>
                        <small style="color:rgba(255,255,255,0.7);">HRV (SDNN)</small>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6">
                    <?php
                    $status_color = empty($alerts) ? '#43a047' : '#e53935';
                    $status_text = empty($alerts) ? 'Normal' : count($alerts) . ' Alert(s)';
                    ?>
                    <div class="card dash-stat"
                        style="background:linear-gradient(135deg,<?php echo $status_color; ?>,<?php echo $status_color; ?>dd);color:#fff;">
                        <i class="zmdi zmdi-shield-check"></i>
                        <h3 style="font-size:1.1rem;"><?php echo $status_text; ?></h3>
                        <small style="color:rgba(255,255,255,0.7);">Health Status</small>
                    </div>
                </div>
            </div>

            <!-- Chart + Alerts -->
            <div class="row clearfix">
                <div class="col-lg-8 col-md-12">
                    <div class="card">
                        <div class="header">
                            <h2><strong>Heart Rate</strong> Trend</h2>
                        </div>
                        <div class="body">
                            <div id="hrTrendChart" style="height: 250px; width: 100%;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-12">
                    <div class="card">
                        <div class="header">
                            <h2><strong>Recent</strong> Alerts</h2>
                        </div>
                        <div class="body">
                            <ul class="list-unstyled">
                                <?php foreach ($alerts as $alert): ?>
                                    <li class="m-b-15">
                                        <small
                                            class="text-muted"><?php echo date('M d, H:i', strtotime($alert['timestamp'])); ?></small>
                                        <p class="m-b-0 text-danger" style="font-size:13px;"><strong>Alert:</strong>
                                            <?php echo $alert['message']; ?></p>
                                    </li>
                                <?php endforeach; ?>
                                <?php if (empty($alerts))
                                    echo "<li class='text-muted'>No recent alerts. Stay healthy!</li>"; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="row clearfix">
                <div class="col-lg-3 col-md-6 col-sm-6">
                    <a href="Patient-MyProfile.php" class="quick-link">
                        <div class="card">
                            <div class="body text-center" style="padding:25px;">
                                <i class="zmdi zmdi-account-circle" style="font-size:2.5rem;color:#00bcd4;"></i>
                                <h5 class="m-t-10">My Profile</h5>
                                <p class="text-muted" style="font-size:12px;">Personal info & vitals snapshot</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6">
                    <a href="Patient-Profile.php?patientId=<?php echo urlencode($patientId); ?>" class="quick-link">
                        <div class="card">
                            <div class="body text-center" style="padding:25px;">
                                <i class="zmdi zmdi-heart-pulse" style="font-size:2.5rem;color:#e53935;"></i>
                                <h5 class="m-t-10">My Health Profile</h5>
                                <p class="text-muted" style="font-size:12px;">ECG, vitals trends & AI summary</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6">
                    <a href="Patient-AI-Assistant.php" class="quick-link">
                        <div class="card">
                            <div class="body text-center" style="padding:25px;">
                                <i class="zmdi zmdi-robot" style="font-size:2.5rem;color:#7b1fa2;"></i>
                                <h5 class="m-t-10">AI Assistant</h5>
                                <p class="text-muted" style="font-size:12px;">Chat with AI about your health</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6">
                    <div class="card">
                        <div class="body text-center" style="padding:25px;">
                            <i class="zmdi zmdi-account" style="font-size:2.5rem;color:#ff9800;"></i>
                            <h5 class="m-t-10">My Doctor</h5>
                            <?php if (!empty($patientData['doctor_name'])): ?>
                                <p class="text-muted" style="font-size:12px;">Dr.
                                    <?php echo htmlspecialchars($patientData['doctor_name']); ?></p>
                                <small
                                    class="text-info"><?php echo htmlspecialchars($patientData['doctor_spec']); ?></small>
                            <?php else: ?>
                                <p class="text-warning" style="font-size:12px;">No doctor assigned</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="../assets/bundles/libscripts.bundle.js"></script>
    <script>
        window.onload = function () {
            var chart = new CanvasJS.Chart("hrTrendChart", {
                animationEnabled: true,
                theme: "light2",
                axisY: { title: "BPM", includeZero: false },
                data: [{
                    type: "line",
                    lineColor: "#e53935",
                    markerColor: "#e53935",
                    dataPoints: [
                        <?php
                        $revReadings = array_reverse($readings);
                        foreach ($revReadings as $r) {
                            echo "{ x: new Date('" . $r['timestamp'] . "'), y: " . $r['heartRate'] . " },";
                        }
                        ?>
                    ]
                }]
            });
            chart.render();
        };

        function checkSessionStatus() {
            fetch('../api/toggle_session.php?action=status')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        updateSessionUI(data.is_monitoring);
                    }
                });
        }

        function toggleSession() {
            var btn = document.getElementById('toggle-session-btn');
            btn.disabled = true;
            fetch('../api/toggle_session.php?action=toggle')
                .then(r => r.json())
                .then(data => {
                    btn.disabled = false;
                    if (data.success) {
                        updateSessionUI(data.is_monitoring);
                        alert(data.message);
                    } else {
                        alert(data.message);
                    }
                })
                .catch(() => { btn.disabled = false; });
        }

        function updateSessionUI(isMonitoring) {
            var card = document.getElementById('session-card');
            var indicator = document.getElementById('session-indicator');
            var text = document.getElementById('session-status-text');
            var btn = document.getElementById('toggle-session-btn');

            if (isMonitoring) {
                if (card) card.style.borderLeftColor = '#28a745';
                indicator.style.backgroundColor = '#28a745';
                indicator.style.boxShadow = '0 0 8px #28a745';
                text.innerText = 'ECG Monitoring Session Active';
                btn.className = 'btn btn-danger btn-round waves-effect px-4';
                btn.style.padding = '10px 24px';
                btn.innerHTML = '<i class="zmdi zmdi-power m-r-5"></i> Stop Monitoring Session';
            } else {
                if (card) card.style.borderLeftColor = '#dc3545';
                indicator.style.backgroundColor = '#dc3545';
                indicator.style.boxShadow = '0 0 8px #dc3545';
                text.innerText = 'ECG Monitoring Session Stopped (Idle)';
                btn.className = 'btn btn-success btn-round waves-effect px-4';
                btn.style.padding = '10px 24px';
                btn.innerHTML = '<i class="zmdi zmdi-play m-r-5"></i> Start Monitoring Session';
            }
        }

        checkSessionStatus();
    </script>
</body>

</html>