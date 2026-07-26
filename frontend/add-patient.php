<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
include("../config/DB_Config.php");
// Start the session
session_start();

include_once("../backend/patient_logic.php");

// Check if the user is logged in and if the user type is admin or doctor
if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'doctor')) {
    header("Location: index.php?status=Access is denied&type=error");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $result = handlePatientAction($conn, $_POST);
    if (isset($result['redirect'])) {
        header("Location: " . $result['redirect'] . "?status=" . urlencode($result['status']) . "&type=" . $result['type']);
        exit();
    } else {
        $status_msg = $result['status'];
        $type_msg = $result['type'];
    }
}

// Initializing variables
$patientId = $_POST['patientId'] ?? ($_GET['patientId'] ?? '');
$name = $_POST['name'] ?? '';
$phoneNo = $_POST['phoneNo'] ?? '';
$email = $_POST['email'] ?? '';
$age = $_POST['age'] ?? '';
$gender = $_POST['gender'] ?? '';
$medicalHistory = $_POST['medicalHistory'] ?? '';
// Auto-assign the doctor if the current user is a doctor
$assignedDoctorID = ($_SESSION['user_type'] === 'doctor') ? $_SESSION['user_id'] : null;
$staffName = $_POST['staff_name'] ?? '';
$wardNo = $_POST['ward_no'] ?? '';
$date = $_POST['Date'] ?? date('Y-m-d H:i:s');
$linked_mac_address = "";

if ($patientId) {
    try {
        $sql = "SELECT * FROM patients WHERE \"patientID\" = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$patientId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $name = $row['name'];
            $phoneNo = $row['phone_no'];
            $email = $row['email'];
            $age = $row['age'];
            $gender = $row['gender'];
            $medicalHistory = $row['medical_history'];
            $assignedDoctorID = $row['assignedDoctorID'];
            $staffName = $row['staff_name'];
            $wardNo = $row['ward_no'];
            $date = $row['date'];

            // Fetch linked device
            $link_sql = "SELECT mac_address FROM device_patient_link WHERE patient_id = ? AND delinked_at IS NULL LIMIT 1";
            $link_stmt = $conn->prepare($link_sql);
            $link_stmt->execute([$patientId]);
            $link_row = $link_stmt->fetch();
            if ($link_row) $linked_mac_address = $link_row['mac_address'];
        }
    } catch (PDOException $e) {
        $error = "Error fetching patient: " . $e->getMessage();
    }
}

