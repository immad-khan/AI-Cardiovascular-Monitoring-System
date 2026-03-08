<?php 
include("../config/DB_Config.php");
// Start the session
session_start();


// Check if the user is logged in and if the user type is admin
if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'doctor')) {
    // If not admin, redirect to index.php with a relevant status
    header("Location: index.php?status=Access is denied&type=error");
    exit(); // Stop further execution after the redirect
}else if($_SESSION['user_type'] == 'doctor'){
	$stmt = $conn->prepare("SELECT id FROM doctorProfile WHERE user_id = ?");
	$stmt->bind_param("i", $_SESSION['user_id']);
	$stmt->execute();
	$result = $stmt->get_result();

	// Check if a user exists with that username
	if ($result->num_rows > 0) {
			
		header("Location: patients.php?status=Doctor login successful");
		exit();
	}
	
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Include the backend logic
    include_once("../backend/doctor_logic.php");
    
    // Call the registration handler
    $result = handleDoctorRegistration($conn, $_POST, $_FILES, $_SESSION);
    
    // Perform redirect based on result
    $status = urlencode($result['status']);
    $type = urlencode($result['type']);
    $redirect = $result['redirect'];
    
    header("Location: $redirect?status=$status&type=$type");
    exit();
}
		
		if ($stmt->execute()) {
			// Get the user ID of the newly created doctor account
			$user_id = $stmt->insert_id;

			// Insert profile information into doctorProfile table
			$stmt_profile = $conn->prepare("INSERT INTO doctorProfile (user_id, full_name, specialization, phone_number, profile_picture, description,website_url) VALUES (?, ?, ?, ?, ?, ?,?)");
			$stmt_profile->bind_param("issssss", $user_id, $full_name, $specialization, $phone_number, $profile_picture_path, $description,$website_url);
			
			if ($stmt_profile->execute()) {
				header("Location: doctors.php?status=Account Created");
			} else {
				header("Location: add-doctor.php?status=Profile creation failed&type=error");
			}

			$stmt_profile->close();
		} else {
			header("Location: add-doctor.php?status=User creation failed&type=error");
		}
			
		$stmt->close();
	 }

}

// Close the connection
$conn->close();
?>

<!doctype html>
<html class="no-js " lang="en">

