<?php 
include("../config/DB_Config.php");
// Start the session
session_start();
// Check if the user is logged in and if the user type is admin or doctor
if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'doctor')) {
    header("Location: index.php?status=Access is denied&type=error");
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

<title>Patients - CUST Digihealth </title>
<link rel="icon" href="favicon.ico" type="image/x-icon">
<link rel="stylesheet" href="../assets/plugins/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/color_skins.css">
</head>
<body class="theme-cyan">
<div class="page-loader-wrapper">
    <div class="loader">
        <div class="m-t-30"><img class="zmdi-hc-spin" src="../assets/images/logo.svg" width="48" height="48" alt="CUST Digihealth"></div>
        <p>Please wait...</p>
    </div>
</div>
<div class="overlay"></div>
<nav class="navbar p-l-5 p-r-5">
    <?php include("top_nav.php") ?>
</nav>
<aside id="leftsidebar" class="sidebar">
     <ul class="nav nav-tabs">
        <li class="nav-item"><a class="nav-link active" data-toggle="tab" style="background: #00cfd1; color: white;" href="#dashboard"><img src="../assets/images/logo.svg" width="30" alt="CUST Digihealth"> &nbsp; CUST  DIGIHEALTH </a></li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane stretchRight active" id="dashboard">
            <div class="menu">
                <ul class="list">
                    <li>
                        <div class="user-info">
                            <div class="image"><a href="profile.html" class=" waves-effect waves-block"><img src="../assets/images/admin.png" alt="User"></a></div>
                            <div class="detail">
                                <h4><?php echo htmlspecialchars($_SESSION['user_type'] == 'admin' ? 'Super Administrator' : 'Doctor'); ?></h4>
                                <small><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></small>                        
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
                </ul>
            </div>
        </div>
    </div>    
</aside>

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
                        </ul>
                    </div>
                    <div class="body">
                        <div class="tab-content m-t-10">
                            <div class="tab-pane table-responsive active" id="All">
                                <table class="table m-b-0 table-hover">
                                    <thead>
                                        <tr>                  
                                            <th>Patient ID</th>
                                            <th>Name</th>
                                            <th>Gender</th>
                                            <th>Age</th>
                                            <th>Assigned Doctor</th>
                                            <th>Attached Device</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php 
                                        try {
                                            $doctor_condition = "";
                                            $params = [];
                                            if($_SESSION['user_type'] == 'doctor'){
                                                $doctor_condition = " AND p.\"assignedDoctorID\" = ?";
                                                $params[] = $_SESSION['user_id'];
                                            }

                                            $sql = "SELECT p.*, u.username as doctor_name, dp.full_name as doctor_full_name, 
                                                    (SELECT md.model || ' (' || md.mac_address || ')' 
                                                     FROM monitoring_devices md 
                                                     WHERE md.\"patientID\" = p.\"patientID\" LIMIT 1) as device_info
                                                    FROM patients p 
                                                    LEFT JOIN users u ON p.\"assignedDoctorID\" = u.\"userID\"
                                                    LEFT JOIN \"doctorProfile\" dp ON u.\"userID\" = dp.\"userID\"
                                                    WHERE p.\"isActive\" = TRUE $doctor_condition
                                                    ORDER BY p.date DESC";

                                            $stmt = $conn->prepare($sql);
                                            $stmt->execute($params);

                                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                $doctorDisplay = $row['doctor_full_name'] ?? $row['doctor_name'] ?? 'Unassigned';
                                                $deviceDisplay = $row['device_info'] ?? 'None';
                                                
                                                echo '<tr>
                                                    <td><a href="./Patient-Profile.php?patientId=' . htmlspecialchars($row['patientID']) . '">'.htmlspecialchars($row['patientID']).'</a></td>
                                                    <td>' . htmlspecialchars($row['name']) . '</td>
                                                    <td>' . htmlspecialchars($row['gender']) . '</td>
                                                    <td>' . htmlspecialchars($row['age']) . '</td>
                                                    <td>' . htmlspecialchars($doctorDisplay) . '</td>
                                                    <td>' . htmlspecialchars($deviceDisplay) . '</td>
                                                    <td>' . date('d M Y', strtotime($row['date'])) . '</td>
                                                    <td>
                                                        <a href="./Patient-Profile.php?patientId=' . htmlspecialchars($row['patientID']) . '" class="btn btn-sm btn-info"><i class="zmdi zmdi-eye"></i></a>
                                                        <button class="btn btn-sm btn-danger"><i class="zmdi zmdi-delete"></i></button>
                                                    </td>
                                                </tr>';
                                            }
                                        } catch (PDOException $e) {
                                            echo '<tr><td colspan="8" class="text-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
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

<script src="../assets/bundles/libscripts.bundle.js"></script>
<script src="../assets/bundles/vendorscripts.bundle.js"></script>
<script src="../assets/bundles/mainscripts.bundle.js"></script>
</body>
</html>
