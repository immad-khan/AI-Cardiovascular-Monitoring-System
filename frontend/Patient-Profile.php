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

    // 2. Fetch Vitals & ECG
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
<title>Patient Profile - Supabase</title>
<link rel="stylesheet" href="../assets/plugins/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/color_skins.css">
<script src="../assets/js/canvasjs.min.js"></script>
</head>
<body class="theme-cyan">
<section class="content profile-page">
    <div class="container-fluid">
        <div class="row clearfix">
            <div class="col-lg-4 col-md-12">
                <div class="card member-card">
                    <div class="header l-coral"><h4>Patient ID: <?php echo $patientId ?></h4></div>
                    <div class="body">
                        <strong>Name:</strong> <p><?php echo $patientData["name"]; ?></p>
                        <hr>
                        <strong>Latest Vitals</strong>
                        <div class="row">
                            <div class="col-6">HR: <h5><?php echo $vitalsData["heartRate"][0] ?? "--"; ?></h5></div>
                            <div class="col-6">SpO2: <h5 class="text-success"><?php echo $vitalsData["SpO2"][0] ?? "--"; ?>%</h5></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8 col-md-12">
                <div class="card">
                    <div class="body"><div id="vitalsTrendChart" style="height: 300px; width: 100%;"></div></div>
                </div>
            </div>
        </div>
        <div id="ecgPlots" style="margin-top: 20px;"></div>
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
