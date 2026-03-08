<?php 
include("../config/DB_Config.php");
session_start();

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: index.php?status=access_denied");
    exit();
}

try {
    $doctor_count = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'doctor'")->fetchColumn();
    $patient_count = $conn->query("SELECT COUNT(*) FROM patients WHERE \"isActive\" = TRUE")->fetchColumn();
    $device_count = $conn->query("SELECT COUNT(*) FROM monitoring_devices")->fetchColumn();
    
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
<title>Dashboard - CUST Digihealth</title>
<link rel="icon" href="favicon.ico" type="image/x-icon">
<link rel="stylesheet" href="../assets/plugins/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/color_skins.css">
</head>
<body class="theme-cyan">
<div class="overlay"></div>
<nav class="navbar p-l-5 p-r-5"><?php include("top_nav.php") ?></nav>
<aside id="leftsidebar" class="sidebar">
    <div class="menu">
        <ul class="list">
            <li class="active open"><a href="dashboard.php"><i class="zmdi zmdi-home"></i><span>Dashboard</span></a></li>            
            <li><a href="doctors.php"><i class="zmdi zmdi-account-add"></i><span>Doctors</span> </a></li>
            <li><a href="patients.php"><i class="zmdi zmdi-account-o"></i><span>Patients</span> </a></li>
            <li><a href="devices.php"><i class="zmdi zmdi-swap-alt"></i><span>ECG Devices</span> </a></li>
        </ul>
    </div>
</aside>

<section class="content home">
    <div class="block-header">
        <div class="row">
            <div class="col-lg-5 col-md-5 col-sm-12"><h2>Dashboard</h2></div>            
        </div>
    </div>
    <div class="container-fluid">
        <div class="row clearfix">
            <div class="col-lg-4 col-md-6"><div class="card body"><h3 class="m-b-0"><?php echo $doctor_count ?></h3><p>Doctors</p></div></div>
            <div class="col-lg-4 col-md-6"><div class="card body"><h3 class="m-b-0"><?php echo $patient_count ?></h3><p>Patients</p></div></div>
            <div class="col-lg-4 col-md-6"><div class="card body"><h3 class="m-b-0"><?php echo $device_count ?></h3><p>ECG Devices</p></div></div>
        </div>
        <div class="row clearfix">
            <div class="col-lg-4 col-md-12">
                <div class="card"><div class="header"><h2>Alerts</h2></div><div class="body">
                    <ul class="list-unstyled">
                        <?php foreach($alerts as $alert): ?>
                            <li><i class="zmdi zmdi-notifications text-danger"></i> <small><?php echo date('H:i', strtotime($alert['timestamp'])) ?></small> - <strong><?php echo $alert['model'] ?></strong>: <?php echo $alert['message'] ?></li>
                        <?php endforeach; ?>
                        <?php if(empty($alerts)) echo "<li>No critical alerts.</li>"; ?>
                    </ul>
                </div></div>
            </div>
            <div class="col-lg-8 col-md-12">
                <div class="card"><div class="header"><h2>New Patients</h2></div><div class="body table-responsive">
                    <table class="table">
                        <thead><tr><th>ID</th><th>Name</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php foreach($new_patients as $p): ?>
                                <tr><td><?php echo $p['patientID'] ?></td><td><?php echo $p['name'] ?></td><td><a href="Patient-Profile.php?patientId=<?php echo $p['patientID'] ?>" class="btn btn-sm btn-info">View</a></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div></div>
            </div>
        </div>
    </div>
</section>
</body>
</html>
