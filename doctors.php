<?php 
include("./config/DB_Config.php");
// Start the session
session_start();

// Check if the user is logged in and if the user type is admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    // If not admin, redirect to index.php with a relevant status
    header("Location: index.php?status=access_denied&type=".$_SESSION['user_type']);
    exit(); // Stop further execution after the redirect
}
?>
<!doctype html>
<html class="no-js " lang="en">

<!-- Mirrored from hms.cognisun.net/CUST Digihealth/html/light/doctors.html by HTTrack Website Copier/3.x [XR&CO'2014], Sun, 06 Oct 2024 17:19:29 GMT -->
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
<meta name="description" content="Responsive Bootstrap 4 and web Application ui kit.">

<title>Doctors - CUST Digihealth </title>
<link rel="icon" href="favicon.ico" type="image/x-icon">
<!-- Favicon-->
<link rel="stylesheet" href="./assets/plugins/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="./assets/plugins/bootstrap-select/css/bootstrap-select.css"/>
<!-- Custom Css -->
<link rel="stylesheet" href="./assets/css/main.css">
<link rel="stylesheet" href="./assets/css/color_skins.css">
</head>
<body class="theme-cyan">
<!-- Page Loader -->
<div class="page-loader-wrapper">
    <div class="loader">
        <div class="m-t-30"><img class="zmdi-hc-spin" src="./assets/images/logo.svg" width="48" height="48" alt="CUST Digihealth"></div>
        <p>Please wait...</p>
    </div>
</div>
<!-- Overlay For Sidebars -->
<div class="overlay"></div>
<!-- Top Bar -->
<nav class="navbar p-l-5 p-r-5">
    <?php include("top_nav.php") ?>
</nav>
<!-- Left Sidebar -->
<aside id="leftsidebar" class="sidebar">
     <ul class="nav nav-tabs">
        <li class="nav-item"><a class="nav-link active" data-toggle="tab" style="background: #00cfd1;
    color: white;" href="#dashboard"><img src="./assets/images/logo.svg" width="30" alt="CUST Digihealth"> &nbsp; CUST  DIGIHEALTH </a></li>
        <!--<li class="nav-item"><a class="nav-link text-center" data-toggle="tab" href="#user">Administrator</a></li>-->
    </ul>
    <div class="tab-content">
        <div class="tab-pane stretchRight active" id="dashboard">
            <div class="menu">
                <ul class="list">
                    <li>
                        <div class="user-info">
                            <div class="image"><a href="profile.html" class=" waves-effect waves-block"><img src="./assets/images/admin.png" alt="User"></a></div>
                            <div class="detail">
                                <h4>Super Administrator</h4>
                                <small>Waqas</small>                        
                            </div>
                        </div>
                    </li>	
                    <li class="header">MAIN</li>
                    <li><a href="dashboard.php"><i class="zmdi zmdi-home"></i><span>Dashboard</span></a></li>            
                    
                    <li class="active open"><a href="javascript:void(0);" class="menu-toggle"><i class="zmdi zmdi-account-add"></i><span>Doctors</span> </a>
                        <ul class="ml-menu">
                            <li class="active"><a href="doctors.php">All Doctors</a></li>
                            <li><a href="add-doctor.php">Add Doctor</a></li>   
                            <li><a href="events.php">Doctor Schedule</a></li>
                        </ul>
                    </li>
                    <li><a href="javascript:void(0);" class="menu-toggle"><i class="zmdi zmdi-account-o"></i><span>Patients</span> </a>
                        <ul class="ml-menu">
                            <li><a href="patients.php">All Patients</a></li>
                            <li><a href="add-patient.php">Add Patient</a></li>          
                        </ul>
                    </li>
                     <li><a href="javascript:void(0);" class="menu-toggle"><i class="zmdi zmdi-swap-alt"></i><span>ECG Devices</span> </a>
                        <ul class="ml-menu">
                            <li><a href="devices.php">All Devices</a></li>
                            <li><a href="add-device.php">Add Device</a></li>         
                        </ul>
                    </li>
                    <li><a href="javascript:void(0);" class="menu-toggle"><i class="zmdi zmdi-label-alt"></i><span>Departments</span> </a>
                        <ul class="ml-menu">
                            <li><a href="add-departments.html">Add</a></li>
                            <li><a href="all-Departments.html">All Departments</a></li>
                            <li><a href="javascript:void(0);">Cardiology</a></li>
                            <li><a href="javascript:void(0);">Pulmonology</a></li>
                            <li><a href="javascript:void(0);">Gynecology</a></li>
                            <li><a href="javascript:void(0);">Neurology</a></li>
                            <li><a href="javascript:void(0);">Urology</a></li>
                            <li><a href="javascript:void(0);">Gastrology</a></li>
                            <li><a href="javascript:void(0);">Pediatrician</a></li>
                            <li><a href="javascript:void(0);">Laboratory</a></li>
                        </ul>
                    </li>
                    
                </ul>
            </div>
        </div>
        
    </div>    
</aside>
<!-- Right Sidebar -->

<?php include("rightsidebar.php") ?>
<!-- Chat-launcher -->

<section class="content">
    <div class="block-header">
        <div class="row">
            <div class="col-lg-5 col-md-5 col-sm-12">
                <h2>All Doctors
                <small>Welcome to CUST Digihealth</small>
                </h2>
            </div>            
            <div class="col-lg-7 col-md-7 col-sm-12 text-right">
                <button class="btn btn-white btn-icon btn-round d-none d-md-inline-block float-right m-l-10" type="button">
                    <i class="zmdi zmdi-plus"></i>
                </button>
                <ul class="breadcrumb float-md-right">
                    <li class="breadcrumb-item"><a href="index-2.html"><i class="zmdi zmdi-home"></i> Home</a></li>
                    <li class="breadcrumb-item"><a href="javascript:void(0);">Doctors</a></li>
                    <li class="breadcrumb-item active">All Doctors</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row clearfix">
            <div class="col-lg-12">
                <div class="card">
                    <div class="body">
                        <ul class="nav nav-tabs padding-0">
                            <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#Permanent">Permanent</a></li>
                            <li class="nav-item"></li>
                        </ul>
                    </div>
                </div>
                <div class="tab-content m-t-10">
                    <div class="tab-pane active" id="Permanent">
                        <div class="row clearfix">
						<?php 
							// Fetch all doctors from the database
							$sql = "SELECT u.id, u.username, u.email, p.full_name, p.specialization, p.phone_number, p.profile_picture, p.description, p.website_url
									FROM users u
									JOIN doctorProfile p ON u.id = p.user_id
									WHERE u.type = 'doctor' and u.isActive=1";
							$result = $conn->query($sql);

							// Check if there are results
							if ($result->num_rows > 0) {
								 while ($row = $result->fetch_assoc()) {
									$color = "xl-parpl";
									if($row['specialization'] == "Cardiologist"){
										$color = "xl-blue";
									}
									else if($row['specialization'] == "Urologist"){
										$color = "xl-seagreen";
									}
									else if($row['specialization'] == "Gynecologist"){
										$color = "xl-pink";
									}
									else if($row['specialization'] == "Gastroenterologist"){
										$color = "xl-khaki";
									}
							?>
								
							
						
                            <div class="col-lg-3 col-md-4 col-sm-6">
                                <div class="card <?php echo $color; ?> member-card doctor">
                                    <div class="body">
                                        <div class="member-thumb">
                                            <img src="<?php echo htmlspecialchars($row['profile_picture']) ?>" class="img-fluid" alt="profile-image">                               
                                        </div>
                                        <div class="detail">
                                            <h4 class="m-b-0"><?php echo htmlspecialchars($row['full_name'])  ?></h4>
                                            <p class="text-muted"><?php echo htmlspecialchars($row['specialization'])  ?></p>
                                            
                                            <p class="text-muted"><?php echo htmlspecialchars($row['phone_number'])  ?></p>      
                                            <p class="text-muted" style="min-height:100px;"><?php echo htmlspecialchars($row['description'])  ?></p>                           
                                            <a href="<?php echo htmlspecialchars($row['website_url'])  ?>"  class="btn btn-default btn-round btn-simple" target="_blank">View Profile</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
							
								 <?php } } ?>
                            
                        </div> 
                    </div>
                    
                </div>
            </div>
        </div> 
    </div>
</section>
<!-- Jquery Core Js --> 
<script src="./assets/bundles/libscripts.bundle.js"></script> <!-- Bootstrap JS and jQuery v3.2.1 -->
<script src="./assets/bundles/vendorscripts.bundle.js"></script> <!-- slimscroll, waves Scripts Plugin Js --> 

<script src="./assets/bundles/mainscripts.bundle.js"></script><!-- Custom Js --> 
</body>

<!-- Mirrored from hms.cognisun.net/CUST Digihealth/html/light/doctors.html by HTTrack Website Copier/3.x [XR&CO'2014], Sun, 06 Oct 2024 17:19:29 GMT -->
</html>