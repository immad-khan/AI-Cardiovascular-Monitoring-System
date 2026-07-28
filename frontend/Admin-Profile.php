<?php
session_start();
include("../config/DB_Config.php");

if (!isset($_SESSION["user_type"]) || $_SESSION["user_type"] !== "admin") {
    header("Location: index.php?status=Access is denied&type=error");
    exit();
}

$userId = $_SESSION['user_id'];

// Ensure profile_picture column exists
try {
    $conn->exec('ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(500)');
} catch (PDOException $e) {}

// Fetch admin info
try {
    $stmt = $conn->prepare('SELECT *, COALESCE(profile_picture, \'\') as profile_picture FROM users WHERE "userID" = ?');
    $stmt->execute([$userId]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $admin = ['username' => $_SESSION['username'], 'email' => '', 'profile_picture' => ''];
}

// Count stats
try {
    $doctor_count = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'doctor'")->fetchColumn();
    $patient_count = $conn->query("SELECT COUNT(*) FROM patients WHERE \"isActive\" = TRUE")->fetchColumn();
    $device_count = $conn->query("SELECT COUNT(*) FROM monitoring_devices")->fetchColumn();
} catch (PDOException $e) {
    $doctor_count = $patient_count = $device_count = 0;
}
?>
<!doctype html>
<html class="no-js" lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
<title>Admin Profile - CUST Digihealth</title>
<link rel="icon" href="favicon.ico" type="image/x-icon">
<link rel="stylesheet" href="../assets/plugins/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/color_skins.css">
<style>
    .profile-header { background: linear-gradient(135deg, #00bcd4 0%, #0097a7 100%); color: #fff; padding: 30px 0; }
    .profile-img { width: 150px; height: 150px; border: 5px solid #fff; border-radius: 50%; object-fit: cover; }
    .upload-overlay { position: absolute; bottom: 8px; right: 8px; background: #00bcd4; border-radius: 50%; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; cursor: pointer; border: 2px solid #fff; transition: background 0.2s; }
    .upload-overlay:hover { background: #0097a7; }
    .stat-card { text-align: center; padding: 20px; }
    .stat-card h3 { font-size: 2rem; margin: 10px 0 5px; }
</style>
<!-- Mobile CSS Fix --><link rel="stylesheet" href="../assets/css/mobile.css">
</head>
<body class="theme-cyan">
<nav class="navbar p-l-5 p-r-5"><?php include("top_nav.php") ?></nav>
<aside id="leftsidebar" class="sidebar"><?php include("admin_sidebar.php") ?></aside>

<section class="content profile-page">
    <div class="block-header">
        <div class="row">
            <div class="col-lg-7 col-md-5 col-sm-12">
                <h2>Admin Profile</h2>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row clearfix">
            <div class="col-lg-4 col-md-12">
                <div class="card member-card profile-header">
                    <div class="body text-center">
                        <div style="position:relative;display:inline-block;">
                            <img id="admin-profile-img" 
                                 src="<?php echo !empty($admin['profile_picture']) ? htmlspecialchars($admin['profile_picture']) : '../assets/images/admin.png'; ?>" 
                                 class="profile-img m-b-15" alt="Profile">
                            <label for="admin-profile-input" class="upload-overlay" title="Change Profile Photo">
                                <i class="zmdi zmdi-camera" style="color:#fff;font-size:18px;"></i>
                            </label>
                            <input type="file" id="admin-profile-input" accept="image/*" style="display:none;">
                        </div>
                        <div id="admin-img-status" style="font-size:12px;" class="m-b-10"></div>
                        <h4 class="m-t-10"><?php echo htmlspecialchars($admin['username']); ?></h4>
                        <p class="text-white">System Administrator</p>
                        <hr>
                        <ul class="list-unstyled text-left p-l-20 p-r-20">
                            <li><i class="zmdi zmdi-email m-r-10"></i><?php echo htmlspecialchars($admin['email'] ?? 'N/A'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-lg-8 col-md-12">
                <div class="row clearfix">
                    <div class="col-lg-4 col-md-4 col-sm-6">
                        <div class="card stat-card">
                            <i class="zmdi zmdi-account text-info" style="font-size:2rem;"></i>
                            <h3><?php echo $doctor_count; ?></h3>
                            <p class="text-muted">Doctors</p>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-4 col-sm-6">
                        <div class="card stat-card">
                            <i class="zmdi zmdi-account-o text-success" style="font-size:2rem;"></i>
                            <h3><?php echo $patient_count; ?></h3>
                            <p class="text-muted">Patients</p>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-4 col-sm-6">
                        <div class="card stat-card">
                            <i class="zmdi zmdi-cast-connected text-warning" style="font-size:2rem;"></i>
                            <h3><?php echo $device_count; ?></h3>
                            <p class="text-muted">IoT Devices</p>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="header"><h2><strong>Account</strong> Details</h2></div>
                    <div class="body">
                        <table class="table">
                            <tr><td><strong>Username</strong></td><td><?php echo htmlspecialchars($admin['username']); ?></td></tr>
                            <tr><td><strong>Email</strong></td><td><?php echo htmlspecialchars($admin['email'] ?? 'N/A'); ?></td></tr>
                            <tr><td><strong>Role</strong></td><td><span class="badge badge-info">Administrator</span></td></tr>
                            <tr><td><strong>Account Status</strong></td><td><span class="badge badge-success">Active</span></td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="../assets/bundles/libscripts.bundle.js"></script>
<script>
document.getElementById('admin-profile-input').addEventListener('change', function(e) {
    var file = e.target.files[0];
    if (!file) return;

    var status = document.getElementById('admin-img-status');
    var preview = document.getElementById('admin-profile-img');

    if (file.size > 5 * 1024 * 1024) {
        status.innerHTML = '<span class="text-danger">File too large (max 5MB)</span>';
        return;
    }

    var reader = new FileReader();
    reader.onload = function(ev) { preview.src = ev.target.result; };
    reader.readAsDataURL(file);

    var formData = new FormData();
    formData.append('profile_image', file);

    status.innerHTML = '<span class="text-info">Uploading...</span>';

    fetch('../api/upload_profile_image.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            preview.src = data.url;
            status.innerHTML = '<span class="text-success">Profile image updated!</span>';
            setTimeout(function() { status.innerHTML = ''; }, 3000);
        } else {
            status.innerHTML = '<span class="text-danger">' + (data.message || 'Upload failed') + '</span>';
        }
    })
    .catch(function() {
        status.innerHTML = '<span class="text-danger">Network error</span>';
    });
});
</script>
</body>
</html>
