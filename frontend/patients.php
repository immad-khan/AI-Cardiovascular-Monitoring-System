<?php 
include("../config/DB_Config.php");
// Start the session
session_start();
// Check if the user is logged in and if the user type is admin
if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'doctor')) {
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
                    <li class="active open"><a href="javascript:void(0);" class="menu-toggle"><i class="zmdi zmdi-account-o"></i><span>Patients</span> </a>
                        <ul class="ml-menu">
                            <li class="active"><a href="patients.php">All Patients</a></li>
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

<section class="content">
    <div class="block-header">
        <div class="row">
            <div class="col-lg-7 col-md-5 col-sm-12">
                <h2>All Patients
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
                    <li class="breadcrumb-item active">All Patients</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row clearfix">
            <div class="col-md-12">
                <div class="card patients-list">
                    <div class="header">
                        <h2><strong>Patients</strong> List</h2>
                        <ul class="header-dropdown">
                            <li class="dropdown"> <a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"> <i class="zmdi zmdi-more"></i> </a>
                                <ul class="dropdown-menu dropdown-menu-right slideUp">
                                    <li><a href="add-patient.php">Add New Patient</a></li>
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
                                            <th>Patient ID</th>
											<th>Gender</th>
											<th>Associated Doctors</th>
											<th>Attached Device</th>
											<th>Date</th>
											<th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
									
									<?php 
									
										$doctor_condition = "";
										 if($_SESSION['user_type'] == 'doctor'){
											 $doctor_condition = " and  FIND_IN_SET(".$_SESSION['user_id'].", p.associated_doctors) > 0";
											 
										 }
										
										// Fetch all patients
										$sql = " SELECT p.patient_id, p.phone_no, p.email, p.age, p.gender, p.medical_history,  p.staff_name, p.ward_no, p.date, p.associated_doctors 
											FROM patients p 
											where p.isActive=1 ".$doctor_condition;

										$result = $conn->query($sql);

										// Check if there are results
										if ($result->num_rows > 0) {
											   while ($row = $result->fetch_assoc()) {
												   									
												// Fetch doctor names from the associatedDoctors (which is a list of doctor IDs)
												$doctorNames = []; // To store doctor names
												$attached_device_mac = "";
												if (!empty($row['associated_doctors'])) {
													// Convert comma-separated doctor IDs into an array
													$doctorIds = explode(',', $row['associated_doctors']);
													$placeholders = implode(',', array_fill(0, count($doctorIds), '?'));
													
													// Prepare SQL query to fetch doctor names from the users table
													$sql_doctors = "SELECT full_name, specialization FROM doctorProfile WHERE user_id IN ($placeholders)";
													
													if ($stmt_doctors = $conn->prepare($sql_doctors)) {
														// Dynamically bind doctor IDs to the SQL query
														$stmt_doctors->bind_param(str_repeat('i', count($doctorIds)), ...$doctorIds);
														
														// Execute the query
														$stmt_doctors->execute();
														
														// Get the result
														$result_doctors = $stmt_doctors->get_result();
														
														// Fetch all doctor names
														while ($doctor_row = $result_doctors->fetch_assoc()) {
															$doctorNames[] = $doctor_row['full_name'] ." (".$doctor_row['specialization'].")";
														}
														
														// Close the doctor statement
														$stmt_doctors->close();
													}
													
													// Prepare SQL query to fetch doctor names from the users table
													$sql_device = "SELECT dpl.mac_address, dpl.patient_id, ed.model FROM device_patient_link dpl inner join ecg_devices ed on dpl.mac_address=ed.mac_address  WHERE patient_id='".$row['patient_id']."' and delinked_at IS NULL";
													
													if ($stmt_device = $conn->prepare($sql_device)) {
														// Dynamically bind doctor IDs to the SQL query
														
														try {
															
															if (!$stmt_device->execute()) {
																throw new Exception("Execute failed: " . $stmt_device->error);
																
															}
														} catch (Exception $e) {
															// Handle any exceptions
															echo "Error: " . $e->getMessage();
															
														}
														// Get the result
														$result_device = $stmt_device->get_result();
														
														// Fetch all doctor names
														while ($device_row = $result_device->fetch_assoc()) {
															$attached_device_mac = $device_row['model']. " (".$device_row['mac_address'].")";
														}
														
														// Close the doctor statement
														$stmt_device->close();
													}
												}

												// Now doctor names are stored in the $doctorNames array
												$doctorNamesList = implode(', ', $doctorNames); // Convert array to a string for display
												echo '<tr>
												
														<td><a href=./Patient-Profile.php?patientId=' . htmlspecialchars($row['patient_id']) . ' target="_new">'.htmlspecialchars($row['patient_id']).'</a></td>
														<td>' . htmlspecialchars($row['gender']) . '</td>
														<td>' . htmlspecialchars($doctorNamesList ) . '</td>
														<td>' . htmlspecialchars($attached_device_mac ) . '</td>
														<td>' . htmlspecialchars($row['date']) . '</td>
														<td><a href="./add-patient.php?patientId='.htmlspecialchars($row['patient_id']).'">Edit</a></td>
													</tr>';
											}
																					
										}
									?>
                                       
                                    </tbody>
                                </table>                            
                            </div>
                            
                        </div>
                    </div>
                </div>
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
</body>
<script>
	$(function(){
		<?php if($_SESSION["user_type"] !== "admin"){ ?>
			$(".toggle-sidebar").click();
			$(".toggle-sidebar").addClass("d-none");
			$(".notification-box").addClass("d-none");
			
		<?php } ?>
		
		
	})
</script>

<!-- Mirrored from hms.cognisun.net/CUST Digihealth/html/light/patients.html by HTTrack Website Copier/3.x [XR&CO'2014], Sun, 06 Oct 2024 17:19:29 GMT -->
</html>
