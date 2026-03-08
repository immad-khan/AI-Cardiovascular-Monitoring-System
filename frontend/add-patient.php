<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
include("../config/DB_Config.php");
// Start the session
session_start();

include_once("../backend/patient_logic.php");

// Check if the user is logged in and if the user type is admin
if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'doctor')) {
    // If not admin, redirect to index.php with a relevant status
    header("Location: index.php?status=Access is denied&type=error");
    exit(); // Stop further execution after the redirect
}

$mac_addresses_linked = getLinkedMacAddresses($conn);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $result = handlePatientAction($conn, $_POST);
    
    // Redirect if result says so
    if (isset($result['redirect'])) {
        header("Location: " . $result['redirect'] . "?status=" . urlencode($result['status']) . "&type=" . $result['type']);
        exit();
    } else {
        $status = urlencode($result['status']);
        $type = urlencode($result['type']);
    }
}
// Delete the old logic below as it's been moved to handlePatientAction
?>
						$stmt->execute();
				}
				// Close the statement
				$stmt->close();
			} else {
				echo "Failed to prepare the SQL statement.";
			}
		}else{
		
			// Prepare and bind the SQL statement
			$stmt = $conn->prepare("INSERT INTO patients (patient_id, phone_no, email, age, gender, medical_history, associated_doctors, staff_name, ward_no, date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
			$stmt->bind_param("sssissssss", $patient_id, $phone_no, $email, $age, $gender, $medical_history, $associated_doctors, $staff_name, $ward_no, $date);
			
			// Execute the statement
			if ($stmt->execute()) {
				echo "New patient added successfully.";
			} else {
				echo "Error: " . $stmt->error;
			}
			if(!empty($mac_address)){
					$stmt = $conn->prepare("INSERT INTO device_patient_link (patient_id, mac_address) VALUES (?, ?)");
				$stmt->bind_param("ss", $patient_id, $mac_address);
				
				// Execute the statement
				if ($stmt->execute()) {
					echo "Device Linked successfully.";
				} else {
					echo "Error: " . $stmt->error;
					exit();
				}
			}
			
        // Close the statement
        $stmt->close();
		}
    }
}




// Assuming patientId is passed through GET or POST
$patientId = isset($_GET['patientId']) ? $_GET['patientId'] : '';
$phoneNo = '';
$email = '';
$age = '';
$gender = '';
$medicalHistory = '';
$associatedDoctors = ''; // This contains doctor IDs (comma-separated)
$staffName = '';
$wardNo = '';
$date = '';
$linked_mac_address = "";
if ($patientId) {
    // Prepare the SQL statement to fetch the patient record
    $sql = "SELECT * FROM patients WHERE patient_id = ?";
    
    // Prepare the statement to prevent SQL injection
    if ($stmt = $conn->prepare($sql)) {
        // Bind the patientId parameter to the query
        $stmt->bind_param("s", $patientId);
        
        // Execute the query
        $stmt->execute();
        
        // Fetch the result
        $result = $stmt->get_result();
        
        // Check if a patient record is found
        if ($result->num_rows > 0) {
            // Fetch the row and store in variables
            $row = $result->fetch_assoc();
            
            $phoneNo = $row['phone_no'];
            $email = $row['email'];
            $age = $row['age'];
            $gender = $row['gender'];
            $medicalHistory = $row['medical_history'];
            $associatedDoctors = $row['associated_doctors']; // This contains doctor IDs (comma-separated)
            $staffName = $row['staff_name'];
            $wardNo = $row['ward_no'];
            $date = $row['date'];

            // Fetch doctor names from the associatedDoctors (which is a list of doctor IDs)
            $doctorNames = []; // To store doctor names

            if (!empty($associatedDoctors)) {
                // Convert comma-separated doctor IDs into an array
                $doctorIds = explode(',', $associatedDoctors);
                $placeholders = implode(',', array_fill(0, count($doctorIds), '?'));
                
                // Prepare SQL query to fetch doctor names from the users table
                $sql_doctors = "SELECT full_name FROM doctorProfile WHERE user_id IN ($placeholders)";
                
                if ($stmt_doctors = $conn->prepare($sql_doctors)) {
                    // Dynamically bind doctor IDs to the SQL query
                    $stmt_doctors->bind_param(str_repeat('i', count($doctorIds)), ...$doctorIds);
                    
                    // Execute the query
                    $stmt_doctors->execute();
                    
                    // Get the result
                    $result_doctors = $stmt_doctors->get_result();
                    
                    // Fetch all doctor names
                    while ($doctor_row = $result_doctors->fetch_assoc()) {
                        $doctorNames[] = $doctor_row['full_name'];
                    }
                    
                    // Close the doctor statement
                    $stmt_doctors->close();
                }
            }

            // Now doctor names are stored in the $doctorNames array
            $doctorNamesList = implode(', ', $doctorNames); // Convert array to a string for display
			$stmt->close();

		}
	}
	 // Prepare the SQL statement to fetch the patient record
    $sql = "SELECT * FROM device_patient_link WHERE patient_id = ? and delinked_at is null";
    
    // Prepare the statement to prevent SQL injection
    if ($stmt = $conn->prepare($sql)) {
        // Bind the patientId parameter to the query
        $stmt->bind_param("s", $patientId);
        
        // Execute the query
        $stmt->execute();
        
        // Fetch the result
        $result = $stmt->get_result();
        
        // Check if a patient record is found
        if ($result->num_rows > 0) {
            // Fetch the row and store in variables
            $row = $result->fetch_assoc();
            $linked_mac_address = $row['mac_address'];
		}
	}
}

