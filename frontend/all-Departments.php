<?php 
include("../config/DB_Config.php");
session_start();

if (!isset($_SESSION["user_type"]) || $_SESSION["user_type"] !== "admin") {
    header("Location: dashboard.php");
    exit();
}

try {
    // 1. Fetch Departments
    $stmt = $conn->query("SELECT * FROM departments ORDER BY name ASC");
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!doctype html>
<html class="no-js " lang="en">
<head>
<meta charset="utf-8">
<title>Departments Management - Digihealth</title>
<link rel="stylesheet" href="../assets/plugins/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/color_skins.css">
<!-- Mobile CSS Fix --><link rel="stylesheet" href="../assets/css/mobile.css">
</head>
<body class="theme-cyan">
<div class="overlay"></div>
<nav class="navbar p-l-5 p-r-5"><?php include("top_nav.php"); ?></nav>
<!-- Left Sidebar -->
<aside id="leftsidebar" class="sidebar">
    <?php include("admin_sidebar.php") ?>
</aside>
<section class="content">
    <div class="block-header">
        <div class="row">
            <div class="col-lg-5 col-md-5 col-sm-12"><h2>Departments Hub</h2></div>            
        </div>
    </div>
    <div class="container-fluid">
        <div class="row clearfix">
            <?php foreach($departments as $dept): ?>
            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="card">
                    <div class="body text-center">
                        <div class="p-15">
                            <h5 class="m-b-0"><?php echo htmlspecialchars($dept['name']); ?></h5>
                            <small><?php echo htmlspecialchars($dept['description'] ?? 'No description'); ?></small>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if(empty($departments)) echo "<div class='col-12 text-center text-muted'>No departments found. Use SQL Setup to initialize.</div>"; ?>
        </div>
    </div>
</section>

<!-- Jquery Core Js --> 
<script src="../assets/bundles/libscripts.bundle.js"></script> <!-- Lib Scripts Plugin Js --> 
<script src="../assets/bundles/vendorscripts.bundle.js"></script> <!-- Lib Scripts Plugin Js --> 

<script src="../assets/bundles/mainscripts.bundle.js"></script><!-- Custom Js --> 
</body>
</html>
