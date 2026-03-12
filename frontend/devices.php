<?php 
include("../config/DB_Config.php");
// Start the session
session_start();
// Check if the user is logged in and if the user type is admin
if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'tech-admin')) {
    // If not admin, redirect to index.php with a relevant status
    header("Location: index.php?status=Access is denied&type=error");
    exit(); // Stop further execution after the redirect
}
?>
<!doctype html>
<html class="no-js " lang="en">

<!-- Mirrored from hms.cognisun.net/CUST Digihealth/html/light/patients.html by HTTrack Website Copier/3.x [XR&CO'2014], Sun, 06 Oct 2024 17:19:29 GMT -->
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
<meta name="description" content="Responsive Bootstrap 4 and web Application ui kit.">

<title>Patients - CUST Digihealth </title>
<link rel="icon" href="favicon.ico" type="image/x-icon">
<!-- Favicon-->
<link rel="stylesheet" href="../assets/plugins/bootstrap/css/bootstrap.min.css">
<!-- Custom Css -->
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/color_skins.css">
</head>
<body class="theme-cyan">
<!-- Page Loader -->
<div class="page-loader-wrapper">
    <div class="loader">
        <div class="m-t-30"><img class="zmdi-hc-spin" src="../assets/images/logo.svg" width="48" height="48" alt="CUST Digihealth"></div>
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
    color: white;" href="#dashboard"><img src="../assets/images/logo.svg" width="30" alt="CUST Digihealth"> &nbsp; CUST  DIGIHEALTH </a></li>
        <!--<li class="nav-item"><a class="nav-link text-center" data-toggle="tab" href="#user">Administrator</a></li>-->
    </ul>
    <div class="tab-content">
        <div class="tab-pane stretchRight active" id="dashboard">
            <div class="menu">
                <ul class="list">
                    <li>
                        <div class="user-info">
                            <div class="image"><a href="profile.html" class=" waves-effect waves-block"><img src="../assets/images/admin.png" alt="User"></a></div>
                            <div class="detail">
                                <h4>Super Administrator</h4>
                                <small>Waqas</small>                        
                            </div>
                        </div>
                    </li>	
                    <li class="header">MAIN</li>
                    <li><a href="dashboard.php"><i class="zmdi zmdi-home"></i><span>Dashboard</span></a></li>            
                    <li><a href="javascript:void(0);" class="menu-toggle"><i class="zmdi zmdi-account-add"></i><span>Doctors</span> </a>
                        <ul class="ml-menu">
                            <li><a href="doctors.php">All Doctors</a></li>
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
                   <li class="active open"><a href="javascript:void(0);" class="menu-toggle"><i class="zmdi zmdi-swap-alt"></i><span>ECG Devices</span> </a>
                        <ul class="ml-menu">
                            <li class="active"><a href="devices.php">All Devices</a></li>
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

<section class="content">
    <div class="block-header">
        <div class="row">
            <div class="col-lg-7 col-md-5 col-sm-12">
                <h2>All ECG Devices
                <small class="text-muted">Welcome to CUST Digihealth</small>
                </h2>
            </div>
            <div class="col-lg-5 col-md-7 col-sm-12">
                <button class="btn btn-primary btn-icon btn-round d-none d-md-inline-block float-right m-l-10" type="button">
                    <i class="zmdi zmdi-plus"></i>
                </button>
                <ul class="breadcrumb float-md-right">
                    <li class="breadcrumb-item"><a href="dashboard.php"><i class="zmdi zmdi-home"></i> Home</a></li>
                    <li class="breadcrumb-item"><a href="javascript:void(0);">Patients</a></li>
                    <li class="breadcrumb-item active">All ECG Devices</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row clearfix">
            <div class="col-md-12">
                <div class="card patients-list">
                    <div class="header">
                        <h2><strong>ECG Devices</strong> List</h2>
                        <ul class="header-dropdown">
                            <li class="dropdown"> <a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"> <i class="zmdi zmdi-more"></i> </a>
                                <ul class="dropdown-menu dropdown-menu-right slideUp">
                                    <li><a href="add-device.php">Add New ECG Device</a></li>
                                </ul>
                            </li>
                            <li class="remove">
                                <a role="button" class="boxs-close"><i class="zmdi zmdi-close"></i></a>
                            </li>
                        </ul>
                    </div>
                    <div class="body">

                        <!-- Tab panes -->
                        <div class="tab-content m-t-10">
                            <div class="tab-pane table-responsive active" id="All">
                                <table class="table m-b-0 table-hover">
                                    <thead>
                                        <tr>                  
                                            <th>Device ID</th>
											<th>MAC Address</th>
											<th>Model</th>
                                        </tr>
                                    </thead>
                                    <tbody>
									
									<?php 
										// Fetch all monitoring devices (Corrected table and column names)
										try {
											$sql = "SELECT \"deviceID\", mac_address, model, status FROM monitoring_devices";
											$stmt = $conn->prepare($sql);
											$stmt->execute();
											$devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

											if ($devices) {
												foreach ($devices as $row) {
													echo "<tr>";
													echo "<td>" . htmlspecialchars($row['deviceID']) . "</td>";
													echo "<td>" . htmlspecialchars($row['mac_address']) . "</td>";
													echo "<td>" . htmlspecialchars($row['model']) . "</td>";
													echo "<td><span class='badge badge-info'>" . htmlspecialchars($row['status'] ?? 'Active') . "</span></td>";
													echo "</tr>";
												}
											} else {
												echo "<tr><td colspan='4' class='text-center'>No monitoring devices found in the database.</td></tr>";
											}
										} catch (PDOException $e) {
											echo "<tr><td colspan='4' class='text-center text-danger'>Database Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
										}
									?>
                                       
                                    </tbody>
                                </table>  
                            </div>
                            
                        </div>
						
                    </div>
                </div>
				
					
								<a href="./add-device.php" class="btn btn-primary float-right" target="_blank" >Add New Device</a>
            </div>
        </div>
    </div>
</section>
<!-- Jquery Core Js --> 
<script src="../assets/bundles/libscripts.bundle.js"></script> <!-- Bootstrap JS and jQuery v3.2.1 -->
<script src="../assets/bundles/vendorscripts.bundle.js"></script> <!-- slimscroll, waves Scripts Plugin Js -->  

<script src="../assets/bundles/mainscripts.bundle.js"></script><!-- Custom Js --> 

<link rel="stylesheet" href="../assets/plugins/toast/jquery.toast.min.css">
<script src="../assets/plugins/toast/jquery.toast.min.js"></script>

<?php 
if(isset($_GET['status'])){ 
	$type="success";
	if(isset($_GET['type'])){
		$type = "error";
		
	}
 ?>

<script>
$.toast({
	text: '<?php echo $_GET["status"] ?>',
	showHideTransition: 'slide',
	position: 'bottom-right', 
	hideAfter: 4000, 
	icon: '<?php echo $type ?>'
})
</script>
<?php } ?>

<script>
	$(function(){
		<?php if($_SESSION["user_type"] !== "admin"){ ?>
			$(".toggle-sidebar").click();
			$(".toggle-sidebar").addClass("d-none");
			$(".notification-box").addClass("d-none");
			
		<?php } ?>
		
		
	})
</script>
</body>

<!-- Mirrored from hms.cognisun.net/CUST Digihealth/html/light/patients.html by HTTrack Website Copier/3.x [XR&CO'2014], Sun, 06 Oct 2024 17:19:29 GMT -->
</html>
