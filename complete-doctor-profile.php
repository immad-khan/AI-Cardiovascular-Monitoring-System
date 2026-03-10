<?php
session_start();
include("./config/DB_Config.php");

// Check if the user is logged in as doctor or admin
if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'doctor')) {
    header("Location: frontend/sign-up.php?status=access_denied&type=error");
    exit();
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate the input
    $full_name = htmlspecialchars(trim($_POST['full_name']));
    $specialization = htmlspecialchars(trim($_POST['specialization']));
    $phone_number = htmlspecialchars(trim($_POST['phone_number']));
    $description = htmlspecialchars(trim($_POST['description']));
    $website_url = htmlspecialchars(trim($_POST['website_url']));
    $profile_picture = $_FILES['profile_picture'];

    // Validate required fields
    if (empty($full_name) || empty($specialization) || empty($phone_number)) {
        header("Location: complete-doctor-profile.php?status=missing_fields&type=error");
        exit();
    }

    // Validate the profile picture (optional — allow empty)
    $profile_picture_path = null;
    if (!empty($profile_picture['name'])) {
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $file_extension = strtolower(pathinfo($profile_picture['name'], PATHINFO_EXTENSION));

        if ($profile_picture['error'] !== UPLOAD_ERR_OK) {
            header("Location: complete-doctor-profile.php?status=file_upload_error&type=error");
            exit();
        }

        if (!in_array($file_extension, $allowed_extensions)) {
            header("Location: complete-doctor-profile.php?status=invalid_file_type&type=error");
            exit();
        }

        $upload_directory = './uploads/';
        $profile_picture_path = $upload_directory . uniqid() . '.' . $file_extension;

        if (!move_uploaded_file($profile_picture['tmp_name'], $profile_picture_path)) {
            header("Location: complete-doctor-profile.php?status=upload_failed&type=error");
            exit();
        }
    }

    // Insert profile information into doctorProfile table using the logged-in userID
    $user_id = $_SESSION['user_id'];
    try {
        $stmt_profile = $conn->prepare('INSERT INTO "doctorProfile" ("userID", full_name, specialization, phone_number, profile_picture, description, website_url) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt_profile->execute([$user_id, $full_name, $specialization, $phone_number, $profile_picture_path, $description, $website_url]);
        header("Location: frontend/patients.php?status=profile_created&type=success");
        exit();
    } catch (PDOException $e) {
        header("Location: complete-doctor-profile.php?status=profile_creation_failed&type=error");
        exit();
    }
}
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
<link rel="stylesheet" href="./assets/plugins/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="./assets/plugins/dropzone/dropzone.css">
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
				Add Doctor
                </h2>
            </div>
            <div class="col-lg-5 col-md-7 col-sm-12">
                <button class="btn btn-white btn-icon btn-round d-none d-md-inline-block float-right m-l-10" type="button">
                    <i class="zmdi zmdi-plus"></i>
                </button>
                <ul class="breadcrumb float-md-right">
                    <li class="breadcrumb-item"><a href="index-2.html"><i class="zmdi zmdi-home"></i> Home</a></li>
                    <li class="breadcrumb-item"><a href="javascript:void(0);">Doctors</a></li>
                    <li class="breadcrumb-item active">Add Doctors</li>
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
							<ul class="header-dropdown">
								<li class="remove">
									<a role="button" class="boxs-close"><i class="zmdi zmdi-close"></i></a>
								</li>
							</ul>
						</div>
						<div class="body">
							<div class="row clearfix">
								<div class="col-sm-6">
									<div class="form-group">
										<input type="text" class="form-control" name="username" placeholder="User Name" required>
									</div>
								</div>
								<div class="col-sm-6">
									<div class="form-group">
										<input type="email" class="form-control" name="email" placeholder="Email" required>
									</div>
								</div>
								<div class="col-sm-6">
									<div class="form-group">
										<input type="text" class="form-control" name="password" placeholder="Password" required>
									</div>
								</div>
								<div class="col-sm-6">
									<div class="form-group">
										<input type="text" class="form-control" required name="confirm_password" placeholder="Confirm Password">
									</div>
								</div>
							</div>
						</div>
					</div>
				
				
					<div class="card">
						<div class="header">
							<h2><strong>Doctor</strong> Profile <small> </h2>
							<ul class="header-dropdown">
								<li class="remove">
									<a role="button" class="boxs-close"><i class="zmdi zmdi-close"></i></a>
								</li>
							</ul>
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
<script src="./assets/bundles/libscripts.bundle.js"></script> <!-- Bootstrap JS and jQuery v3.2.1 -->
<script src="./assets/bundles/vendorscripts.bundle.js"></script> <!-- slimscroll, waves Scripts Plugin Js -->  

<script src="./assets/plugins/dropzone/dropzone.js"></script> <!-- Dropzone Plugin Js -->
<script src="./assets/bundles/mainscripts.bundle.js"></script><!-- Custom Js -->
</body>

<!-- Mirrored from hms.cognisun.net/oreo/html/light/add-doctor.html by HTTrack Website Copier/3.x [XR&CO'2014], Sun, 06 Oct 2024 17:19:29 GMT -->
</html>