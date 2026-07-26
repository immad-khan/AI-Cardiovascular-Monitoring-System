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
    if (!$patientData) { die("Patient not found."); }

    // 2. Fetch Vitals & ECG
    $vitals_query = "SELECT vr.*, md.mac_address
                    FROM vital_sign_readings vr
                    LEFT JOIN monitoring_devices md ON vr.\"deviceID\" = md.\"deviceID\"
                    WHERE vr.\"patientID\" = ? OR (vr.\"patientID\" IS NULL AND md.\"patientID\" = ?)
                    ORDER BY vr.timestamp DESC LIMIT 20";
    $stmt = $conn->prepare($vitals_query);
    $stmt->execute([$patientId, $patientId]);
    $readings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $vitalsData = ["heartRate" => [], "Respiration" => []];
    $ecgDates = [];
    $ecgJsonArray = [];
    $latestReading = null;

    foreach ($readings as $idx => $row) {
        if ($idx === 0) $latestReading = $row;
        $ecgDates[] = $row["timestamp"];
        $vitalsData["heartRate"][] = $row["heartRate"];
        $vitalsData["Respiration"][] = $row["RespirationImpedance"];

        $stmt_ecg = $conn->prepare("SELECT ecg_value FROM esp_ecg_data WHERE \"readingID\" = ? LIMIT 500");
        $stmt_ecg->execute([$row["readingID"]]);
        $samples = $stmt_ecg->fetchAll(PDO::FETCH_COLUMN);
        $ecgJsonArray[] = json_encode(array_map("floatval", $samples));
    }

    // 3. Fetch tasks for this patient (doctors only)
    $patient_tasks = [];
    if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'doctor') {
        $task_stmt = $conn->prepare("
            SELECT dt.\"taskID\", dt.task_type, dt.status, dt.created_at, dt.notes
            FROM doctor_tasks dt
            WHERE dt.\"patientID\" = ? AND dt.\"doctorID\" = ?
            ORDER BY dt.created_at DESC LIMIT 10
        ");
        $task_stmt->execute([$patientId, $_SESSION['user_id']]);
        $patient_tasks = $task_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!doctype html>
<html class="no-js" lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
<title>My Health Profile - CUST Digihealth</title>
<link rel="icon" href="favicon.ico" type="image/x-icon">
<link rel="stylesheet" href="../assets/plugins/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/color_skins.css">
<script src="../assets/js/canvasjs.min.js"></script>
<style>body.theme-cyan .content { margin-top: 0 !important; } body.theme-cyan .sidebar { top: 0 !important; }</style>
</head>
<body class="theme-cyan">
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
                <h2>My Health Profile
                <small class="text-muted">ECG monitoring & vital signs for <?php echo strtoupper($patientData['name']); ?></small>
                </h2>
            </div>
            <div class="col-lg-5 col-md-7 col-sm-12 text-right">
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row clearfix">
            <div class="col-lg-12 col-md-12">
                <!-- Latest Reading Summary Bar -->
                <?php if ($latestReading): ?>
                <div class="card" style="background:linear-gradient(135deg,#e3f2fd,#bbdefb);border-left:4px solid #1565c0;">
                    <div class="body">
                        <div class="row">
                            <div class="col-md-3 col-6 text-center">
                                <small style="color:#666;">HEART RATE</small>
                                <h4 style="color:#1565c0;margin:0;"><?php echo $latestReading['heartRate'] ?? '--'; ?> <small>BPM</small></h4>
                            </div>
                            <div class="col-md-3 col-6 text-center">
                                <small style="color:#666;">RESP</small>
                                <h4 style="color:#e65100;margin:0;"><?php echo $latestReading['RespirationImpedance'] ?? '--'; ?></h4>
                            </div>
                            <div class="col-md-2 col-6 text-center">
                                <?php
                                $sqi = $latestReading['signal_quality'] ?? null;
                                $sqi_class = 'secondary';
                                if ($sqi !== null) { if ($sqi >= 80) $sqi_class = 'success'; elseif ($sqi >= 50) $sqi_class = 'warning'; else $sqi_class = 'danger'; }
                                ?>
                                <small style="color:#666;">SQI</small>
                                <h4 style="color:#6a1b9a;margin:0;"><span class="badge badge-<?php echo $sqi_class; ?>"><?php echo $sqi !== null ? $sqi : '--'; ?>/100</span></h4>
                            </div>
                            <div class="col-md-2 col-6 text-center">
                                <small style="color:#666;">SDNN</small>
                                <h4 style="color:#5c6bc0;margin:0;"><?php echo $latestReading['hrv_sdnn'] !== null ? round($latestReading['hrv_sdnn'], 1) . 'ms' : '--'; ?></h4>
                            </div>
                            <div class="col-md-2 col-6 text-center">
                                <small style="color:#666;">RMSSD</small>
                                <h4 style="color:#5c6bc0;margin:0;"><?php echo $latestReading['hrv_rmssd'] !== null ? round($latestReading['hrv_rmssd'], 1) . 'ms' : '--'; ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- AI Rhythm & Flags Row -->
                <?php if ($latestReading): ?>
                <div class="row m-b-10">
                    <div class="col-md-8">
                        <?php if (!empty($latestReading['final_prediction'])): ?>
                        <div class="card" style="border-left:4px solid #00bcd4;">
                            <div class="body" style="padding:12px 20px;">
                                <strong style="color:#0097a7;"><i class="zmdi zmdi-robot m-r-5"></i>AI Rhythm:</strong>
                                <span class="badge badge-info" style="font-size:13px;padding:5px 12px;margin-left:5px;"><?php echo htmlspecialchars($latestReading['final_prediction']); ?></span>
                                <small class="text-muted" style="margin-left:8px;">Confidence: <?php echo round(($latestReading['confidenceScore'] ?? 0) * 100, 1); ?>%</small>
                                <span class="text-muted" style="margin-left:8px;font-size:12px;"><?php echo date('M d, H:i', strtotime($latestReading['timestamp'])); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <?php if (!empty($latestReading['arrhythmia_flags'])): ?>
                        <div class="card" style="border-left:4px solid #ff9800;">
                            <div class="body" style="padding:10px 15px;">
                                <small style="color:#e65100;font-weight:600;"><i class="zmdi zmdi-alert-circle m-r-5"></i>FLAGS:</small><br>
                                <?php foreach(explode('; ', $latestReading['arrhythmia_flags']) as $flag): ?>
                                    <span class="flag-badge" style="background:#fff3e0;color:#e65100;border:1px solid #ffcc02;font-size:11px;padding:3px 8px;margin:1px 2px;"><?php echo htmlspecialchars($flag); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Charts Row -->
                <div class="row clearfix">
                    <div class="col-lg-7 col-md-12">
                        <div class="card">
                            <div class="header"><h2><strong>Vital</strong> Signs Trend</h2></div>
                            <div class="body"><div id="vitalsTrendChart" style="height: 350px; width: 100%;"></div></div>
                        </div>
                    </div>
                    <div class="col-lg-5 col-md-12">
                        <div class="card">
                            <div class="header"><h2><strong>AI Monitoring</strong> Insights</h2></div>
                            <div class="body table-responsive" style="max-height:350px;overflow-y:auto;">
                                <table class="table table-sm">
                                    <thead><tr><th>Time</th><th>AI Result</th><th>SDNN</th><th>RMSSD</th><th>SQI</th></tr></thead>
                                    <tbody>
                                        <?php
                                        $hist_stmt = $conn->prepare(
                                            "SELECT vr.timestamp, vr.final_prediction, vr.\"confidenceScore\", vr.hrv_sdnn, vr.hrv_rmssd, vr.signal_quality
                                             FROM vital_sign_readings vr
                                             JOIN monitoring_devices md ON vr.\"deviceID\" = md.\"deviceID\"
                                             WHERE md.\"patientID\" = ?
                                             ORDER BY vr.timestamp DESC LIMIT 15"
                                        );
                                        $hist_stmt->execute([$patientId]);
                                        $hist_rows = $hist_stmt->fetchAll(PDO::FETCH_ASSOC);
                                        foreach($hist_rows as $log):
                                            $sqi_v = $log['signal_quality'];
                                            $sqi_c = $sqi_v >= 80 ? 'success' : ($sqi_v >= 50 ? 'warning' : 'danger');
                                        ?>
                                            <tr>
                                                <td><small><?php echo date('M d H:i', strtotime($log['timestamp'])); ?></small></td>
                                                <td>
                                                    <?php if($log['final_prediction']): ?>
                                                    <span class="badge badge-info" style="white-space:normal;font-size:11px;"><?php echo htmlspecialchars($log['final_prediction']); ?></span>
                                                    <?php else: ?><span class="text-muted">--</span><?php endif; ?>
                                                </td>
                                                <td style="font-size:12px;"><?php echo $log['hrv_sdnn'] !== null ? round($log['hrv_sdnn'],1).'ms' : '--'; ?></td>
                                                <td style="font-size:12px;"><?php echo $log['hrv_rmssd'] !== null ? round($log['hrv_rmssd'],1).'ms' : '--'; ?></td>
                                                <td>
                                                    <?php if($sqi_v !== null): ?>
                                                    <span class="badge badge-<?php echo $sqi_c; ?>" style="font-size:11px;"><?php echo $sqi_v; ?></span>
                                                    <?php else: ?>--<?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if(empty($hist_rows)) echo "<tr><td colspan='5' class='text-center text-muted'>No AI logs found.</td></tr>"; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ECG Plots -->
                <div class="card">
                    <div class="header"><h2><strong>ECG</strong> Waveform History</h2></div>
                    <div class="body">
                        <div id="ecgPlots" style="max-height: 500px; overflow-y: auto;"></div>
                    </div>
                </div>

                <!-- AI Health Summary -->
                <div class="card">
                    <div class="header" style="background:linear-gradient(135deg,#00bcd4,#0097a7);color:#fff;">
                        <h2><i class="zmdi zmdi-robot m-r-10"></i><strong>AI Health</strong> Summary</h2>
                    </div>
                    <div class="body">
                        <div id="ai-summary-content">
                            <div class="text-center p-t-20 p-b-20">
                                <button class="btn btn-primary btn-round" id="generate-summary-btn" onclick="generateAISummary()">
                                    <i class="zmdi zmdi-play-circle m-r-5"></i> Generate AI Health Summary
                                </button>
                                <p class="text-muted m-t-10" style="font-size:12px;">AI will analyze your ECG, vitals, and monitoring data to provide a comprehensive health overview.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Task Panel: only visible to doctors when tasks exist -->
        <?php if ($_SESSION['user_type'] === 'doctor' && !empty($patient_tasks)): ?>
        <div class="row clearfix" id="pending-tasks">
            <div class="col-lg-12">
                <div class="card">
                    <div class="header bg-red">
                        <h2 style="color:#fff;"><i class="zmdi zmdi-assignment m-r-10"></i><strong>Clinical Tasks</strong> Requiring Review <small style="color:rgba(255,255,255,0.7);">For <?php echo htmlspecialchars($patientData['name']); ?></small></h2>
                    </div>
                    <div class="body">
                        <div class="row">
                            <?php foreach($patient_tasks as $task): ?>
                            <div class="col-lg-4 col-md-6" id="task-card-<?php echo $task['taskID']; ?>">
                                <div class="card" style="border-left: 4px solid <?php echo $task['status'] === 'Pending' ? '#f44336' : ($task['status'] === 'Escalated' ? '#FF9800' : '#4CAF50'); ?>;">
                                    <div class="body">
                                        <span class="badge badge-<?php echo $task['status'] === 'Pending' ? 'danger' : ($task['status'] === 'Escalated' ? 'warning' : 'success'); ?> m-b-10"><?php echo $task['status']; ?></span>
                                        <h5 class="m-b-5"><?php echo htmlspecialchars($task['task_type']); ?></h5>
                                        <small class="text-muted"><?php echo date('M d, Y H:i', strtotime($task['created_at'])); ?></small>
                                        <?php if($task['notes']): ?>
                                            <p class="m-t-10 text-muted"><em><?php echo htmlspecialchars($task['notes']); ?></em></p>
                                        <?php endif; ?>
                                        <?php if($task['status'] === 'Pending'): ?>
                                        <div class="m-t-15">
                                            <textarea class="form-control m-b-10" placeholder="Add clinical notes..." id="notes-<?php echo $task['taskID']; ?>"></textarea>
                                            <button class="btn btn-success btn-sm btn-round" onclick="resolveTask(<?php echo $task['taskID']; ?>, 'Reviewed')"><i class="zmdi zmdi-check"></i> Reviewed</button>
                                            <button class="btn btn-warning btn-sm btn-round m-l-5" onclick="resolveTask(<?php echo $task['taskID']; ?>, 'Escalated')"><i class="zmdi zmdi-flag"></i> Escalate</button>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>
<script src="../assets/bundles/libscripts.bundle.js"></script>
<script>
function resolveTask(taskID, status) {
    var notes = document.getElementById('notes-' + taskID);
    var notesVal = notes ? notes.value : '';
    var formData = new FormData();
    formData.append('taskID', taskID);
    formData.append('status', status);
    formData.append('notes', notesVal);
    fetch('../api/tasks.php', { method: 'POST', body: formData, credentials: 'same-origin' })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            var card = document.getElementById('task-card-' + taskID);
            if (card) {
                card.querySelector('.card').style.borderLeftColor = status === 'Reviewed' ? '#4CAF50' : '#FF9800';
                card.querySelector('.badge').className = 'badge badge-' + (status === 'Reviewed' ? 'success' : 'warning') + ' m-b-10';
                card.querySelector('.badge').textContent = status;
                var actionDiv = card.querySelector('.m-t-15');
                if (actionDiv) actionDiv.innerHTML = '<span class="text-success"><i class="zmdi zmdi-check-circle"></i> ' + status + '</span>';
            }
        } else { alert('Error: ' + data.message); }
    })
    .catch(() => alert('Network error.'));
}
</script>
<script>
$(function(){
    var hrPoints = [];
    <?php
    $revDates = array_reverse($ecgDates);
    $revHR = array_reverse($vitalsData["heartRate"]);
    foreach($revDates as $i => $d) {
        $h = $revHR[$i] ?? 0;
        echo "hrPoints.push({ label: \"$d\", y: $h });\n";
    }
    ?>
    var chart = new CanvasJS.Chart("vitalsTrendChart", {
        theme: "light2",
        title: { text: "Heart Rate Trend" },
        data: [{ type: "line", name: "HR (BPM)", color: "#e53935", showInLegend: true, dataPoints: hrPoints }]
    });
    chart.render();

    <?php foreach ($ecgJsonArray as $index => $data) {
        if ($data !== '[]' && $data !== '') { ?>
        $("#ecgPlots").append("<div id=\"chart_<?php echo $index; ?>\" style=\"height:300px; width:100%; margin-bottom:20px;\"></div>");
        new CanvasJS.Chart("chart_<?php echo $index; ?>", {
            theme: "light2",
            title: { text: "ECG Sample #<?php echo $index + 1; ?>" },
            data: [{ type: "spline", color: "red", dataPoints: <?php echo $data; ?>.map(v => ({y: v})) }]
        }).render();
    <?php } } ?>
    if ($("#ecgPlots").is(':empty')) {
        $("#ecgPlots").html("<div class='text-center p-30 text-muted'><i class='zmdi zmdi-chart-donut' style='font-size:2rem;'></i><p>No ECG waveform data available.</p></div>");
    }
});

