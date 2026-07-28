<?php 
// Start the session at the very beginning
session_start();
include("../config/DB_Config.php");

// Check if the user is logged in and if the user type is admin or doctor
if (!isset($_SESSION["user_type"]) || ($_SESSION["user_type"] !== "admin" && $_SESSION["user_type"] !== "doctor")) {
    header("Location: index.php?status=Access is denied&type=error");
    exit();
}

// If user is a doctor, check if profile exists
if ($_SESSION["user_type"] == "doctor") {
    $stmt = $conn->prepare('SELECT "doctorID" FROM "doctorProfile" WHERE "userID" = ?');
    $stmt->execute([$_SESSION["user_id"]]);
    if ($stmt->fetch()) {
        header("Location: patients.php?status=Doctor login successful");
        exit();
    }
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include_once("../backend/doctor_logic.php");
    $result = handleDoctorRegistration($conn, $_POST, $_FILES, $_SESSION);
    
    $status = urlencode($result["status"]);
    $type = urlencode($result["type"]);
    $redirect = $result["redirect"];
    
    header("Location: $redirect?status=$status&type=$type");
    exit();
}
?>

<!doctype html>
<html class="no-js " lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
<meta name="description" content="Responsive Bootstrap 4 and web Application ui kit.">

<title>Add Doctor - CUST Digihealth</title>
<link rel="icon" href="favicon.ico" type="image/x-icon">
<link rel="stylesheet" href="../assets/plugins/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../assets/plugins/dropzone/dropzone.css">
<link rel="stylesheet" href="../assets/plugins/bootstrap-select/css/bootstrap-select.css"/>
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/color_skins.css">
<!-- Mobile CSS Fix --><link rel="stylesheet" href="../assets/css/mobile.css">
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
<nav class="navbar p-l-5 p-r-5">
    <?php include("top_nav.php") ?>
</nav>

<aside id="leftsidebar" class="sidebar">
    <?php include("admin_sidebar.php") ?>
</aside>

<section class="content">
    <div class="block-header">
        <div class="row">
            <div class="col-lg-7 col-md-5 col-sm-12">
                <h2>Create Doctor Profile
                <small class="text-muted">Welcome to CUST Digihealth</small>   
                </h2>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row clearfix">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <form method="post" action="" enctype="multipart/form-data">
                    <div class="card">
                        <div class="header">
                            <h2><strong>Doctor's</strong> Account Information <small>Login Credentials</small> </h2>
                        </div>
                        <div class="body">
                            <div class="row clearfix">
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <input type="text" class="form-control" name="username" placeholder="Username" required>
                                    </div>  
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <input type="email" class="form-control" name="email" placeholder="Email" required>
                                    </div>  
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <input type="password" class="form-control" name="password" placeholder="Password" required>
                                    </div>  
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <input type="password" class="form-control" name="confirm_password" placeholder="Confirm Password" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="header">
                            <h2><strong>Doctor</strong> Profile</h2>
                        </div>
                        <div class="body">
                            <div class="row clearfix">
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <input type="text" class="form-control" name="full_name" placeholder="Full Name" required>
                                    </div>
                                </div>  
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <input type="text" class="form-control" name="specialization" placeholder="Specialization" required>
                                    </div>
                                </div>  
                            </div>
                            <div class="row clearfix">
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <input type="text" name="phone_number" class="form-control" placeholder="Phone (e.g. 03xx-xxxxxxx)" required>
                                    </div>
                                </div>  
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <input type="file" class="form-control" name="profile_picture" accept="image/*">
                                    </div>
                                </div>  
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <input type="text" class="form-control" name="website_url" placeholder="Website URL">
                                    </div>
                                </div>  
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <textarea rows="4" class="form-control no-resize" name="description" placeholder="Bio / Description..."></textarea>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <button type="submit" class="btn btn-primary btn-round">Submit</button>
                                    <a href="dashboard.php" class="btn btn-default btn-round btn-simple">Cancel</a>
                                </div>  
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Jquery Core Js --> 
<script src="../assets/bundles/libscripts.bundle.js"></script>
<script src="../assets/bundles/vendorscripts.bundle.js"></script>
<script src="../assets/bundles/mainscripts.bundle.js"></script>
<script>
    // Simple script to hide loader after page components are handled by mainscripts
    $(window).on('load', function() {
        $('.page-loader-wrapper').fadeOut();
    });
    // Fallback if mainscripts doesn't hide it
    setTimeout(function() {
        $('.page-loader-wrapper').fadeOut();
    }, 2000);
</script>
</body>
</html>
