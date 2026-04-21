<?php 
include("../config/DB_Config.php");
session_start();

// Access Control: ONLY Patients can access this dashboard
if (!isset($_SESSION["user_type"]) || $_SESSION["user_type"] !== "patient") {
    header("Location: index.php?status=Unauthorized Access&type=error");
    exit();
}

$userID = $_SESSION["user_id"];

try {
    // 1. Fetch Patient ID linked to this User Account
    $stmt = $conn->prepare("SELECT \"patientID\", name, age, gender, medical_history FROM patients WHERE email = ? LIMIT 1");
    // Using email mapping which is consistent across users and patients tables
    $stmt->execute([$_SESSION["email"]]);
    $patientData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patientData) {
        die("<div class='container mt-5 alert alert-warning'>Patient profile not linked to this account. Contact Administrator.</div>");
    }

    $patientId = $patientData["patientID"];

    // 2. Fetch Latest Vitals
    $vitals_query = "SELECT vr.*, md.model 
                    FROM vital_sign_readings vr
                    JOIN monitoring_devices md ON vr.\"deviceID\" = md.\"deviceID\"
                    WHERE md.\"patientID\" = ? 
                    ORDER BY vr.timestamp DESC LIMIT 15";
    $stmt = $conn->prepare($vitals_query);
    $stmt->execute([$patientId]);
    $readings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Fetch Recent Critical Alerts for this patient
    $alert_query = "SELECT a.* FROM \"CRITICAL_ALERT\" a 
                   JOIN monitoring_devices md ON a.\"deviceID\" = md.\"deviceID\"
                   WHERE md.\"patientID\" = ? ORDER BY a.timestamp DESC LIMIT 5";
    $stmt = $conn->prepare($alert_query);
    $stmt->execute([$patientId]);
    $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Data Connection Error: " . $e->getMessage());
}
?>
<!doctype html>
<html class="no-js " lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
<title>My Health Dashboard - CUST Digihealth</title>
<link rel="icon" href="favicon.ico" type="image/x-icon">
<link rel="stylesheet" href="../assets/plugins/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/color_skins.css">
<script src="../assets/js/canvasjs.min.js"></script>
</head>
<body class="theme-cyan">
<nav class="navbar p-l-5 p-r-5">
    <div class="container-fluid">
        <div class="navbar-header">
            <a href="javascript:void(0);" class="navbar-brand"><img src="../assets/images/logo.svg" width="30" alt="CUST"> <span class="m-l-10">My Health Portal</span></a>
        </div>
        <ul class="nav navbar-nav navbar-right">
            <li><a href="logout.php" class="mega-menu" title="Sign Out"><i class="zmdi zmdi-power"></i></a></li>
        </ul>
    </div>
</nav>

<aside id="leftsidebar" class="sidebar">
    <?php include("patient_sidebar.php") ?>
</aside>

<section class="content home">
    <div class="block-header">
        <div class="row">
            <div class="col-lg-7 col-md-6 col-sm-12">
                <h2>Health Overview <small>Welcome, <?php echo explode(' ', $patientData['name'])[0]; ?>!</small></h2>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row clearfix">
            <div class="col-lg-4 col-md-6 col-sm-12">
                <div class="card info-box-2 bg-blue">
                    <div class="body">
                        <div class="icon"><i class="zmdi zmdi-favorite"></i></div>
                        <div class="content">
                            <div class="text">LATEST HEART RATE</div>
                            <div class="number"><?php echo $readings[0]['heartRate'] ?? '--'; ?> <small>BPM</small></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 col-sm-12">
                <div class="card info-box-2 bg-green">
                    <div class="body">
                        <div class="icon"><i class="zmdi zmdi-brightness-low"></i></div>
                        <div class="content">
                            <div class="text">LATEST SpO2</div>
                            <div class="number"><?php echo $readings[0]['SpO2'] ?? '--'; ?> <small>%</small></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-12 col-sm-12">
                <div class="card info-box-2 bg-red">
                    <div class="body">
                        <div class="icon"><i class="zmdi zmdi-alert-triangle"></i></div>
                        <div class="content">
                            <div class="text">HEALTH STATUS</div>
                            <div class="number"><?php echo (count($alerts) > 0) ? 'Attention Needed' : 'Normal'; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row clearfix">
            <div class="col-lg-8 col-md-12">
                <div class="card">
                    <div class="header"><h2>Heart Rate Trend (%)</h2></div>
                    <div class="body">
                        <div id="hrTrendChart" style="height: 250px; width: 100%;"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-12">
                <div class="card">
                    <div class="header"><h2>Recent Notifications</h2></div>
                    <div class="body">
                        <ul class="list-unstyled">
                            <?php foreach($alerts as $alert): ?>
                                <li class="m-b-15">
                                    <small class="text-muted"><?php echo date('M d, H:i', strtotime($alert['timestamp'])); ?></small>
                                    <p class="m-b-0 text-danger"><strong>Critical Alert:</strong> <?php echo $alert['message']; ?></p>
                                </li>
                            <?php endforeach; ?>
                            <?php if(empty($alerts)) echo "<li>No recent alerts. Stay healthy!</li>"; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row clearfix">
            <div class="col-sm-12">
                <div class="card">
                    <div class="header"><h2>My Medical Summary</h2></div>
                    <div class="body">
                        <p><strong>Primary Concern:</strong> Cardiovascular Monitoring</p>
                        <p><strong>History:</strong> <?php echo nl2br(htmlspecialchars($patientData['medical_history'])); ?></p>
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
            lineColor: "#2196F3",
            markerColor: "#2196F3",
            dataPoints: [
                <?php 
                $revReadings = array_reverse($readings);
                foreach($revReadings as $r) {
                    echo "{ x: new Date('".$r['timestamp']."'), y: ".$r['heartRate']." },";
                }
                ?>
            ]
        }]
    });
    chart.render();
}
</script>
</body>
</html>
