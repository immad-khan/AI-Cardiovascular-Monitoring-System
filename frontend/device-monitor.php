<?php
session_start();
include("../config/DB_Config.php");

if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['admin', 'doctor'])) {
    header("Location: index.php?status=Access denied&type=error");
    exit();
}
?>
<!doctype html>
<html class="no-js" lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
<title>IoT Device Monitor - CUST DigiHealth</title>
<link rel="icon" href="favicon.ico" type="image/x-icon">
<link rel="stylesheet" href="../assets/plugins/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/color_skins.css">
<style>
    .device-card { transition: all 0.3s ease; }
    .pulse-live {
        display: inline-block;
        width: 12px; height: 12px;
        border-radius: 50%;
        background: #4CAF50;
        box-shadow: 0 0 0 0 rgba(76,175,80,0.7);
        animation: pulse-green 1.5s infinite;
        margin-right: 6px;
    }
    .pulse-offline {
        display: inline-block;
        width: 12px; height: 12px;
        border-radius: 50%;
        background: #9E9E9E;
        margin-right: 6px;
    }
    @keyframes pulse-green {
        0%   { box-shadow: 0 0 0 0 rgba(76,175,80,0.7); }
        70%  { box-shadow: 0 0 0 10px rgba(76,175,80,0); }
        100% { box-shadow: 0 0 0 0 rgba(76,175,80,0); }
    }
    .stat-pill {
        font-size: 12px;
        padding: 4px 10px;
        border-radius: 20px;
        font-weight: 600;
        letter-spacing: 0.5px;
    }
    #last-update { font-size: 12px; color: #aaa; }
    .prediction-badge { font-size: 13px; padding: 5px 12px; }
    .ecg-sparkline { height: 60px; }
    .data-flow-log {
        background: #1a1a2e;
        color: #0f0;
        font-family: monospace;
        font-size: 12px;
        padding: 12px;
        border-radius: 4px;
        height: 200px;
        overflow-y: auto;
    }
    .data-flow-log .log-err  { color: #f44336; }
    .data-flow-log .log-warn { color: #FF9800; }
    .data-flow-log .log-ok   { color: #4CAF50; }
</style>
<!-- Mobile CSS Fix --><link rel="stylesheet" href="../assets/css/mobile.css">
</head>
<body class="theme-cyan">
<div class="page-loader-wrapper">
    <div class="loader">
        <div class="m-t-30"><img class="zmdi-hc-spin" src="../assets/images/logo.svg" width="48" height="48" alt="Loading"></div>
        <p>Loading Device Monitor...</p>
    </div>
</div>
<div class="overlay"></div>

<nav class="navbar p-l-5 p-r-5">
    <?php include("top_nav.php") ?>
</nav>
<aside id="leftsidebar" class="sidebar">
    <?php
    if ($_SESSION['user_type'] === 'admin') include("admin_sidebar.php");
    else include("doctor_sidebar.php");
    ?>
</aside>

<section class="content">
    <div class="block-header">
        <div class="row">
            <div class="col-lg-7 col-md-5 col-sm-12">
                <h2>IoT Device Monitor
                <small class="text-muted">Real-time data pipeline status</small>
                </h2>
            </div>
            <div class="col-lg-5 col-md-7 col-sm-12 text-right" style="padding-top:12px;">
                <span id="last-update">Initializing...</span>
                &nbsp;
                <button class="btn btn-sm btn-primary btn-round" onclick="fetchStatus()">
                    <i class="zmdi zmdi-refresh"></i> Refresh Now
                </button>
            </div>
        </div>
    </div>

    <div class="container-fluid">

        <!-- Global Stats Row -->
        <div class="row clearfix" id="global-stats">
            <div class="col-lg-4 col-md-4 col-sm-12">
                <div class="card info-box-2 bg-blue">
                    <div class="body">
                        <div class="icon"><i class="zmdi zmdi-cast-connected"></i></div>
                        <div class="content">
                            <div class="text">TOTAL READINGS</div>
                            <div class="number" id="stat-readings">--</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-4 col-sm-12">
                <div class="card info-box-2 bg-red">
                    <div class="body">
                        <div class="icon"><i class="zmdi zmdi-alert-triangle"></i></div>
                        <div class="content">
                            <div class="text">ACTIVE ALERTS</div>
                            <div class="number" id="stat-alerts">--</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-4 col-sm-12">
                <div class="card info-box-2 bg-green">
                    <div class="body">
                        <div class="icon"><i class="zmdi zmdi-time"></i></div>
                        <div class="content">
                            <div class="text">LAST DATA RECEIVED</div>
                            <div class="number" id="stat-last" style="font-size:18px;">--</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Devices Grid -->
        <div class="row clearfix">
            <div class="col-lg-12 col-md-12">
                <div class="card">
                    <div class="header">
                        <h2><strong>Registered</strong> IoT Devices</h2>
                    </div>
                    <div class="body" id="devices-container">
                        <div class="text-center p-t-20 p-b-20 text-muted">
                            <i class="zmdi zmdi-refresh zmdi-hc-spin" style="font-size:30px;"></i>
                            <p>Loading devices...</p>
                        </div>
                    </div>
                </div>
            </div>

            </div>
        </div>

        <div class="row clearfix">
            <div class="col-lg-12 col-md-12">

                <!-- Live Activity Log -->
                <div class="card">
                    <div class="header">
                        <h2><strong>Live</strong> Activity Log</h2>
                    </div>
                    <div class="body p-0">
                        <div class="data-flow-log" id="activity-log">
                            <span class="log-ok">[SYSTEM] DigiHealth Device Monitor initialized.</span><br>
                            <span class="log-ok">[SYSTEM] Polling every 10 seconds...</span><br>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="../assets/bundles/libscripts.bundle.js"></script>
<script src="../assets/bundles/vendorscripts.bundle.js"></script>
<script src="../assets/bundles/mainscripts.bundle.js"></script>
<script>
function addLog(msg, type) {
    var log = document.getElementById('activity-log');
    var now = new Date().toLocaleTimeString();
    var cls = type === 'ok' ? 'log-ok' : (type === 'err' ? 'log-err' : 'log-warn');
    log.innerHTML += '<span class="' + cls + '">[' + now + '] ' + msg + '</span><br>';
    log.scrollTop = log.scrollHeight;
}

function timeSince(dateStr) {
    if (!dateStr) return 'Never';
    var diff = Math.floor((new Date() - new Date(dateStr)) / 1000);
    if (diff < 60)   return diff + 's ago';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    return Math.floor(diff / 3600) + 'h ago';
}

function predBadgeClass(pred) {
    if (!pred) return 'secondary';
    var p = pred.toLowerCase();
    if (p.includes('normal')) return 'success';
    if (p.includes('tachy') || p.includes('ventricular')) return 'danger';
    return 'warning';
}

function renderDevices(devices) {
    var c = document.getElementById('devices-container');
    if (!devices || devices.length === 0) {
        c.innerHTML = '<div class="text-center text-muted p-20"><i class="zmdi zmdi-cast" style="font-size:40px;"></i><p class="m-t-10">No devices registered yet.<br>Start <code>ecg_forwarder.py</code> on your Pi — it will auto-register!</p></div>';
        return;
    }

    var html = '<table class="table table-hover m-b-0"><thead><tr>' +
        '<th>Status</th><th>MAC / Device</th><th>Patient</th>' +
        '<th>HR</th><th>AI Result</th><th>Last Seen</th><th></th>' +
        '</tr></thead><tbody>';

    devices.forEach(function(d) {
        var liveDot = d.is_live
            ? '<span class="pulse-live"></span><strong class="text-success">LIVE</strong>'
            : '<span class="pulse-offline"></span><span class="text-muted">Offline</span>';

        var patient = d.patient_name
            ? '<a href="Patient-Profile.php?patientId=' + encodeURIComponent(d.patientID) + '" class="text-info">' + d.patient_name + '</a>'
            : '<span class="text-warning">⚠ Unlinked</span>';

        var hr  = d.heartRate ? d.heartRate + ' <small>BPM</small>' : '--';
        var pred = d.final_prediction
            ? '<span class="badge badge-' + predBadgeClass(d.final_prediction) + ' prediction-badge">' + d.final_prediction + '</span>'
            : '<span class="text-muted">--</span>';

        html += '<tr>' +
            '<td>' + liveDot + '</td>' +
            '<td><code style="font-size:11px;">' + (d.mac_address || 'N/A') + '</code><br>' +
              '<small class="text-muted">' + (d.model || 'Raspberry Pi') + '</small></td>' +
            '<td>' + patient + '</td>' +
            '<td>' + hr + '</td>' +
            '<td>' + pred + '</td>' +
            '<td><small class="text-muted">' + timeSince(d.last_seen) + '</small></td>' +
            '<td>' + (d.patientID ? '<a href="Patient-Profile.php?patientId=' + encodeURIComponent(d.patientID) + '" class="btn btn-xs btn-info btn-round">View</a>' : '') + '</td>' +
            '</tr>';
    });

    html += '</tbody></table>';
    c.innerHTML = html;
}

function fetchStatus() {
    fetch('../api/device_status.php', { credentials: 'same-origin' })
        .then(r => r.json())
        .then(data => {
            if (!data.success) { addLog('API error: ' + data.message, 'err'); return; }

            // Update global stats
            document.getElementById('stat-readings').textContent = data.global.total_readings.toLocaleString();
            document.getElementById('stat-alerts').textContent   = data.global.active_alerts;
            document.getElementById('stat-last').textContent     = timeSince(data.global.last_reading);

            renderDevices(data.devices);

            // Log new live readings
            data.devices.forEach(function(d) {
                if (d.is_live) {
                    addLog('📡 [' + d.mac_address + '] LIVE — ' + (d.final_prediction || 'No prediction') + ' | HR: ' + (d.heartRate || '--') + ' BPM', 'ok');
                }
            });

            document.getElementById('last-update').textContent = 'Last updated: ' + new Date().toLocaleTimeString();
        })
        .catch(function(e) {
            addLog('Network error: ' + e.message, 'err');
        });
}

// Initial load + auto-refresh every 10 seconds
fetchStatus();
setInterval(fetchStatus, 10000);
</script>
</body>
</html>
