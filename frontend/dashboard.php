<?php
session_start();
include("../config/DB_Config.php");

if (!isset($_SESSION["user_type"]) || $_SESSION["user_type"] !== "admin") {
    header("Location: index.php?status=access_denied&type=error");
    exit();
}

try {
    $doctor_count = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'doctor'")->fetchColumn();
    $patient_count = $conn->query("SELECT COUNT(*) FROM patients WHERE \"isActive\" = TRUE")->fetchColumn();
    $device_count = $conn->query("SELECT COUNT(*) FROM monitoring_devices")->fetchColumn();
    
    // New Statistics from Use Case Diagrams
    $nurse_count = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'nurse'")->fetchColumn();
    $dept_count = 0; // Fallback until departments table is migrated
    try { $dept_count = $conn->query("SELECT COUNT(*) FROM departments")->fetchColumn(); } catch(Exception $e) {}
    
    $new_patients = $conn->query("SELECT * FROM patients ORDER BY date DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    $alerts = $conn->query("SELECT a.*, md.model FROM \"CRITICAL_ALERT\" a JOIN monitoring_devices md ON a.\"deviceID\" = md.\"deviceID\" ORDER BY a.timestamp DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Data Fetch Error: " . $e->getMessage());
}
?>
<!doctype html>
<html class="no-js " lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
<title>Admin Dashboard - Digihealth</title>
<link rel="icon" href="favicon.ico" type="image/x-icon">
<link rel="stylesheet" href="../assets/plugins/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/color_skins.css">
</head>
<body class="theme-cyan">
<div class="overlay"></div>
<nav class="navbar p-l-5 p-r-5"><?php include("top_nav.php") ?></nav>

<aside id="leftsidebar" class="sidebar">
    <?php include("admin_sidebar.php") ?>
</aside>

<section class="content home">
    <div class="block-header">
        <div class="row">
            <div class="col-lg-5 col-md-5 col-sm-12"><h2>Admin Command Center</h2></div>            
        </div>
    </div>
    <div class="container-fluid">
        <div class="row clearfix">
            <div class="col-lg-2 col-md-4 col-sm-6"><div class="card body"><h5 class="m-b-0"><?php echo $doctor_count ?></h5><p>Doctors</p></div></div>
            <div class="col-lg-2 col-md-4 col-sm-6"><div class="card body"><h5 class="m-b-0"><?php echo $patient_count ?></h5><p>Patients</p></div></div>
            <div class="col-lg-2 col-md-4 col-sm-6"><div class="card body"><h5 class="m-b-0"><?php echo $device_count ?></h5><p>IoT Hubs</p></div></div>
            <div class="col-lg-2 col-md-4 col-sm-6"><div class="card body"><h5 class="m-b-0"><?php echo $nurse_count ?></h5><p>Nursing Staff</p></div></div>
            <div class="col-lg-2 col-md-4 col-sm-6"><div class="card body"><h5 class="m-b-0"><?php echo $dept_count ?></h5><p>Departments</p></div></div>
        </div>
        <div class="row clearfix">
            <div class="col-lg-4 col-md-12">
                <div class="card"><div class="header"><h2>Real-time Alerts</h2></div><div class="body">
                    <ul class="list-unstyled">
                        <?php foreach($alerts as $alert): ?>
                            <li><i class="zmdi zmdi-notifications text-danger"></i> <small><?php echo date('H:i', strtotime($alert['timestamp'])) ?></small> - <strong><?php echo $alert['model'] ?></strong>: <?php echo $alert['message'] ?></li>
                        <?php endforeach; ?>
                        <?php if(empty($alerts)) echo "<li>No critical alerts.</li>"; ?>
                    </ul>
                </div></div>
            </div>
            <div class="col-lg-8 col-md-12">
                <div class="card"><div class="header"><h2>Recent Admissions</h2></div><div class="body table-responsive">
                    <table class="table">
                        <thead><tr><th>ID</th><th>Name</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php foreach($new_patients as $p): ?>
                                <tr><td><?php echo $p['patientID'] ?></td><td><?php echo $p['name'] ?></td><td><a href="Patient-Profile.php?patientId=<?php echo $p['patientID'] ?>" class="btn btn-sm btn-info">Analyze Profile</a></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div></div>
            </div>
        </div>
    </div>
</section>

<!-- Jquery Core Js --> 
<script src="../assets/bundles/libscripts.bundle.js"></script> <!-- Lib Scripts Plugin Js --> 
<script src="../assets/bundles/vendorscripts.bundle.js"></script> <!-- Lib Scripts Plugin Js --> 

<script src="../assets/bundles/mainscripts.bundle.js"></script><!-- Custom Js --> 
</body>
</html>
