<?php
session_start();
include("../config/DB_Config.php");

// Access Control: ONLY Doctors can access this dashboard
if (!isset($_SESSION["user_type"]) || $_SESSION["user_type"] !== "doctor") {
    header("Location: index.php?status=Unauthorized Access&type=error");
    exit();
}

$userID = $_SESSION["user_id"];

try {
    // 1. Fetch Doctor's Patients Count
    $stmt = $conn->prepare("SELECT COUNT(*) FROM patients WHERE \"assignedDoctorID\" = ? AND \"isActive\" = TRUE");
    $stmt->execute([$userID]);
    $my_patients_count = $stmt->fetchColumn();

    // 2. Fetch Active Alerts for their Patients
    $alert_stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM \"CRITICAL_ALERT\" ca
        JOIN monitoring_devices md ON ca.\"deviceID\" = md.\"deviceID\"
        JOIN patients p ON md.\"patientID\" = p.\"patientID\"
        WHERE p.\"assignedDoctorID\" = ? AND ca.status = 'Active'
    ");
    $alert_stmt->execute([$userID]);
    $active_alerts_count = $alert_stmt->fetchColumn();

    // 3. Fetch Device Status for their Patients
    $device_stmt = $conn->prepare("
        SELECT 
            COUNT(*) FILTER (WHERE status = 'Online') as online,
            COUNT(*) FILTER (WHERE status = 'Offline') as offline
        FROM monitoring_devices 
        WHERE \"patientID\" IN (SELECT \"patientID\" FROM patients WHERE \"assignedDoctorID\" = ?)
    ");
    $device_stmt->execute([$userID]);
    $device_stats = $device_stmt->fetch(PDO::FETCH_ASSOC);

    // 4. Fetch Recent Patients
    $recent_stmt = $conn->prepare("
        SELECT p.*, md.model, md.mac_address 
        FROM patients p 
        LEFT JOIN monitoring_devices md ON p.\"patientID\" = md.\"patientID\"
        WHERE p.\"assignedDoctorID\" = ? AND p.\"isActive\" = TRUE
        ORDER BY p.date DESC LIMIT 5
    ");
    $recent_stmt->execute([$userID]);
    $recent_patients = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Fetch Pending Tasks
    $tasks_stmt = $conn->prepare("
        SELECT dt.*, p.name as patient_name
        FROM doctor_tasks dt
        JOIN patients p ON dt.\"patientID\" = p.\"patientID\"
        WHERE dt.\"doctorID\" = ? AND dt.status = 'Pending'
        ORDER BY dt.created_at DESC LIMIT 5
    ");
    $tasks_stmt->execute([$userID]);
    $pending_tasks = $tasks_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Silently handle for now or show error
    $error_msg = "Error: " . $e->getMessage();
}
?>
<!doctype html>
<html class="no-js " lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
<title>Doctor Dashboard - CUST Digihealth</title>
<link rel="icon" href="favicon.ico" type="image/x-icon">
<link rel="stylesheet" href="../assets/plugins/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/color_skins.css">
<!-- Mobile CSS Fix --><link rel="stylesheet" href="../assets/css/mobile.css">
</head>
<body class="theme-cyan">
<div class="page-loader-wrapper">
    <div class="loader">
        <div class="m-t-30"><img class="zmdi-hc-spin" src="../assets/images/logo.svg" width="48" height="48" alt="CUST Digihealth"></div>
        <p>Please wait...</p>
    </div>
</div>
<div class="overlay"></div>
<nav class="navbar p-l-5 p-r-5">
    <?php include("top_nav.php") ?>
</nav>

<aside id="leftsidebar" class="sidebar">
    <?php include("doctor_sidebar.php") ?>
</aside>

<section class="content home">
    <div class="block-header">
        <div class="row">
            <div class="col-lg-7 col-md-5 col-sm-12">
                <h2>Clinical Command Center
                <small class="text-muted">Welcome, Dr. <?php echo strtoupper($_SESSION['username']); ?></small>
                </h2>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row clearfix">
            <div class="col-lg-3 col-md-6 col-sm-12">
                <div class="card info-box-2 bg-blue">
                    <div class="body">
                        <div class="icon"><i class="zmdi zmdi-account-o"></i></div>
                        <div class="content">
                            <div class="text">MY PATIENTS</div>
                            <div class="number"><?php echo $my_patients_count; ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-12">
                <div class="card info-box-2 bg-red">
                    <div class="body">
                        <div class="icon"><i class="zmdi zmdi-alert-circle"></i></div>
                        <div class="content">
                            <div class="text">ACTIVE ALERTS</div>
                            <div class="number"><?php echo $active_alerts_count; ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-12">
                <div class="card info-box-2 bg-green">
                    <div class="body">
                        <div class="icon"><i class="zmdi zmdi-cast-connected"></i></div>
                        <div class="content">
                            <div class="text">DEVICES ONLINE</div>
                            <div class="number"><?php echo $device_stats['online'] ?? 0; ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-12">
                <div class="card info-box-2 bg-grey">
                    <div class="body">
                        <div class="icon"><i class="zmdi zmdi-power-off"></i></div>
                        <div class="content">
                            <div class="text">DEVICES OFFLINE</div>
                            <div class="number"><?php echo $device_stats['offline'] ?? 0; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row clearfix">
            <!-- Pending Tasks Section -->
            <div class="col-lg-6 col-md-12">
                <div class="card">
                    <div class="header">
                        <h2><strong>Pending</strong> Tasks <small>Requires your attention</small></h2>
                    </div>
                    <div class="body table-responsive">
                        <table class="table m-b-0 table-hover">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Task Type</th>
                                    <th>Time</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($pending_tasks as $task): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($task['patient_name']); ?></strong><br><small class="text-muted"><?php echo $task['patientID']; ?></small></td>
                                        <td><span class="badge badge-warning"><?php echo htmlspecialchars($task['task_type']); ?></span></td>
                                        <td><?php echo date('H:i, M d', strtotime($task['created_at'])); ?></td>
                                        <td>
                                            <a href="Patient-Profile.php?patientId=<?php echo urlencode($task['patientID']); ?>&taskId=<?php echo $task['taskID']; ?>" class="btn btn-sm btn-primary btn-round">Review</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if(empty($pending_tasks)) echo "<tr><td colspan='4' class='text-center text-success'>No pending tasks. Great job!</td></tr>"; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Patients Section -->
            <div class="col-lg-6 col-md-12">
                <div class="card">
                    <div class="header">
                        <h2><strong>Recent</strong> My Patients <small>Latest admissions</small></h2>
                        <ul class="header-dropdown">
                            <li><a href="add-patient.php" class="btn btn-primary btn-sm btn-round text-white pt-2 pb-2">Add Patient</a></li>
                        </ul>
                    </div>
                    <div class="body table-responsive">
                        <table class="table m-b-0 table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Device</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recent_patients as $p): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($p['name']); ?></strong><br><small class="text-muted"><?php echo $p['patientID']; ?></small></td>
                                        <td><span class="text-muted"><?php echo $p['mac_address'] ?? 'Unassigned'; ?></span></td>
                                        <td>
                                            <a href="Patient-Profile.php?patientId=<?php echo urlencode($p['patientID']); ?>" class="btn btn-sm btn-info btn-round">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if(empty($recent_patients)) echo "<tr><td colspan='3' class='text-center'>No patients assigned yet.</td></tr>"; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Jquery Core Js --> 
<script src="../assets/bundles/libscripts.bundle.js"></script> 
<script src="../assets/bundles/vendorscripts.bundle.js"></script> 

<script src="../assets/bundles/mainscripts.bundle.js"></script>
</body>
</html>