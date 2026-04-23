<?php 
error_reporting(E_ALL);
ini_set("display_errors", 1);
include("../config/DB_Config.php");
session_start();

if (!isset($_SESSION["user_type"]) || ($_SESSION["user_type"] !== "admin" && $_SESSION["user_type"] !== "doctor" && $_SESSION["user_type"] !== "patient")) {
    header("Location: index.php?status=Access is denied&type=error");
    exit(0);
}

$patientId = isset($_GET["patientId"]) ? $_GET["patientId"] : "";
if (!$patientId) {
    header("Location: dashboard.php?status=Patient not found&type=error");
    exit();
}

try {
    // 1. Fetch Patient Info
    $stmt = $conn->prepare("SELECT * FROM patients WHERE \"patientID\" = ?");
    $stmt->execute([$patientId]);
    $patientData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patientData) {
        die("Patient not found.");
    }

    // 2. Fetch AI Predictions
    $ai_history = [];
    try {
        $ai_stmt = $conn->prepare("SELECT * FROM esp_ecg_predictions WHERE mac_address IN (SELECT mac_address FROM monitoring_devices WHERE \"patientID\" = ?) ORDER BY datetime DESC LIMIT 10");
        $ai_stmt->execute([$patientId]);
        $ai_history = $ai_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Table doesn't exist yet (planned for future phases)
    }

    // 3. Fetch Vitals & ECG
    $vitals_query = "SELECT vr.*, md.mac_address 
                    FROM vital_sign_readings vr
                    JOIN monitoring_devices md ON vr.\"deviceID\" = md.\"deviceID\"
                    WHERE md.\"patientID\" = ? 
                    ORDER BY vr.timestamp DESC LIMIT 20";
    $stmt = $conn->prepare($vitals_query);
    $stmt->execute([$patientId]);
    $readings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $vitalsData = ["heartRate" => [], "SpO2" => [], "Respiration" => []];
    $ecgDates = [];
    $ecgJsonArray = [];

    foreach ($readings as $row) {
        $ecgDates[] = $row["timestamp"];
        $vitalsData["heartRate"][] = $row["heartRate"];
        $vitalsData["SpO2"][] = $row["SpO2"];
        $vitalsData["Respiration"][] = $row["RespirationImpedance"];

        $stmt_ecg = $conn->prepare("SELECT ecg_value FROM esp_ecg_data WHERE \"readingID\" = ? LIMIT 500");
        $stmt_ecg->execute([$row["readingID"]]);
        $samples = $stmt_ecg->fetchAll(PDO::FETCH_COLUMN);
        $ecgJsonArray[] = json_encode(array_map("floatval", $samples));
    }
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!doctype html>
<html class="no-js " lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
<title>Patient Profile - CUST Digihealth</title>
<link rel="icon" href="favicon.ico" type="image/x-icon">
<link rel="stylesheet" href="../assets/plugins/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/color_skins.css">
<script src="../assets/js/canvasjs.min.js"></script>
</head>
<body class="theme-cyan">
<nav class="navbar p-l-5 p-r-5">
    <?php include("top_nav.php") ?>
</nav>
<aside id="leftsidebar" class="sidebar">
    <?php 
    if ($_SESSION['user_type'] === 'admin') include("admin_sidebar.php");
    elseif ($_SESSION['user_type'] === 'doctor') include("doctor_sidebar.php");
    else include("patient_sidebar.php");
    ?>
</aside>

<section class="content profile-page">
    <div class="block-header">
        <div class="row">
            <div class="col-lg-7 col-md-5 col-sm-12">
                <h2>Clinical Profile Overview
                <small class="text-muted">In-depth health analysis for <?php echo strtoupper($patientData['name']); ?></small>
                </h2>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row clearfix">
            <div class="col-lg-4 col-md-12">
                <div class="card member-card">
                    <div class="header l-blue"><h4>ID: <?php echo $patientId ?></h4></div>
                    <div class="body">
                        <div class="m-t-10">
                            <strong>Full Name:</strong> <p><?php echo htmlspecialchars($patientData["name"]); ?></p>
                            <strong>Medical History:</strong>
                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($patientData['medical_history'] ?? 'No history recorded.')); ?></p>
                        </div>
                        <hr>
                        <strong>Latest Vitals Snapshot</strong>
                        <div class="row m-t-10">
                            <div class="col-6">HR: <h5 class="text-info"><?php echo $vitalsData["heartRate"][0] ?? "--"; ?> <small>BPM</small></h5></div>
                            <div class="col-6">SpO2: <h5 class="text-success"><?php echo $vitalsData["SpO2"][0] ?? "--"; ?>%</h5></div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="header"><h2><strong>AI Monitoring</strong> Insights</h2></div>
                    <div class="body table-responsive">
                        <table class="table table-sm">
                            <thead><tr><th>Time</th><th>Result</th><th>PDR</th></tr></thead>
                            <tbody>
                                <?php foreach($ai_history as $log): ?>
                                    <tr>
                                        <td><small><?php echo date('H:i', strtotime($log['datetime'])); ?></small></td>
                                        <td><span class="badge badge-info"><?php echo $log['device']; ?></span></td>
                                        <td><?php echo round($log['PDR'] ?? 0); ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if(empty($ai_history)) echo "<tr><td colspan='3' class='text-center'>No AI logs found.</td></tr>"; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-8 col-md-12">
                <div class="card">
                    <div class="header"><h2><strong>Vital</strong> Signs Trend</h2></div>
                    <div class="body"><div id="vitalsTrendChart" style="height: 350px; width: 100%;"></div></div>
                </div>
                
                <div class="card">
                    <div class="header"><h2><strong>ECG</strong> Live Feed (Historical)</h2></div>
                    <div class="body">
                        <div id="ecgPlots" style="max-height: 500px; overflow-y: auto;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<script src="../assets/bundles/libscripts.bundle.js"></script>
<script>
$(function(){
    var spO2Points = [], hrPoints = [];
    <?php 
    $revDates = array_reverse($ecgDates);
    $revSpO2 = array_reverse($vitalsData["SpO2"]);
    $revHR = array_reverse($vitalsData["heartRate"]);
    foreach($revDates as $i => $d) {
        $s = $revSpO2[$i] ?? 0;
        $h = $revHR[$i] ?? 0;
        echo "spO2Points.push({ label: \"$d\", y: $s });\n";
        echo "hrPoints.push({ label: \"$d\", y: $h });\n";
    }
    ?>
    var chart = new CanvasJS.Chart("vitalsTrendChart", {
        theme: "light2",
        title: { text: "Patient Trends" },
        data: [{ type: "line", name: "SpO2 (%)", showInLegend: true, dataPoints: spO2Points },
               { type: "line", name: "HR (BPM)", showInLegend: true, dataPoints: hrPoints }]
    });
    chart.render();

    <?php foreach ($ecgJsonArray as $index => $data) { ?>
        $("#ecgPlots").append("<div id=\"chart_<?php echo $index; ?>\" style=\"height:300px; width:100%; margin-bottom:20px;\"></div>");
        new CanvasJS.Chart("chart_<?php echo $index; ?>", {
            theme: "light2",
            title: { text: "ECG Sample" },
            data: [{ type: "spline", color: "red", dataPoints: <?php echo $data; ?>.map(v => ({y: v})) }]
        }).render();
    <?php } ?>
});
</script>
</body>
</html>
