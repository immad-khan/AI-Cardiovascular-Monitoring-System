<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
include("./config/DB_Config.php");
// Start the session
session_start();

// Check if the user is logged in and if the user type is admin

if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'tech-admin')) {
    // If not admin, redirect to index.php with a relevant status
    header("Location: index.php?status=Access is denied&type=error");
    exit(); // Stop further execution after the redirect
}

// Step 1: Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mac_address = $_POST['mac_address'];
    $model = $_POST['model'];

    // Step 2: Prepare and bind
    $stmt = $conn->prepare("INSERT INTO ecg_devices (mac_address, model) VALUES (?, ?)");
    $stmt->bind_param("ss", $mac_address, $model);

    // Step 3: Execute the statement
    if ($stmt->execute()) {
        // Redirect to devices.php after successful addition
        header("Location: devices.php?status=Device Added Successfully");
        exit(); // Ensure no further code is executed after the redirect
    } else {
        echo "Error: " . $stmt->error;
    }

    // Step 4: Close the statement
    $stmt->close();
}

// Step 5: Close the database connection
$conn->close();
?>

<!doctype html>
<html class="no-js " lang="en">

<!-- Mirrored from hms.cognisun.net/oreo/html/light/add-patient.html by HTTrack Website Copier/3.x [XR&CO'2014], Sun, 06 Oct 2024 17:19:29 GMT -->
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
<meta name="description" content="Responsive Bootstrap 4 and web Application ui kit.">

<title>Add ECG Device - CUST Digihealth </title>
<link rel="icon" href="favicon.ico" type="image/x-icon">
<!-- Favicon-->
<link rel="stylesheet" href="./assets/plugins/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="./assets/plugins/dropzone/dropzone.css">
<link href="./assets/plugins/bootstrap-material-datetimepicker/css/bootstrap-material-datetimepicker.css" rel="stylesheet" />
<link rel="stylesheet" href="./assets/plugins/bootstrap-select/css/bootstrap-select.css"/>
<!-- Custom Css -->
<link rel="stylesheet" href="./assets/css/main.css">
<link rel="stylesheet" href="./assets/css/color_skins.css">
</head>
<body class="theme-cyan">
<!-- Page Loader -->
<div class="page-loader-wrapper">
    <div class="loader">
        <div class="m-t-30"><img class="zmdi-hc-spin" src="./assets/images/logo.svg" width="48" height="48" alt="Oreo"></div>
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
                    <li><a href="book-appointment.html"><i class="zmdi zmdi-calendar-check"></i><span>Appointment</span> </a></li>
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
                            <li><a href="devices.php">All Devices</a></li>
                            <li class="active"><a href="add-device.php">Add Device</a></li>         
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
            <div class="col-lg-7 col-md-5 col-sm-12">
                <h2>Add ECG Device
                <small class="text-muted">Welcome to CUST Digihealth</small>
                </h2>
            </div>
            <div class="col-lg-5 col-md-7 col-sm-12">               
                <ul class="breadcrumb float-md-right">
                    <li class="breadcrumb-item"><a href="dashboard.php"><i class="zmdi zmdi-home"></i> Home</a></li>
                    <li class="breadcrumb-item"><a href="javascript:void(0);">Patient</a></li>
                    <li class="breadcrumb-item active">Add Device</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="container-fluid">
		<form method="post" action="#">
		
			<div class="row clearfix">
				<div class="col-md-12">
					<div class="card">
						<div class="header">
							<h2><strong>ECG Device</strong> Information </h2>
						   
						</div>
						<div class="body">
							
							<div class="row clearfix">
								<div class="col-sm-6">
									<div class="form-group">
										<input type="text" class="form-control" name="mac_address" placeholder="ECG Device MAC Address">
									</div>
								</div>
								<div class="col-sm-6">
										<select class="form-control show-tick mb-2" name="model" data-live-search="true" >
										<option value="RPI-Kit">RPI-Kit</option>
										<option value="Samsung Watch">Samsung Watch</option>
										
										</select>
								</div>
								<div class="col-sm-12">
									<button type="submit" class="btn btn-primary btn-round">Submit</button>
									<a href="./devices.php"  class="btn btn-default btn-round btn-simple">Go back to devices list</a>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		
		</form>
    </div>
</section>
<!-- Jquery Core Js --> 
<script src="./assets/bundles/libscripts.bundle.js"></script> <!-- Bootstrap JS and jQuery v3.2.1 -->
<script src="./assets/bundles/vendorscripts.bundle.js"></script> <!-- slimscroll, waves Scripts Plugin Js -->  

<script src="./assets/plugins/dropzone/dropzone.js"></script> <!-- Dropzone Plugin Js -->
<script src="./assets/plugins/momentjs/moment.js"></script> <!-- Moment Plugin Js -->
<script src="./assets/plugins/bootstrap-material-datetimepicker/js/bootstrap-material-datetimepicker.js"></script>

<script src="./assets/bundles/mainscripts.bundle.js"></script><!-- Custom Js -->

<link rel="stylesheet" href="./assets/plugins/toast/jquery.toast.min.css">
<script src="./assets/plugins/toast/jquery.toast.min.js"></script>

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
    $(function () {
    //Datetimepicker plugin
    $('.datetimepicker').bootstrapMaterialDatePicker({
        format: 'dddd DD MMMM YYYY - HH:mm',
        clearButton: true,
        weekStart: 1
    });
});
</script>

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

<!-- Mirrored from hms.cognisun.net/oreo/html/light/add-patient.html by HTTrack Website Copier/3.x [XR&CO'2014], Sun, 06 Oct 2024 17:19:29 GMT -->
</html>