// Fetch Doctors list for the select box
$doctors_list = [];
try {
    $d_sql = "SELECT \"userID\", full_name, specialization FROM \"doctorProfile\"";
    $doctors_list = $conn->query($d_sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// Fetch available (unassigned) devices
$available_devices = [];
try {
    $dev_sql = "SELECT mac_address, model FROM monitoring_devices WHERE status != 'Assigned' OR \"patientID\" IS NULL OR \"patientID\" = '' OR \"patientID\" = ?";
    $dev_stmt = $conn->prepare($dev_sql);
    $dev_stmt->execute([$patientId]);
    $available_devices = $dev_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

?>
<!doctype html>
<html class="no-js " lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
<title>Add Patient - CUST Digihealth </title>
<link rel="icon" href="favicon.ico" type="image/x-icon">
<link rel="stylesheet" href="../assets/plugins/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../assets/plugins/bootstrap-select/css/bootstrap-select.css"/>
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/color_skins.css">
</head>
<body class="theme-cyan">
<nav class="navbar p-l-5 p-r-5">
    <?php include("top_nav.php") ?>
</nav>
<aside id="leftsidebar" class="sidebar">
    <?php /* Keep navigation sidebar content standard or shared */ ?>
    <!-- Basic Menu -->
    <div class="menu">
        <ul class="list">
             <li><a href="dashboard.php"><i class="zmdi zmdi-home"></i><span>Dashboard</span></a></li>
             <li class="active open"><a href="patients.php"><i class="zmdi zmdi-account-o"></i><span>Patients</span></a></li>
        </ul>
    </div>
</aside>

<section class="content">
    <div class="block-header">
        <div class="row">
            <div class="col-lg-7 col-md-5 col-sm-12">
                <h2><?php echo $patientId ? 'Edit' : 'Add'; ?> Patient
                <small class="text-muted">Fill in the clinical details below</small>
                </h2>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <?php if(isset($status_msg)): ?>
            <div class="alert alert-<?php echo $type_msg == 'success' ? 'success' : 'danger'; ?>">
                <?php echo $status_msg; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="row clearfix">
                <div class="col-lg-12 col-md-12 col-sm-12">
                    <div class="card">
                        <div class="header">
                            <h2><strong>Basic</strong> Information <small>Patient profile</small></h2>
                        </div>
                        <div class="body">
                            <div class="row clearfix">
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <input type="text" name="patientId" class="form-control" placeholder="Patient ID" value="<?php echo htmlspecialchars($patientId); ?>" <?php echo $patientId ? 'readonly' : 'required'; ?> style="background: #e9ecef;">
                                        <?php if($patientId) echo '<input type="hidden" name="editPatient" value="'.htmlspecialchars($patientId).'">'; ?>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <input type="text" name="phoneNo" class="form-control" placeholder="Phone Number" value="<?php echo htmlspecialchars($phoneNo); ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row clearfix">
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <input type="email" name="email" class="form-control" placeholder="Email Address" value="<?php echo htmlspecialchars($email); ?>" required>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="form-group">
                                        <input type="number" name="age" class="form-control" placeholder="Age" value="<?php echo htmlspecialchars($age); ?>" required>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <select name="gender" class="form-control show-tick" required>
                                        <option value="">- Gender -</option>
                                        <option value="Male" <?php if($gender == 'Male') echo 'selected'; ?>>Male</option>
                                        <option value="Female" <?php if($gender == 'Female') echo 'selected'; ?>>Female</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row clearfix">
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <textarea name="medicalHistory" rows="4" class="form-control no-resize" placeholder="Patients' family history, lifestyle, and other medical conditions..."><?php echo htmlspecialchars($medicalHistory); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="header">
                            <h2><strong>Registration</strong> Information <small>Associate Doctor to Patient...</small></h2>
                        </div>
                        <div class="body">
                            <div class="row clearfix">
                                <div class="col-sm-6">
                                    <select name="AssociatedDoctors[]" class="form-control show-tick" data-live-search="true">
                                        <option value="">-- Select Doctor --</option>
                                        <?php foreach($doctors_list as $doc): ?>
                                            <option value="<?php echo $doc['userID']; ?>" <?php echo ($assignedDoctorID == $doc['userID']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($doc['full_name'] . " (" . $doc['specialization'] . ")"); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-sm-6">
                                    <select name="mac_address" class="form-control show-tick" data-live-search="true">
                                        <option value="">-- Link IoT Device --</option>
                                        <?php foreach($available_devices as $dev): ?>
                                            <option value="<?php echo $dev['mac_address']; ?>" <?php echo ($linked_mac_address == $dev['mac_address']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($dev['mac_address'] . " - " . $dev['model']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row clearfix m-t-20">
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <input type="text" name="name" class="form-control" placeholder="Patient Name (Real Name)" value="<?php echo htmlspecialchars($name); ?>" required>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <input type="text" name="ward_no" class="form-control" placeholder="Ward# / Staff Name" value="<?php echo htmlspecialchars($wardNo); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="row clearfix">
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <input type="text" name="Date" class="form-control" value="<?php echo htmlspecialchars($date); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="row clearfix m-t-20">
                                <div class="col-sm-12">
                                    <button type="submit" class="btn btn-primary btn-round">Submit</button>
                                    <a href="patients.php" class="btn btn-default btn-round btn-simple">Go back to patients list</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<script src="../assets/bundles/libscripts.bundle.js"></script>
<script src="../assets/bundles/vendorscripts.bundle.js"></script>
<script src="../assets/bundles/mainscripts.bundle.js"></script>
</body>
</html>
