<?php 
include("../config/DB_Config.php");
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

<!-- Mirrored from hms.cognisun.net/CUST Digihealth/html/light/ by HTTrack Website Copier/3.x [XR&CO'2014], Sun, 06 Oct 2024 17:15:35 GMT -->
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
<meta name="description" content="Responsive Bootstrap 4 and web Application ui kit.">
<title>Dashboard - CUST Digihealth</title>
<link rel="icon" href="favicon.ico" type="image/x-icon"> <!-- Favicon-->
<link rel="stylesheet" href="../assets/plugins/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../assets/plugins/jvectormap/jquery-jvectormap-2.0.3.min.css"/>
<link rel="stylesheet" href="../assets/plugins/morrisjs/morris.min.css" />
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
                    <li class="active open"><a href="dashboard.php"><i class="zmdi zmdi-home"></i><span>Dashboard</span></a></li>            
                    
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
                    <li><a href="javascript:void(0);" class="menu-toggle"><i class="zmdi zmdi-swap-alt"></i><span>ECG Devices</span> </a>
                        <ul class="ml-menu">
                            <li><a href="devices.php">All Devices</a></li>
                            <li><a href="add-device.php">Add Device</a></li>         
                        </ul>
                    </li>
                   
                   <li class="active open"><a href="./stats.php"><i class="zmdi zmdi-swap-alt"></i><span>Edge Devices Stats</span> </a>
                       
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
<!-- Main Content -->
<section class="content home">
    <div class="block-header">
        <div class="row">
            <div class="col-lg-5 col-md-5 col-sm-12">
                <h2>Dashboard
                <small>Welcome to CUST Digihealth</small>
                </h2>
            </div>            
            <div class="col-lg-7 col-md-7 col-sm-12 text-right">
                
                <ul class="breadcrumb float-md-right">
                    <li class="breadcrumb-item"><a href="dashboard.php"><i class="zmdi zmdi-home"></i> Home</a></li>
                    <li class="breadcrumb-item active">Dashboard</li>
                </ul>
            </div>
        </div>
    </div>
	<?php
	// Fetch the number of doctors
	$sql_doctors = "SELECT COUNT(*) AS doctor_count FROM users WHERE type = 'doctor'";
	$result_doctors = $conn->query($sql_doctors);

	if ($result_doctors->num_rows > 0) {
		$row_doctors = $result_doctors->fetch_assoc();
		$doctor_count = $row_doctors['doctor_count'];
	} else {
		$doctor_count = 0;
	}

	// Fetch the number of patients
	$sql_patients = "SELECT COUNT(*) AS patient_count FROM patients where isActive=1";
	$result_patients = $conn->query($sql_patients);

	if ($result_patients->num_rows > 0) {
		$row_patients = $result_patients->fetch_assoc();
		$patient_count = $row_patients['patient_count'];
	} else {
		$patient_count = 0;
	}

	?>
    <div class="container-fluid">
        <div class="row clearfix">
            <div class="col-lg-4 col-md-6">
                <div class="card">
                    <div class="body">
                        <h3 class="number count-to m-b-0" data-from="0" data-to="<?php echo $doctor_count ?>" data-speed="2500" data-fresh-interval="700"><?php echo $doctor_count ?> <i class="zmdi zmdi-trending-up float-right"></i></h3>
                        <p class="text-muted">Doctors</p>
                        <div class="progress">
                            <div class="progress-bar l-blush" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%;"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="card">
                    <div class="body">
                        <h3 class="number count-to m-b-0" data-from="0" data-to="<?php echo $patient_count ?>" data-speed="2500" data-fresh-interval="1000"><?php echo $patient_count ?> <i class="zmdi zmdi-trending-up float-right"></i></h3>
                        <p class="text-muted">Patients</p>
                        <div class="progress">
                            <div class="progress-bar l-green" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%;"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-12">
                <div class="card">
                    <div class="body">
                        <h3 class="number count-to m-b-0" data-from="0" data-to="18" data-speed="2500" data-fresh-interval="1000">18 <i class="zmdi zmdi-trending-up float-right"></i></h3>
                        <p class="text-muted">Nursing Staff </p>
                        <div class="progress">
                            <div class="progress-bar l-parpl" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
              
        <div class="row clearfix">
            <div class="col-lg-4 col-md-12">
                <div class="card">
                    <div class="header">
                        <h2>Timeline</h2>
                        <ul class="header-dropdown">                            
                            <li class="remove">
                                <a role="button" class="boxs-close"><i class="zmdi zmdi-close"></i></a>
                            </li>
                        </ul>
                    </div>
                    <div class="body">
                        <div class="new_timeline">
                            <div class="header">
                                <div class="color-overlay">
									<script>
									const currentDate = new Date();

										// Array of full month names
										const monthNames = [
										  "January", "February", "March", "April", "May", "June", 
										  "July", "August", "September", "October", "November", "December"
										];
										const dayNames = [
										  "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"
										];
										// Get the full current month name
										const currentMonthName = monthNames[currentDate.getMonth()];

										// Get the current day name
										const currentDayName = dayNames[currentDate.getDay()];
										
										// Get the current date
										const currentDay = currentDate.getDate();

										// Combine the month name and day
										const fullDate = `${currentMonthName} ${currentDay}, ${currentDate.getFullYear()}`;

									</script>
									
								
                                    <div class="day-number"><script>document.write(currentDay)</script></div>
                                    <div class="date-right">
                                    <div class="day-name"><script>document.write(currentDayName)</script> </div>
                                    <div class="month"><script>document.write(currentMonthName)</script> 
									
										<script>
											document.write(new Date().getFullYear())
										</script>
									</div>
                                    </div>
                                </div>                                
                            </div>
                            <ul>
                                <li>
                                    <div class="bullet pink"></div>
                                    <div class="time">5pm</div>
                                    <div class="desc">
                                        <h3>PT-112</h3>
                                        <h4>Abnormal Reading Received</h4>
                                    </div>
                                </li>
                                <li>
                                    <div class="bullet pink"></div>
                                    <div class="time">4:35pm</div>
                                    <div class="desc">
                                        <h3>PT-112</h3>
                                        <h4>Abnormal Reading Received</h4>
                                        
                                    </div>
                                </li>
                                <li>
                                    <div class="bullet pink"></div>
                                    <div class="time">2:29pm</div>
                                    <div class="desc">
                                        <h3>PT-113</h3>
                                        <h4>Abnormal Reading Received</h4>
                                        
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8 col-md-12">
                
                <div class="card patient_list">
                    <div class="header">
                        <h2><strong>New</strong> Patient List</h2>                        
                        <ul class="header-dropdown">
                            <li class="dropdown"> <a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"> <i class="zmdi zmdi-more"></i> </a>
                                <ul class="dropdown-menu dropdown-menu-right slideUp">
                                    <li><a href="javascript:void(0);">2024 Year</a></li>
                                    <li><a href="javascript:void(0);">2023 Year</a></li>
                                </ul>
                            </li>
                            <li class="remove">
                                <a role="button" class="boxs-close"><i class="zmdi zmdi-close"></i></a>
                            </li>
                        </ul>
                    </div>
                    <div class="body">
                        <div class="table-responsive">
                            <table class="table m-b-0 table-hover">
                                    <thead>
                                        <tr>                  
                                            <th>Patient ID</th>
											<th >Associated Doctors</th>
											<th>Attached Device</th>
											<!--<th>Date</th>-->
                                        </tr>
                                    </thead>
                                    <tbody>
									
									<?php 
									
									
									
										// Fetch all patients
										$sql = " SELECT p.patient_id, p.phone_no, p.email, p.age, p.gender, p.medical_history,  p.staff_name, p.ward_no, p.date, p.associated_doctors 
											FROM patients p 
											where p.isActive=1 limit 5";

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
													$sql_device = "SELECT mac_address, patient_id FROM device_patient_link WHERE patient_id='".$row['patient_id']."' and delinked_at IS NULL";
													
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
															$attached_device_mac = $device_row['mac_address'];
														}
														
														// Close the doctor statement
														$stmt_device->close();
													}
												}

												// Now doctor names are stored in the $doctorNames array
												$doctorNamesList = implode(', ', $doctorNames); // Convert array to a string for display
												echo '<tr>
												
														<td><a href=./Patient-Profile.php?patientId=' . htmlspecialchars($row['patient_id']) . ' target="_blank">'.htmlspecialchars($row['patient_id']).'</a></td>
														<td>' . htmlspecialchars($doctorNamesList ) . '</td>
														<td>' . htmlspecialchars($attached_device_mac ) . '</td>
														<!--<td>' . htmlspecialchars($row['date']) . '</td>-->
												
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
</section>
<!-- Jquery Core Js --> 
<script src="../assets/bundles/libscripts.bundle.js"></script> <!-- Lib Scripts Plugin Js ( jquery.v3.2.1, Bootstrap4 js) --> 
<script src="../assets/bundles/vendorscripts.bundle.js"></script> <!-- slimscroll, waves Scripts Plugin Js -->

<script src="../assets/bundles/morrisscripts.bundle.js"></script><!-- Morris Plugin Js -->
<script src="../assets/bundles/jvectormap.bundle.js"></script> <!-- JVectorMap Plugin Js -->
<script src="../assets/bundles/knob.bundle.js"></script> <!-- Jquery Knob, Count To, Sparkline Js -->

<script src="../assets/bundles/mainscripts.bundle.js"></script>
<script src="../assets/js/pages/index.js"></script>
</body>

<!-- Mirrored from hms.cognisun.net/CUST Digihealth/html/light/ by HTTrack Website Copier/3.x [XR&CO'2014], Sun, 06 Oct 2024 17:16:01 GMT -->
</html>