?>
<!doctype html>
<html class="no-js " lang="en">

<!-- Mirrored from hms.cognisun.net/oreo/html/light/add-patient.html by HTTrack Website Copier/3.x [XR&CO'2014], Sun, 06 Oct 2024 17:19:29 GMT -->
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
<meta name="description" content="Responsive Bootstrap 4 and web Application ui kit.">

<title>Add Patient - CUST Digihealth </title>
<link rel="icon" href="favicon.ico" type="image/x-icon">
<!-- Favicon-->
<link rel="stylesheet" href="../assets/plugins/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../assets/plugins/dropzone/dropzone.css">
<link href="../assets/plugins/bootstrap-material-datetimepicker/css/bootstrap-material-datetimepicker.css" rel="stylesheet" />
<link rel="stylesheet" href="../assets/plugins/bootstrap-select/css/bootstrap-select.css"/>
<!-- Custom Css -->
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/color_skins.css">
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
                    <li><a href="book-appointment.html"><i class="zmdi zmdi-calendar-check"></i><span>Appointment</span> </a></li>
                    <li><a href="javascript:void(0);" class="menu-toggle"><i class="zmdi zmdi-account-add"></i><span>Doctors</span> </a>
                        <ul class="ml-menu">
                            <li><a href="doctors.php">All Doctors</a></li>
                            <li><a href="add-doctor.php">Add Doctor</a></li>  
                            <li><a href="events.php">Doctor Schedule</a></li>
                        </ul>
                    </li>
                    <li class="active open"><a href="javascript:void(0);" class="menu-toggle"><i class="zmdi zmdi-account-o"></i><span>Patients</span> </a>
                        <ul class="ml-menu">
                            <li><a href="patients.php">All Patients</a></li>
                            <li class="active"><a href="add-patient.php">Add Patient</a></li>                       
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
            <div class="col-lg-7 col-md-5 col-sm-12">
                <h2>Add Patient
                <small class="text-muted">Welcome to CUST Digihealth</small>
                </h2>
            </div>
            <div class="col-lg-5 col-md-7 col-sm-12">               
                <ul class="breadcrumb float-md-right">
                    <li class="breadcrumb-item"><a href="dashboard.php"><i class="zmdi zmdi-home"></i> Home</a></li>
                    <li class="breadcrumb-item"><a href="javascript:void(0);">Patient</a></li>
                    <li class="breadcrumb-item active">Add Patient</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="container-fluid">
		<form method="post" action="#">
		
			<div class="row clearfix">
				<div class="col-lg-12 col-md-12 col-sm-12">
					<div class="card">
						<div class="header">
							<h2><strong>Basic</strong> Information <small>Patient profile</small> </h2>
							
						</div>
						<div class="body">
							
							<div class="row clearfix">
								<div class="col-sm-6">
									<div class="form-group">
									<?php 
									if ($patientId) { ?>
										
										<input type="hidden" name="editPatient" value="<?php echo $patientId ?>" />
										
										<input type="text" class="form-control" readonly value="<?php echo $patientId  ?>" name="patientId" placeholder="Patient Id">
									<?php }else{ ?>
										
										<input type="text" class="form-control" value="<?php echo $patientId  ?>" name="patientId" placeholder="Patient Id">
										
									<?php }
									?>
									</div>
								</div>
								<div class="col-sm-6">
									<div class="form-group">
										<input type="text" class="form-control" value="<?php echo $phoneNo  ?>" name="phoneNo" placeholder="Phone No.">
									</div>
								</div>
							</div>
							<div class="row clearfix">
						   
								<div class="col-sm-6">
									<div class="form-group">
										<input type="text" class="form-control" value="<?php echo $email  ?>" name="email" placeholder="Enter Email">
									</div>
								</div>						
								<div class="col-sm-3">
									<div class="form-group">
										<input type="text" class="form-control"  value="<?php echo $age  ?>" name="age" placeholder="Age">
									</div>
								</div>
								<div class="col-sm-3">
									<select class="form-control show-tick" name="gender">
										<option value="">- Gender -</option>
										<?php 
										if($gender == "Male") {?>
											<option  selected value="Male">Male</option>
										<?php }else{ ?>
										
											<option  value="Male">Male</option>
										<?php }?>
										<?php 
										if($gender == "Female") {?>
											<option  selected value="Female">Female</option>
										<?php }else{ ?>
										
											<option  value="Female">Female</option>
										<?php }?>
										
										<?php 
										if($gender == "Other") {?>
											<option  selected value="Other">Other</option>
										<?php }else{ ?>
										
											<option  value="Other">Other</option>
										<?php }?>
										
									</select>
								</div>     
							</div>
							<div class="row clearfix">                            
								<div class="col-sm-12">
									<div class="form-group">
										<textarea rows="4" class="form-control no-resize" name="medicalHistory" placeholder="Description / Medical history"><?php echo $medicalHistory  ?></textarea>
									</div>
								</div>
							   
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row clearfix">
				<div class="col-md-12">
					<div class="card">
						<div class="header">
							<h2><strong>Registration</strong> Information <small>Associate Doctor to Patient...</small> </h2>
						   
						</div>
						<div class="body">
							<div class="row clearfix">
								<div class="col-sm-6">
										<select class="form-control show-tick mb-2" name="AssociatedDoctors[]" data-live-search="true" multiple>
										
										<option value="">Select Doctor</option>
										<?php 
											// Fetch all doctors' names and specializations
											$sql = "SELECT u.id, p.full_name, p.specialization
													FROM users u
													JOIN doctorProfile p ON u.id = p.user_id
													WHERE u.type = 'doctor'";
											$result = $conn->query($sql);
											// Check if there are results
											if ($result->num_rows > 0) {
												// Output data of each doctor as options
												while ($row = $result->fetch_assoc()) {
													$associatedDoctorsArr = explode(",",$associatedDoctors);
													if (in_array($row['id'], $associatedDoctorsArr)) {
														echo "<option selected value='" . htmlspecialchars($row['id']) . "'>" 
														 . htmlspecialchars($row['full_name']) . " (" . htmlspecialchars($row['specialization']) . ")"
														 . "</option>";
													}else{
														echo "<option value='" . htmlspecialchars($row['id']) . "'>" 
														 . htmlspecialchars($row['full_name']) . " (" . htmlspecialchars($row['specialization']) . ")"
														 . "</option>";
													}
												}
											} else {
												echo "<option value=''>No doctors available</option>";
											}

										?>
										
										</select>
									   
								</div>
								<div class="col-sm-6">
									<select class="form-control show-tick mb-2" name="mac_address" data-live-search="true">
									
									<?php if(empty($linked_mac_address)) { ?>
										<option value="">Select Device</option>
									<?php }else{ ?>										
										<option value="">Delink Device</option>
									<?php } ?>
									<?php
									
									// Step 1: Fetch all ECG devices
									$sql = "SELECT id, mac_address, model FROM ecg_devices";
									$result = $conn->query($sql);

									if ($result->num_rows > 0) {
										// Loop through the devices and add them as options in the select tag
										while ($row = $result->fetch_assoc()) {
											 
											if($linked_mac_address == $row['mac_address']){
												echo "<option value='" . $row['mac_address'] . "' selected>" . $row['mac_address'] . " - " . $row['model'] . "</option>";
											}else{
												if (!in_array($row['mac_address'], $mac_addresses_linked)) {
													
												echo "<option value='" . $row['mac_address'] . "' >" . $row['mac_address'] . " - " . $row['model'] . "</option>";
												}
											}
										}
									} else {
										echo "<option value=''>No devices available</option>";
									}
									?>
									<option value=''></option>
									</select>
								</div>
								<div class="col-sm-6">
									<div class="form-group">
										<input type="text" class="form-control" value="<?php echo $staffName  ?>" name="StaffName" placeholder="Staff on Duty">
									</div>
								</div>
								<div class="col-sm-6">
									<div class="form-group">
										<input type="text" class="form-control" value="<?php echo $wardNo  ?>" name="WardNo" placeholder="Ward No.">
									</div>
								</div>
							</div>
							<div class="row clearfix">
								<div class="col-sm-6">
									<div class="input-group">
										<span class="input-group-addon">
											<i class="zmdi zmdi-calendar"></i>
										</span>
										<input type="text"  value="<?php echo $date  ?>" class="datetimepicker form-control" name="Date" placeholder="Please choose date & time...">
									</div>                               
								</div>
								<div class="col-sm-12">
									<button type="submit" class="btn btn-primary btn-round">Submit</button>
									<a href="./patients.php"  class="btn btn-default btn-round btn-simple">Go back to patients list</a>
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
<script src="../assets/bundles/libscripts.bundle.js"></script> <!-- Bootstrap JS and jQuery v3.2.1 -->
<script src="../assets/bundles/vendorscripts.bundle.js"></script> <!-- slimscroll, waves Scripts Plugin Js -->  

<script src="../assets/plugins/dropzone/dropzone.js"></script> <!-- Dropzone Plugin Js -->
<script src="../assets/plugins/momentjs/moment.js"></script> <!-- Moment Plugin Js -->
<script src="../assets/plugins/bootstrap-material-datetimepicker/js/bootstrap-material-datetimepicker.js"></script>


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
