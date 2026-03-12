<?php
session_start();
include("../config/DB_Config.php");

// Access Control: ONLY Doctors can access this dashboard
if (!isset($_SESSION["user_type"]) || $_SESSION["user_type"] !== "doctor") {
    header("Location: index.php?status=Unauthorized Access&type=error");
    exit();
}

$userID = $_SESSION["user_id"];
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

<section class="content">
    <div class="block-header">
        <div class="row">
            <div class="col-lg-7 col-md-5 col-sm-12">
                <h2>Doctor Dashboard
                <small class="text-muted">Welcome to CUST Digihealth</small>
                </h2>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row clearfix">
            <div class="col-lg-12">
                <div class="card">
                    <div class="body">
                        <p>Welcome to your dedicated dashboard. Content will be added here step by step.</p>
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