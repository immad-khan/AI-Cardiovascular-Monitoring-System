<?php 
include("../config/DB_Config.php");
session_start();

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: index.php?status=access_denied&type=error");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: doctors.php?status=invalid_id&type=error");
    exit();
}

$doctor_id = $_GET['id'];

try {
    // Fetch Doctor Details
    $sql_doc = "SELECT u.\"userID\", u.username, u.email, 
                       COALESCE(p.full_name, u.username) as full_name, 
                       COALESCE(p.specialization, 'General Practitioner') as specialization, 
                       COALESCE(p.phone_number, 'N/A') as phone_number, 
                       COALESCE(p.profile_picture, '../assets/images/sm/avatar1.jpg') as profile_picture, 
                       COALESCE(p.description, 'No description available.') as description,
                       p.website_url
                FROM users u
                LEFT JOIN \"doctorProfile\" p ON u.\"userID\" = p.\"userID\"
                WHERE u.\"userID\" = ? AND u.role = 'doctor'";
    $stmt_doc = $conn->prepare($sql_doc);
    $stmt_doc->execute([$doctor_id]);
    $doctor = $stmt_doc->fetch(PDO::FETCH_ASSOC);

    if (!$doctor) {
        header("Location: doctors.php?status=doctor_not_found&type=error");
        exit();
    }

    // Fetch Doctor's Appointments
    $sql_apt = "SELECT a.*, p.name as patient_name, p.\"patientID\"
                FROM appointments a
                JOIN patients p ON a.patient_id = p.\"patientID\"
                WHERE a.doctor_id = ?
                ORDER BY a.appointment_date DESC, a.appointment_time DESC";
    $stmt_apt = $conn->prepare($sql_apt);
    $stmt_apt->execute([$doctor_id]);
    $appointments = $stmt_apt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!doctype html>
<html class="no-js " lang="en">
<head>
    <meta charset="utf-8">
    <title>Doctor Profile - <?php echo htmlspecialchars($doctor['full_name']); ?></title>
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="../assets/plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/color_skins.css">
    <style>
        .profile-header { background: #00cfd1; color: #fff; padding: 30px 0; }
        .profile-img { width: 150px; height: 150px; border: 5px solid #fff; border-radius: 50%; object-fit: cover; }
    </style>
</head>
<body class="theme-cyan">
<nav class="navbar p-l-5 p-r-5"><?php include("top_nav.php") ?></nav>
<aside id="leftsidebar" class="sidebar"><?php include("left_sidebar.php") ?></aside>

<section class="content profile-page">
    <div class="block-header">
        <div class="row">
            <div class="col-lg-7 col-md-5 col-sm-12">
                <h2>Doctor Profile</h2>
            </div>
            <div class="col-lg-5 col-md-7 col-sm-12 text-right">
                <a href="doctors.php" class="btn btn-white btn-round">Back to List</a>
                <a href="edit-doctor.php?id=<?php echo $doctor['userID']; ?>" class="btn btn-primary btn-round">Edit Profile</a>
            </div>
        </div>
    </div>
    
    <div class="container-fluid">
        <div class="row clearfix">
            <div class="col-lg-4 col-md-12">
                <div class="card member-card profile-header">
                    <div class="body text-center">
                        <img src="<?php echo htmlspecialchars($doctor['profile_picture']); ?>" class="profile-img m-b-15" alt="profile">
                        <h4 class="m-t-10"><?php echo htmlspecialchars($doctor['full_name']); ?></h4>
                        <p class="text-white"><?php echo htmlspecialchars($doctor['specialization']); ?></p>
                        <hr>
                        <ul class="list-unstyled text-left p-l-20 p-r-20">
                            <li><i class="zmdi zmdi-email m-r-10"></i><?php echo htmlspecialchars($doctor['email']); ?></li>
                            <li><i class="zmdi zmdi-phone m-r-10"></i><?php echo htmlspecialchars($doctor['phone_number']); ?></li>
                            <?php if($doctor['website_url']): ?>
                                <li><i class="zmdi zmdi-globe m-r-10"></i><?php echo htmlspecialchars($doctor['website_url']); ?></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
                <div class="card">
                    <div class="header">
                        <h2><strong>About</strong> Doctor</h2>
                    </div>
                    <div class="body">
                        <p><?php echo nl2br(htmlspecialchars($doctor['description'])); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-lg-8 col-md-12">
                <div class="card">
                    <div class="header">
                        <h2><strong>Assigned</strong> Appointments</h2>
                    </div>
                    <div class="body">
                        <div class="table-responsive">
                            <table class="table table-hover m-b-0">
                                <thead>
                                    <tr>
                                        <th>Patient Name</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Status</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($appointments)): ?>
                                        <tr><td colspan="5" class="text-center">No appointments found for this doctor.</td></tr>
                                    <?php else: ?>
                                        <?php foreach($appointments as $apt): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($apt['patient_name']); ?></strong></td>
                                                <td><?php echo date('d M Y', strtotime($apt['appointment_date'])); ?></td>
                                                <td><?php echo date('h:i A', strtotime($apt['appointment_time'])); ?></td>
                                                <td><span class="badge <?php echo $apt['status'] == 'Completed' ? 'badge-success' : 'badge-info'; ?>"><?php echo $apt['status']; ?></span></td>
                                                <td><small><?php echo htmlspecialchars($apt['notes'] ?: 'No notes'); ?></small></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<script src="../assets/bundles/libscripts.bundle.js"></script>
<script src="../assets/bundles/mainscripts.bundle.js"></script>
</body>
</html>