<?php 
session_start();
include("../config/DB_Config.php");

if (!isset($_SESSION["user_type"]) || $_SESSION["user_type"] !== "admin") {
    header("Location: index.php?status=Access is denied&type=error");
    exit();
}

$userID = $_GET['id'] ?? null;
if (!$userID) {
    header("Location: doctors.php?status=Invalid Doctor ID&type=error");
    exit();
}

// Ensure profile_picture column exists
try {
    $conn->exec('ALTER TABLE "doctorProfile" ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(500)');
} catch (PDOException $e) {}

// Fetch current data
try {
    $stmt = $conn->prepare("SELECT u.username, u.email, COALESCE(p.profile_picture, '') as profile_picture, p.* FROM users u LEFT JOIN \"doctorProfile\" p ON u.\"userID\" = p.\"userID\" WHERE u.\"userID\" = ? AND u.role = 'doctor'");
    $stmt->execute([$userID]);
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$doctor) {
        header("Location: doctors.php?status=Doctor not found&type=error");
        exit();
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['full_name'];
    $specialization = $_POST['specialization'];
    $phone = $_POST['phone_number'];
    $email = $_POST['email'];
    $description = $_POST['description'];
    
    try {
        $conn->beginTransaction();
        
        // Update User Email
        $stmt_u = $conn->prepare("UPDATE users SET email = ? WHERE \"userID\" = ?");
        $stmt_u->execute([$email, $userID]);
        
        // Check if profile exists, if not create one, else update
        $stmt_check = $conn->prepare("SELECT 1 FROM \"doctorProfile\" WHERE \"userID\" = ?");
        $stmt_check->execute([$userID]);
        
        if ($stmt_check->fetch()) {
            $stmt_p = $conn->prepare("UPDATE \"doctorProfile\" SET full_name = ?, specialization = ?, phone_number = ?, description = ? WHERE \"userID\" = ?");
            $stmt_p->execute([$full_name, $specialization, $phone, $description, $userID]);
        } else {
            $stmt_p = $conn->prepare("INSERT INTO \"doctorProfile\" (\"userID\", full_name, specialization, phone_number, description) VALUES (?, ?, ?, ?, ?)");
            $stmt_p->execute([$userID, $full_name, $specialization, $phone, $description]);
        }
        
        $conn->commit();
        header("Location: doctors.php?status=Doctor updated successfully&type=success");
        exit();
    } catch (PDOException $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        $error = $e->getMessage();
    }
}
?>
<!doctype html>
<html class="no-js " lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
<title>Edit Doctor - CUST Digihealth</title>
<link rel="icon" href="favicon.ico" type="image/x-icon">
<link rel="stylesheet" href="../assets/plugins/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/color_skins.css">
<!-- Mobile CSS Fix --><link rel="stylesheet" href="../assets/css/mobile.css">
</head>
<body class="theme-cyan">
<nav class="navbar p-l-5 p-r-5"><?php include("top_nav.php") ?></nav>
<aside id="leftsidebar" class="sidebar">
    <div class="menu">
        <ul class="list">
            <li><a href="dashboard.php"><i class="zmdi zmdi-home"></i><span>Dashboard</span></a></li>            
            <li class="active open"><a href="doctors.php"><i class="zmdi zmdi-account-add"></i><span>Doctors List</span> </a></li>
        </ul>
    </div>
</aside>

<section class="content">
    <div class="block-header">
        <div class="row">
            <div class="col-lg-5 col-md-5 col-sm-12"><h2>Edit Doctor Profile</h2></div>            
        </div>
    </div>
    <div class="container-fluid">
        <div class="row clearfix">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="card">
                    <div class="body">
                        <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
                        <form method="POST">
                            <div class="row clearfix">
                                <div class="col-sm-12 text-center m-b-20">
                                    <div style="position:relative;display:inline-block;">
                                        <img id="doc-profile-img" 
                                             src="<?php echo !empty($doctor['profile_picture']) ? htmlspecialchars($doctor['profile_picture']) : '../assets/images/profile_av.jpg'; ?>" 
                                             style="width:120px;height:120px;border-radius:50%;object-fit:cover;border:3px solid #00bcd4;" alt="Profile">
                                        <label for="doc-profile-input" style="position:absolute;bottom:5px;right:5px;background:#00bcd4;border-radius:50%;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;border:2px solid #fff;" title="Change Photo">
                                            <i class="zmdi zmdi-camera" style="color:#fff;"></i>
                                        </label>
                                        <input type="file" id="doc-profile-input" accept="image/*" style="display:none;">
                                    </div>
                                    <div id="doc-img-status" class="m-t-5" style="font-size:12px;"></div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label>Full Name</label>
                                        <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($doctor['full_name'] ?? $doctor['username']) ?>" required>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label>Specialization</label>
                                        <input type="text" class="form-control" name="specialization" value="<?php echo htmlspecialchars($doctor['specialization'] ?? 'General Practitioner') ?>" required>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label>Email</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($doctor['email']) ?>" required>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label>Phone Number</label>
                                        <input type="text" class="form-control" name="phone_number" value="<?php echo htmlspecialchars($doctor['phone_number'] ?? 'N/A') ?>">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>Description</label>
                                        <textarea rows="4" class="form-control no-resize" name="description"><?php echo htmlspecialchars($doctor['description'] ?? '') ?></textarea>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <button type="submit" class="btn btn-primary btn-round">Save Changes</button>
                                    <a href="doctors.php" class="btn btn-default btn-round btn-simple">Cancel</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<script src="../assets/bundles/libscripts.bundle.js"></script>
<script>
document.getElementById('doc-profile-input').addEventListener('change', function(e) {
    var file = e.target.files[0];
    if (!file) return;

    var status = document.getElementById('doc-img-status');
    var preview = document.getElementById('doc-profile-img');

    if (file.size > 5 * 1024 * 1024) {
        status.innerHTML = '<span class="text-danger">Max 5MB</span>';
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
            status.innerHTML = '<span class="text-success">Updated!</span>';
            setTimeout(function() { status.innerHTML = ''; }, 3000);
        } else {
            status.innerHTML = '<span class="text-danger">' + (data.message || 'Failed') + '</span>';
        }
    })
    .catch(function() {
        status.innerHTML = '<span class="text-danger">Network error</span>';
    });
});
</script>
</body>
</html>