<!-- Mirrored from hms.cognisun.net/oreo/html/light/add-doctor.html by HTTrack Website Copier/3.x [XR&CO'2014], Sun, 06 Oct 2024 17:19:29 GMT -->
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
<meta name="description" content="Responsive Bootstrap 4 and web Application ui kit.">

<title>Add Doctor - CUST Digihealth</title>
<link rel="icon" href="favicon.ico" type="image/x-icon">
<!-- Favicon-->
<link rel="stylesheet" href="../assets/plugins/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../assets/plugins/dropzone/dropzone.css">
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
                    <li class="active open"><a href="javascript:void(0);" class="menu-toggle"><i class="zmdi zmdi-account-add"></i><span>Doctors</span> </a>
                        <ul class="ml-menu">
                            <li><a href="doctors.php">All Doctors</a></li>
                            <li class="active"><a href="add-doctor.php">Add Doctor</a></li> 
                            <li><a href="events.php">Doctor Schedule</a></li>
                        </ul>
                    </li>
                    <li><a href="javascript:void(0);" class="menu-toggle"><i class="zmdi zmdi-account-o"></i><span>Patients</span> </a>
                        <ul class="ml-menu">
                            <li><a href="patients.php">All Patients</a></li>
                            <li><a href="add-patient.php">Add Patient</a></li>           
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
                <h2>
				
                <small class="text-muted">Welcome to CUST Digihealth</small>
				
				Create Doctor Profile
                </h2>
            </div>
            <div class="col-lg-5 col-md-7 col-sm-12">
                <button class="btn btn-white btn-icon btn-round d-none d-md-inline-block float-right m-l-10" type="button">
                    <i class="zmdi zmdi-plus"></i>
                </button>
                <ul class="breadcrumb float-md-right">
                    <li class="breadcrumb-item"><a href="index-2.html"><i class="zmdi zmdi-home"></i> Home</a></li>
                    <li class="breadcrumb-item"><a href="javascript:void(0);">Doctors</a></li>
                    <li class="breadcrumb-item active">Create Doctor Profile</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row clearfix">
			<div class="col-lg-12 col-md-12 col-sm-12">
			
				<form method="post" action="#"  enctype="multipart/form-data">
					<div class="card">
						<div class="header">
							<h2><strong>Doctor's</strong> Account Information <small>Login Credentials</small> </h2>
							
						</div>
						<div class="body">
							<div class="row clearfix">
								<div class="col-sm-6">
									<div class="form-group">
										 <?php if(isset($_SESSION['email'])){ ?>
											 
											 <input type="text" class="form-control" readonly value="<?php echo $_SESSION['username'] ?>" name="username" placeholder="User Name" required>
										<?php }else{ ?>
											<input type="text" class="form-control" name="username" placeholder="User Name" required>
											
										<?php } ?>
										
									</div>
								</div>
								<div class="col-sm-6">
									<div class="form-group">
									
										<?php if(isset($_SESSION['email'])){ ?>
											 
											 <input type="email" class="form-control" readonly value="<?php echo $_SESSION['email'] ?>" name="email" placeholder="Email" required>
										<?php }else{ ?>
											<input type="email" class="form-control" name="email" placeholder="Email" required>
											
										<?php } ?>
										
									</div>
								</div>
								<div class="col-sm-6 <?php echo isset($_SESSION['password']) == true ?  "d-none": ""; ?>" >
									<div class="form-group">
									
									
										<?php if(isset($_SESSION['password'])){ ?>
											 
											 <input type="password" class="form-control" readonly value="<?php echo $_SESSION['password'] ?>" name="password" placeholder="Password" required>
										<?php }else{ ?>
											<input type="password" class="form-control" name="password" placeholder="Password" required>
											
										<?php } ?>
									</div>
								</div>
								<div class="col-sm-6 <?php echo isset($_SESSION['password']) == true ?  "d-none": ""; ?>">
									<div class="form-group">
									
									
										<?php if(isset($_SESSION['password'])){ ?>
											 
											 <input type="password" class="form-control" readonly value="<?php echo $_SESSION['password'] ?>" name="confirm_password" placeholder="Confirm Password" required>
										<?php }else{ ?>
											<input type="password" class="form-control" required name="confirm_password" placeholder="Confirm Password">
											
										<?php } ?>
									</div>
								</div>
							</div>
						</div>
					</div>
				
				
					<div class="card">
						<div class="header">
							<h2><strong>Doctor</strong> Profile <small> </h2>
							
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
											<input type="text" class="form-control" name="specialization" placeholder="Speciality"  required>
										</div>
									</div>
								</div>
								<div class="row clearfix">
								
									<div class="col-sm-6">
										<div class="form-group">
											<input  type="tel" id="phone_number" name="phone_number" pattern="[0-9]{2}-[0-9]{3}-[0-9]{7}" class="form-control" placeholder="Phone" required>
										</div>
									</div>
									
									<div class="col-sm-6">
										<div class="form-group">
											<input type="file" class="form-control" name="profile_picture" placeholder="Photo"  accept="image/*" required>
										</div>
									</div>
									<div class="col-sm-12">
										<div class="form-group">
											<input type="text" class="form-control" name="website_url" placeholder="Website Url" >
										</div>
									</div>
									<div class="col-sm-12">
										<div class="form-group">
											<textarea rows="4" class="form-control no-resize" name="description" placeholder="Please type what you want..."></textarea>
										</div>
									</div>
									<div class="col-sm-12">
										<button type="submit" class="btn btn-primary btn-round">Submit</button>
										<button type="submit" class="btn btn-default btn-round btn-simple">Cancel</button>
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
<script src="../assets/bundles/libscripts.bundle.js"></script> <!-- Bootstrap JS and jQuery v3.2.1 -->
<script src="../assets/bundles/vendorscripts.bundle.js"></script> <!-- slimscroll, waves Scripts Plugin Js -->  

<script src="../assets/plugins/dropzone/dropzone.js"></script> <!-- Dropzone Plugin Js -->
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
<!-- Mirrored from hms.cognisun.net/oreo/html/light/add-doctor.html by HTTrack Website Copier/3.x [XR&CO'2014], Sun, 06 Oct 2024 17:19:29 GMT -->
</html>