function generateAISummary() {
    var btn = document.getElementById('generate-summary-btn');
    var content = document.getElementById('ai-summary-content');
    btn.disabled = true;
    btn.innerHTML = '<i class="zmdi zmdi-spin zmdi-hc-spin m-r-5"></i> Analyzing health data...';
    content.innerHTML = '<div class="text-center p-t-20 p-b-20"><div class="zmdi zmdi-spin zmdi-hc-spin" style="font-size:2rem;color:#00bcd4;"></div><p class="m-t-10 text-muted">AI is analyzing your ECG, vitals, HRV metrics, and monitoring history...</p><p class="text-muted" style="font-size:11px;">This may take 10-30 seconds</p></div>';
    fetch('../api/ai_health_summary.php?patientId=<?php echo urlencode($patientId); ?>', { credentials: 'same-origin' })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            var riskClass = 'secondary';
            if (data.risk_level === 'Low') riskClass = 'success';
            else if (data.risk_level === 'Moderate') riskClass = 'warning';
            else if (data.risk_level === 'High' || data.risk_level === 'Critical') riskClass = 'danger';
            var formattedSummary = data.summary.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>').replace(/\n\n/g, '</p><p>').replace(/\n- /g, '</p><p class="m-l-10">&bull; ').replace(/\n/g, '<br>');
            content.innerHTML = '<div class="m-b-15"><span class="badge badge-' + riskClass + '" style="font-size:13px;padding:6px 12px;"><i class="zmdi zmdi-shield m-r-5"></i>Risk Level: ' + data.risk_level + '</span></div><div style="line-height:1.8;font-size:14px;"><p>' + formattedSummary + '</p></div><hr><div class="text-right"><button class="btn btn-sm btn-default btn-round" onclick="generateAISummary()"><i class="zmdi zmdi-refresh m-r-5"></i>Refresh</button></div>';
        } else {
            content.innerHTML = '<div class="alert alert-danger"><i class="zmdi zmdi-alert-circle m-r-5"></i>' + (data.message || 'Failed') + '</div><button class="btn btn-sm btn-primary btn-round" onclick="generateAISummary()">Try Again</button>';
        }
    })
    .catch(function() {
        content.innerHTML = '<div class="alert alert-danger">Network error.</div><button class="btn btn-sm btn-primary btn-round" onclick="generateAISummary()">Try Again</button>';
    });
}
</script>
</body>
</html>
