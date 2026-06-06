<?php 
include("../config/DB_Config.php");
session_start();

if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'tech-admin')) {
    header("Location: index.php?status=denied");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mac = $_POST['mac_address'];
    $model = $_POST['model'];
    try {
        $stmt = $conn->prepare("INSERT INTO monitoring_devices (mac_address, model, status) VALUES (?, ?, 'Offline')");
        $stmt->execute([$mac, $model]);
        header("Location: devices.php?status=DeviceAdded");
        exit();
    } catch(PDOException $e) {
        $error = $e->getMessage();
    }
}
?>
<!doctype html>
<html class="no-js " lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
<title>CUST-Digihealth - Add Device</title>
<link rel="icon" href="favicon.ico" type="image/x-icon">
<link rel="stylesheet" href="../assets/plugins/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/color_skins.css">
<style>
    .modern-input {
        border: 2px solid #f4f4f4 !important;
        border-radius: 8px !important;
        padding: 12px 15px !important;
        background: #fcfcfc !important;
        transition: all 0.3s ease !important;
        height: auto !important;
    }
    .modern-input:focus {
        background: #fff !important;
        border-color: #00cfd1 !important;
        box-shadow: 0 4px 12px rgba(0,207,209,0.1) !important;
    }
    .btn-assign {
        background: #00cfd1;
        color: #fff;
        border: none;
        padding: 12px 25px;
        border-radius: 8px;
        font-weight: 600;
        box-shadow: 0 4px 15px rgba(0,207,209,0.3);
    }
</style>
</head>
<body class="theme-cyan">
<!-- Page Loader -->
<div class="page-loader-wrapper">
    <div class="loader">
        <div class="m-t-30"><img class="zmdi-hc-spin" src="../assets/images/logo.svg" width="48" height="48" alt="Oreo"></div>
        <p>Please wait...</p>        
    </div>
</div>
<!-- Overlay For Sidebars -->
<div class="overlay"></div>

<!-- Top Bar -->
<?php include("top_nav.php") ?>

<!-- Left Sidebar -->
<aside id="leftsidebar" class="sidebar">
    <?php include("admin_sidebar.php") ?>
</aside>

<section class="content">
    <div class="block-header">
        <div class="row">
            <div class="col-lg-7 col-md-6 col-sm-12">
                <h2>Add New Device
                <small>Register monitoring hardware for ECG tracking</small>
                </h2>
            </div>
            <div class="col-lg-5 col-md-6 col-sm-12">
                <ul class="breadcrumb float-md-right">
                    <li class="breadcrumb-item"><a href="dashboard.php"><i class="zmdi zmdi-home"></i> Home</a></li>
                    <li class="breadcrumb-item"><a href="devices.php">Devices</a></li>
                    <li class="breadcrumb-item active">Add Device</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row clearfix">
            <div class="col-lg-6 col-md-12">
                <div class="card">
                    <div class="header">
                        <h2>Device <strong>Information</strong></h2>
                    </div>
                    <div class="body">
                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <form method="POST">
                            <div class="form-group">
                                <label style="font-weight:600; color:#999; text-transform:uppercase; font-size:0.75rem;">MAC Address</label>
                                <input type="text" name="mac_address" class="form-control modern-input" placeholder="e.g. AA:BB:CC:DD:EE:FF" required>
                            </div>
                            <div class="form-group">
                                <label style="font-weight:600; color:#999; text-transform:uppercase; font-size:0.75rem;">Device Model</label>
                                <select name="model" class="form-control modern-input" required>
                                    <option value="Raspberry Pi 4">Raspberry Pi 4 (IoT Gateway)</option>
                                    <option value="ESP32-ECG">ESP32 Wearable</option>
                                    <option value="Huawei GT2-Pro">Huawei GT2-Pro</option>
                                </select>
                            </div>
                            <div class="m-t-20">
                                <button type="submit" class="btn btn-assign">Register Device</button>
                                <a href="devices.php" class="btn btn-simple btn-round">Cancel</a>
                            </div>
                        </form>
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
</html>
