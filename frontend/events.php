<?php
session_start();
include("../config/DB_Config.php");

if (!isset($_SESSION["user_type"]) || $_SESSION["user_type"] !== "admin") {
    header("Location: index.php?status=access_denied&type=error");
    exit();
}

// Handle Appointment Assignment
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_appointment'])) {
    $patient_id = $_POST['patient_id'];
    $doctor_id = $_POST['doctor_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $notes = $_POST['notes'];

    try {
        $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, notes, status) VALUES (?, ?, ?, ?, ?, 'Scheduled')");
        $stmt->execute([$patient_id, $doctor_id, $appointment_date, $appointment_time, $notes]);
        $message = "<div class='alert alert-success'>Appointment assigned successfully!</div>";
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

// Fetch Data for Selects
$patients = $conn->query("SELECT \"patientID\", name FROM patients WHERE \"isActive\" = TRUE ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$doctors = $conn->query("SELECT u.\"userID\", COALESCE(p.full_name, u.username) as name FROM users u LEFT JOIN \"doctorProfile\" p ON u.\"userID\" = p.\"userID\" WHERE u.role = 'doctor' AND u.\"isActive\" = TRUE")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html class="no-js " lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
<title>Doctor Schedule - CUST Digihealth</title>
<link rel="icon" href="favicon.ico" type="image/x-icon">
<link rel="stylesheet" href="../assets/plugins/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../assets/plugins/fullcalendar/fullcalendar.min.css">
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/color_skins.css">
</head>
<body class="theme-cyan">
<div class="page-loader-wrapper">
    <div class="loader">
        <div class="m-t-30"><img class="zmdi-hc-spin" src="../assets/images/logo.svg" width="48" height="48" alt="Oreo"></div>
        <p>Please wait...</p>        
    </div>
</div>
<div class="overlay"></div>
<nav class="navbar p-l-5 p-r-5">
    <?php include("top_nav.php") ?>
</nav>

<aside id="leftsidebar" class="sidebar">
    <?php include("left_sidebar.php") ?>
</aside>

<?php include("rightsidebar.php") ?>

<section class="content page-calendar">
    <div class="block-header">
        <div class="row">
            <div class="col-lg-7 col-md-5 col-sm-12">
                <h2>Doctor Schedule
                <small>Manage appointments and hospital events</small>
                </h2>
            </div>
            <div class="col-lg-5 col-md-7 col-sm-12">
                <ul class="breadcrumb float-md-right">
                    <li class="breadcrumb-item"><a href="dashboard.php"><i class="zmdi zmdi-home"></i> Home</a></li>
                    <li class="breadcrumb-item active">Schedule</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="container-fluid">        
        <?php echo $message; ?>
        <div class="row">
            <div class="col-md-12 col-lg-4 col-xl-4">
                <div class="card">
                    <div class="body">
                        <button type="button" class="btn btn-primary btn-round btn-block waves-effect" data-toggle="modal" data-target="#addevent">
                            <i class="zmdi zmdi-calendar-check"></i> Assign Appointment
                        </button>
                    </div>
                </div>
                <div class="card">
                    <div class="header">
                        <h2><strong>Recent</strong> Assignments</h2>
                    </div>
                    <div class="body">
                        <div class="row">
                            <div class="col-12">
                                <ul class="list-unstyled">
                                    <?php
                                    $recent = $conn->query("SELECT a.*, p.name as patient_name, COALESCE(dp.full_name, u.username) as doctor_name 
                                                            FROM appointments a 
                                                            JOIN patients p ON a.patient_id = p.\"patientID\"
                                                            JOIN users u ON a.doctor_id = u.\"userID\"
                                                            LEFT JOIN \"doctorProfile\" dp ON u.\"userID\" = dp.\"userID\"
                                                            ORDER BY a.created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
                                    if(empty($recent)) {
                                        echo "<li class='text-muted'>No recent appointments</li>";
                                    }
                                    foreach($recent as $apt) {
                                        $statusClass = $apt['status'] == 'Completed' ? 'badge-success' : 'badge-info';
                                        echo "<li class='mb-3 p-2 border-bottom'>
                                                <div class='d-flex justify-content-between'>
                                                    <h6 class='mb-0 text-primary'>".htmlspecialchars($apt['patient_name'])."</h6>
                                                    <span class='badge $statusClass text-white'>".date('h:i A', strtotime($apt['appointment_time']))."</span>
                                                </div>
                                                <p class='text-muted small mb-1'>Assigned to: ".htmlspecialchars($apt['doctor_name'])."</p>
                                                <small><i class='zmdi zmdi-calendar'></i> ".date('d M Y', strtotime($apt['appointment_date']))."</small>
                                              </li>";
                                    }
                                    ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-12 col-lg-8 col-xl-8">
                <div class="card">
                    <div class="header d-flex justify-content-between align-items-center">
                        <h2><strong>Calendar</strong> View</h2>
                        <div class="btn-group">
                            <button class="btn btn-default btn-sm waves-effect" id="change-view-today">Today</button>
                            <button class="btn btn-default btn-sm waves-effect" id="change-view-day">Day</button>
                            <button class="btn btn-default btn-sm waves-effect" id="change-view-week">Week</button>
                            <button class="btn btn-default btn-sm waves-effect" id="change-view-month">Month</button>
                        </div>
                    </div>
                    <div class="body">
                        <div id="calendar"></div>
                    </div>
                </div>
            </div>
        </div>        
    </div>
</section>
<!-- Appointment Assignment Modal -->
<div class="modal fade" id="addevent" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-md modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius:15px; overflow:hidden;">
            <div class="modal-header border-0 bg-light p-3">
                <h5 class="title mb-0" style="color:#333; font-weight:600;">Assign Doctor Appointment</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="" method="POST">
                <div class="modal-body p-4">
                    <div class="form-group mb-4">
                        <label class="font-weight-bold text-dark mb-1 ml-1" style="font-size:0.9rem;">Select Patient</label>
                        <select name="patient_id" class="form-control" style="border:none; border-bottom: 2px solid #f1f1f1; border-radius:0; height: 45px; background:transparent; box-shadow:none;" required>
                            <option value="">-- Choose Patient --</option>
                            <?php foreach($patients as $p): ?>
                                <option value="<?php echo $p['patientID']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group mb-4">
                        <label class="font-weight-bold text-dark mb-1 ml-1" style="font-size:0.9rem;">Select Doctor</label>
                        <select name="doctor_id" class="form-control" style="border:none; border-bottom: 2px solid #f1f1f1; border-radius:0; height: 45px; background:transparent; box-shadow:none;" required>
                            <option value="">-- Choose Doctor --</option>
                            <?php foreach($doctors as $d): ?>
                                <option value="<?php echo $d['userID']; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="form-group col-6 mb-4">
                            <label class="font-weight-bold text-dark mb-1 ml-1" style="font-size:0.9rem;">Date</label>
                            <input type="date" name="appointment_date" class="form-control" style="border:none; border-bottom: 2px solid #f1f1f1; border-radius:0; height: 45px; background:transparent; box-shadow:none;" required>
                        </div>
                        <div class="form-group col-6 mb-4">
                            <label class="font-weight-bold text-dark mb-1 ml-1" style="font-size:0.9rem;">Time</label>
                            <input type="time" name="appointment_time" class="form-control" style="border:none; border-bottom: 2px solid #f1f1f1; border-radius:0; height: 45px; background:transparent; box-shadow:none;" required>
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label class="font-weight-bold text-dark mb-1 ml-1" style="font-size:0.9rem;">Additional Notes</label>
                        <textarea name="notes" class="form-control no-resize" style="border:none; border-bottom: 2px solid #f1f1f1; border-radius:0; background:transparent; box-shadow:none;" rows="3" placeholder="e.g. Heart checkup, ECG monitoring..."></textarea>
                    </div>       
                </div>
                <div class="modal-footer border-0 p-3 bg-light">
                    <button type="button" class="btn btn-simple btn-round waves-effect" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="assign_appointment" class="btn btn-primary btn-round px-4 shadow-sm" style="background:#00cfd1; border:none; font-weight:600;">Assign Now</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Jquery Core Js --> 
<script src="../assets/bundles/libscripts.bundle.js"></script> <!-- Lib Scripts Plugin Js --> 
<script src="../assets/bundles/vendorscripts.bundle.js"></script> <!-- Lib Scripts Plugin Js --> 

<script src="../assets/bundles/fullcalendarscripts.bundle.js"></script><!--/ calender javascripts --> 

<script src="../assets/bundles/mainscripts.bundle.js"></script><!-- Custom Js --> 
<script src="../assets/js/pages/calendar/calendar.js"></script>
</body>

<!-- Mirrored from hms.cognisun.net/oreo/html/light/events.php by HTTrack Website Copier/3.x [XR&CO'2014], Sun, 06 Oct 2024 17:19:26 GMT -->
</html>