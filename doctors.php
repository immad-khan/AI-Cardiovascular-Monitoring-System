<?php 
include("./config/DB_Config.php");
session_start();

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: index.php?status=access_denied&type=".$_SESSION['user_type']);
    exit();
}
?>
<!doctype html>
<html class="no-js " lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
<title>Doctors - CUST Digihealth </title>
<link rel="icon" href="favicon.ico" type="image/x-icon">
<link rel="stylesheet" href="./assets/plugins/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="./assets/css/main.css">
<link rel="stylesheet" href="./assets/css/color_skins.css">
</head>
<body class="theme-cyan">
<div class="page-loader-wrapper">
    <div class="loader">
        <div class="m-t-30"><img class="zmdi-hc-spin" src="./assets/images/logo.svg" width="48" height="48" alt="CUST Digihealth"></div>
        <p>Please wait...</p>
    </div>
</div>
<div class="overlay"></div>
<nav class="navbar p-l-5 p-r-5">
    <?php include("top_nav.php") ?>
</nav>
<aside id="leftsidebar" class="sidebar">
    <div class="menu">
        <ul class="list">
            <li><a href="dashboard.php"><i class="zmdi zmdi-home"></i><span>Dashboard</span></a></li>            
            <li class="active open"><a href="doctors.php"><i class="zmdi zmdi-account-add"></i><span>Doctors</span> </a></li>
            <li><a href="patients.php"><i class="zmdi zmdi-account-o"></i><span>Patients</span> </a></li>
            <li><a href="devices.php"><i class="zmdi zmdi-swap-alt"></i><span>ECG Devices</span> </a></li>
        </ul>
    </div>
</aside>
<?php include("rightsidebar.php") ?>

<section class="content">
    <div class="block-header">
        <div class="row">
            <div class="col-lg-5 col-md-5 col-sm-12">
                <h2>All Doctors <small>Welcome to CUST Digihealth</small></h2>
            </div>            
            <div class="col-lg-7 col-md-7 col-sm-12 text-right">
                <a href="add-doctor.php" class="btn btn-primary btn-round">Add Doctor</a>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row clearfix">
            <?php 
                try {
                    $sql = "SELECT u.\"userID\", u.username, u.email, p.full_name, p.specialization, p.phone_number, p.profile_picture, p.description, p.website_url
                            FROM users u
                            JOIN \"doctorProfile\" p ON u.\"userID\" = p.\"userID\"
                            WHERE u.role = 'doctor' AND u.\"isActive\" = TRUE";
                    $stmt = $conn->query($sql);

                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $color = "xl-parpl";
                        if($row['specialization'] == "Cardiologist") $color = "xl-blue";
                        echo '<div class="col-lg-3 col-md-4 col-sm-6">
                            <div class="card ' . $color . ' member-card doctor">
                                <div class="body">
                                    <div class="member-thumb">
                                        <img src="' . htmlspecialchars($row['profile_picture']) . '" class="img-fluid" alt="profile">                               
                                    </div>
                                    <div class="detail">
                                        <h4 class="m-b-0">' . htmlspecialchars($row['full_name']) . '</h4>
                                        <p class="text-muted">' . htmlspecialchars($row['specialization']) . '</p>
                                        <p class="text-muted">' . htmlspecialchars($row['phone_number']) . '</p>      
                                        <p class="text-muted" style="min-height:80px;">' . htmlspecialchars(substr($row['description'], 0, 100)) . '...</p>                           
                                        <a href="' . htmlspecialchars($row['website_url']) . '" class="btn btn-default btn-round btn-simple" target="_blank">View Profile</a>
                                    </div>
                                </div>
                            </div>
                        </div>';
                    }
                } catch (PDOException $e) {
                    echo '<div class="col-12 text-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
            ?>
        </div> 
    </div>
</section>
<script src="./assets/bundles/libscripts.bundle.js"></script>
<script src="./assets/bundles/vendorscripts.bundle.js"></script>
<script src="./assets/bundles/mainscripts.bundle.js"></script>
</body>
</html>
