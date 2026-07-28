<?php
session_start();
include("../config/DB_Config.php");

if (!isset($_SESSION["user_type"]) || $_SESSION["user_type"] !== "doctor") {
    header("Location: index.php?status=Access is denied&type=error");
    exit();
}

$userId = $_SESSION['user_id'];

// Fetch doctor profile + user data
try {
    $stmt = $conn->prepare("
        SELECT u.username, u.email,
               COALESCE(p.full_name, u.username) as full_name,
               COALESCE(p.specialization, 'General Practitioner') as specialization,
               COALESCE(p.phone_number, 'N/A') as phone_number,
               COALESCE(p.profile_picture, '') as profile_picture,
               COALESCE(p.description, '') as description,
               COALESCE(p.website_url, '') as website_url
        FROM users u
        LEFT JOIN \"doctorProfile\" p ON u.\"userID\" = p.\"userID\"
        WHERE u.\"userID\" = ? AND u.role = 'doctor'
    ");
    $stmt->execute([$userId]);
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doctor) {
        die("<div class='container mt-5 alert alert-warning'>Doctor profile not found.</div>");
    }
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// Count assigned patients
$patientCount = 0;
try {
    $pstmt = $conn->prepare('SELECT COUNT(*) FROM patients WHERE "assignedDoctorID" = ?');
    $pstmt->execute([$userId]);
    $patientCount = (int)$pstmt->fetchColumn();
} catch (PDOException $e) {}

// Fetch assigned patients list
$assignedPatients = [];
try {
    $apstmt = $conn->prepare('SELECT "patientID", name, age, gender, phone_no FROM patients WHERE "assignedDoctorID" = ? ORDER BY name');
    $apstmt->execute([$userId]);
    $assignedPatients = $apstmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}
?>
<!doctype html>
<html class="no-js" lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
<title>My Profile - Digihealth</title>
<link rel="icon" href="favicon.ico" type="image/x-icon">
<link rel="stylesheet" href="../assets/plugins/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/color_skins.css">
<style>
    .profile-cover { background: linear-gradient(135deg, #1565c0, #0d47a1); padding: 40px 0 20px; color: #fff; text-align: center; }
    .profile-avatar { width: 140px; height: 140px; border-radius: 50%; border: 5px solid #fff; object-fit: cover; margin-top: -70px; position: relative; z-index: 2; box-shadow: 0 4px 15px rgba(0,0,0,0.15); }
    .avatar-wrapper { position: relative; display: inline-block; }
    .avatar-upload-btn { position: absolute; bottom: 8px; right: 8px; background: #1565c0; border-radius: 50%; width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; cursor: pointer; border: 2px solid #fff; transition: background 0.2s; }
    .avatar-upload-btn:hover { background: #0d47a1; }
    .info-label { font-size: 11px; text-transform: uppercase; color: #999; margin-bottom: 2px; font-weight: 600; letter-spacing: 0.5px; }
    .info-value { font-size: 15px; color: #333; margin-bottom: 12px; }
    .stat-mini { text-align: center; padding: 15px 10px; background: #f8f9fa; border-radius: 10px; }
    .stat-mini h4 { margin: 0; font-size: 1.4rem; }
    .stat-mini small { color: #999; font-size: 11px; }
    .patient-chip { display: inline-flex; align-items: center; gap: 6px; background: #e3f2fd; border: 1px solid #bbdefb; border-radius: 20px; padding: 5px 12px 5px 8px; font-size: 12px; color: #1565c0; margin: 3px; }
    .patient-chip .chip-dot { width: 8px; height: 8px; border-radius: 50%; background: #1565c0; }
</style>
<!-- Mobile CSS Fix --><link rel="stylesheet" href="../assets/css/mobile.css">
</head>
<body class="theme-cyan">
<nav class="navbar p-l-5 p-r-5">
    <?php include("top_nav.php") ?>
</nav>
<aside id="leftsidebar" class="sidebar">
    <?php include("doctor_sidebar.php") ?>
</aside>

<section class="content">
    <div class="profile-cover">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="avatar-wrapper">
                        <img id="doc-profile-img"
                             src="<?php echo !empty($doctor['profile_picture']) ? htmlspecialchars($doctor['profile_picture']) : '../assets/images/profile_av.jpg'; ?>"
                             class="profile-avatar" alt="Profile">
                        <label for="doc-profile-input" class="avatar-upload-btn" title="Change Photo">
                            <i class="zmdi zmdi-camera" style="color:#fff;font-size:16px;"></i>
                        </label>
                        <input type="file" id="doc-profile-input" accept="image/*" style="display:none;">
                    </div>
                    <h3 class="m-t-15" style="font-weight:600;">Dr. <?php echo htmlspecialchars($doctor['full_name']); ?></h3>
                    <p style="opacity:0.85;margin:0;"><?php echo htmlspecialchars($doctor['specialization']); ?></p>
                    <div id="doc-profile-status" style="font-size:12px;" class="m-t-5"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid m-t-20">
        <div class="row clearfix">
            <!-- Left Column: Account + Personal Info -->
            <div class="col-lg-4 col-md-12">
                <div class="card">
                    <div class="header"><h2><strong>Account</strong> Details</h2></div>
                    <div class="body">
                        <div class="info-label">Username</div>
                        <div class="info-value"><?php echo htmlspecialchars($doctor['username']); ?></div>

                        <div class="info-label">Email</div>
                        <div class="info-value"><?php echo htmlspecialchars($doctor['email']); ?></div>

                        <div class="info-label">Role</div>
                        <div class="info-value">
                            <span class="badge badge-info" style="font-size:12px;padding:5px 10px;">Doctor</span>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="header"><h2><strong>Personal</strong> Information</h2></div>
                    <div class="body">
                        <div id="profile-edit-status" style="margin-bottom:12px;"></div>
                        <div class="form-group">
                            <label class="info-label" for="edit_full_name">Full Name</label>
                            <input type="text" class="form-control" id="edit_full_name"
                                   value="<?php echo htmlspecialchars($doctor['full_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="info-label" for="edit_specialization">Specialization</label>
                            <input type="text" class="form-control" id="edit_specialization"
                                   value="<?php echo htmlspecialchars($doctor['specialization']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="info-label" for="edit_phone">Phone</label>
                            <input type="text" class="form-control" id="edit_phone"
                                   value="<?php echo htmlspecialchars($doctor['phone_number']); ?>">
                        </div>
                        <div class="form-group">
                            <label class="info-label" for="edit_description">About</label>
                            <textarea class="form-control no-resize" id="edit_description" rows="4"
                                      placeholder="Tell patients about yourself..."><?php echo htmlspecialchars($doctor['description']); ?></textarea>
                        </div>
                        <button type="button" class="btn btn-primary btn-round" id="saveProfileBtn" onclick="saveProfile()">
                            <i class="zmdi zmdi-check m-r-5"></i>Save Changes
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right Column: Stats + Assigned Patients -->
            <div class="col-lg-8 col-md-12">
                <!-- Stats Row -->
                <div class="row clearfix m-b-20">
                    <div class="col-lg-4 col-md-4 col-sm-4">
                        <div class="card">
                            <div class="body text-center">
                                <div class="stat-mini">
                                    <i class="zmdi zmdi-account-o" style="font-size:1.5rem;color:#1565c0;"></i>
                                    <h4 class="m-t-5" style="color:#1565c0;"><?php echo $patientCount; ?></h4>
                                    <small>Assigned Patients</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-4 col-sm-4">
                        <div class="card">
                            <div class="body text-center">
                                <div class="stat-mini">
                                    <i class="zmdi zmdi-comments" style="font-size:1.5rem;color:#00bcd4;"></i>
                                    <h4 class="m-t-5" style="color:#00bcd4;">Active</h4>
                                    <small>Chat Status</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-4 col-sm-4">
                        <div class="card">
                            <div class="body text-center">
                                <div class="stat-mini">
                                    <i class="zmdi zmdi-shield-check" style="font-size:1.5rem;color:#4caf50;"></i>
                                    <h4 class="m-t-5" style="color:#4caf50;">Active</h4>
                                    <small>Account Status</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Assigned Patients -->
                <div class="card">
                    <div class="header" style="background:#e3f2fd;">
                        <h2 style="color:#1565c0;"><i class="zmdi zmdi-account-multiple m-r-10"></i><strong>Assigned</strong> Patients</h2>
                    </div>
                    <div class="body">
                        <?php if (!empty($assignedPatients)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover m-b-0">
                                <thead>
                                    <tr>
                                        <th>Patient ID</th>
                                        <th>Name</th>
                                        <th>Age</th>
                                        <th>Gender</th>
                                        <th>Phone</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($assignedPatients as $p): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($p['patientID']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($p['name']); ?></td>
                                        <td><?php echo htmlspecialchars($p['age']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $p['gender'] === 'Female' ? 'danger' : 'info'; ?>">
                                                <?php echo htmlspecialchars($p['gender']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($p['phone_no'] ?? 'N/A'); ?></td>
                                        <td>
                                            <a href="Doctor-Chat.php?patientId=<?php echo urlencode($p['patientID']); ?>"
                                               class="btn btn-sm btn-info" title="Chat">
                                                <i class="zmdi zmdi-comments"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center p-t-20 p-b-20">
                            <i class="zmdi zmdi-account-o" style="font-size:2.5rem;color:#ccc;"></i>
                            <p class="text-muted m-t-10">No patients assigned yet.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row clearfix">
                    <div class="col-md-6">
                        <a href="Doctor-Chats.php" style="text-decoration:none;">
                            <div class="card" style="cursor:pointer;transition:transform 0.2s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='none'">
                                <div class="body text-center" style="padding:25px;">
                                    <i class="zmdi zmdi-comments" style="font-size:2.5rem;color:#00bcd4;"></i>
                                    <h5 class="m-t-10" style="color:#333;">Patient Chats</h5>
                                    <p class="text-muted" style="font-size:12px;">Message your assigned patients</p>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="patients.php" style="text-decoration:none;">
                            <div class="card" style="cursor:pointer;transition:transform 0.2s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='none'">
                                <div class="body text-center" style="padding:25px;">
                                    <i class="zmdi zmdi-account-multiple" style="font-size:2.5rem;color:#1565c0;"></i>
                                    <h5 class="m-t-10" style="color:#333;">My Patients</h5>
                                    <p class="text-muted" style="font-size:12px;">View full patient list & details</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="../assets/bundles/libscripts.bundle.js"></script>
<script>
function saveProfile() {
    var status = document.getElementById('profile-edit-status');
    var btn = document.getElementById('saveProfileBtn');
    var data = {
        full_name: document.getElementById('edit_full_name').value.trim(),
        specialization: document.getElementById('edit_specialization').value.trim(),
        phone_number: document.getElementById('edit_phone').value.trim(),
        description: document.getElementById('edit_description').value.trim()
    };

    if (!data.full_name || !data.specialization) {
        status.innerHTML = '<div class="alert alert-danger" style="padding:8px 12px;font-size:13px;">Name and specialization are required.</div>';
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="zmdi zmdi-spinner zmdi-hc-spin m-r-5"></i>Saving...';

    fetch('../api/update_doctor_profile.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
        credentials: 'same-origin'
    })
    .then(function(r) { return r.json(); })
    .then(function(result) {
        if (result.success) {
            status.innerHTML = '<div class="alert alert-success" style="padding:8px 12px;font-size:13px;">' + result.message + '</div>';
            // Update the cover name display
            var nameParts = data.full_name.split(' ');
            document.querySelector('.profile-cover h3').textContent = 'Dr. ' + data.full_name;
        } else {
            status.innerHTML = '<div class="alert alert-danger" style="padding:8px 12px;font-size:13px;">' + (result.message || 'Failed to save.') + '</div>';
        }
    })
    .catch(function() {
        status.innerHTML = '<div class="alert alert-danger" style="padding:8px 12px;font-size:13px;">Network error. Please try again.</div>';
    })
    .finally(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="zmdi zmdi-check m-r-5"></i>Save Changes';
        setTimeout(function() { status.innerHTML = ''; }, 4000);
    });
}

document.getElementById('doc-profile-input').addEventListener('change', function(e) {
    var file = e.target.files[0];
    if (!file) return;

    var status = document.getElementById('doc-profile-status');
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
