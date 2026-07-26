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
    <?php 
    if ($_SESSION['user_type'] === 'admin') {
        include("admin_sidebar.php");
    } elseif ($_SESSION['user_type'] === 'doctor') {
        include("doctor_sidebar.php");
    } else {
        include("patient_sidebar.php");
    }
    ?>
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
        <?php if(isset($_GET['status'])): ?>
            <div class="alert alert-<?php echo (isset($_GET['type']) && $_GET['type'] == 'error') ? 'danger' : 'success'; ?>">
                <?php echo htmlspecialchars($_GET['status']); ?>
            </div>
        <?php endif; ?>
        <div class="row clearfix">
            <div class="col-md-12">
                <div class="card patients-list">
                    <div class="header">
                        <h2><strong>Patients</strong> List</h2>
                        <ul class="header-dropdown">
                            <li>
                                <a href="add-patient.php" class="btn btn-primary btn-round btn-sm" style="color: white; margin-top: -5px;">+ Add Patient</a>
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
                                            <th>Gender</th>
                                            <th>Associated Doctors</th>
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

                                            $sql = "SELECT p.*, dp.full_name as doctor_name, dp.specialization,
                                                    (SELECT md.model || ' (' || md.mac_address || ')' 
                                                     FROM monitoring_devices md 
                                                     WHERE md.\"patientID\" = p.\"patientID\" LIMIT 1) as device_info
                                                    FROM patients p 
                                                    LEFT JOIN \"doctorProfile\" dp ON p.\"assignedDoctorID\" = dp.\"userID\"
                                                    WHERE p.\"isActive\" = TRUE $doctor_condition
                                                    ORDER BY p.date DESC";

                                            $stmt = $conn->prepare($sql);
                                            $stmt->execute($params);

                                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                $doctorDisplay = $row['doctor_name'] ? htmlspecialchars($row['doctor_name'] . " (" . $row['specialization'] . ")") : 'Unassigned';
                                                $deviceDisplay = $row['device_info'] ?? 'None';
                                                
                                                echo '<tr>
                                                    <td><a href="./Patient-Profile.php?patientId=' . htmlspecialchars($row['patientID']) . '" class="text-info"><strong>'.htmlspecialchars($row['patientID']).'</strong></a></td>
                                                    <td>' . htmlspecialchars($row['gender']) . '</td>
                                                    <td>' . $doctorDisplay . '</td>
                                                    <td>' . htmlspecialchars($deviceDisplay) . '</td>
                                                    <td>' . date('l d F Y - H:i', strtotime($row['date'])) . '</td>
                                                    <td>
                                                        <a href="./add-patient.php?patientId=' . htmlspecialchars($row['patientID']) . '" class="btn btn-sm btn-default">Edit</a>
                                                        <button class="btn btn-sm btn-danger" onclick="confirmDelete(\'' . htmlspecialchars($row['patientID']) . '\', \'' . htmlspecialchars($row['name']) . '\')">Delete</button>
                                                        <button class="btn btn-sm btn-warning" onclick="openPasswordModal(\'' . htmlspecialchars($row['patientID']) . '\', \'' . htmlspecialchars(addslashes($row['email'])) . '\', \'patient\')">Change Password</button>
                                                    </td>
                                                </tr>';
                                            }
                                        } catch (PDOException $e) {
                                            echo '<tr><td colspan="6" class="text-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
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

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius:12px;overflow:hidden;">
            <div class="modal-header" style="background:#ff5252;color:#fff;border:none;">
                <h5 class="modal-title"><i class="zmdi zmdi-alert-triangle m-r-5"></i> Delete Patient Account</h5>
                <button type="button" class="close" style="color:#fff;opacity:0.8;" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body text-center" style="padding:30px;">
                <i class="zmdi zmdi-account-circle" style="font-size:3rem;color:#ff5252;"></i>
                <h5 class="m-t-15">Are you sure you want to permanently delete this patient?</h5>
                <p class="text-muted" id="delete-patient-name"></p>
                <p class="text-danger font-weight-bold" style="font-size:13px;">This action cannot be undone. All patient data, vitals, and chat history will be removed.</p>
            </div>
            <div class="modal-footer" style="border:none;justify-content:center;padding-bottom:20px;">
                <button type="button" class="btn btn-default btn-round" data-dismiss="modal">No, Cancel</button>
                <a href="#" id="delete-confirm-btn" class="btn btn-danger btn-round">Yes, Delete</a>
            </div>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="passwordModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius:12px;overflow:hidden;">
            <div class="modal-header" style="background:#ff9800;color:#fff;border:none;">
                <h5 class="modal-title"><i class="zmdi zmdi-lock m-r-5"></i> Change Password</h5>
                <button type="button" class="close" style="color:#fff;opacity:0.8;" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" style="padding:25px;">
                <p class="text-muted" style="font-size:13px;">Set a new password for <strong id="pw-entity-name"></strong>. The new password will be sent to their email.</p>
                <input type="hidden" id="pw-entity-id">
                <input type="hidden" id="pw-entity-email">
                <input type="hidden" id="pw-entity-type">
                <div class="form-group">
                    <label class="font-weight-bold">New Password</label>
                    <input type="password" class="form-control" id="new-password" placeholder="Enter new password" style="border-radius:8px;">
                </div>
                <div class="form-group">
                    <label class="font-weight-bold">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm-password" placeholder="Confirm new password" style="border-radius:8px;">
                </div>
                <div id="pw-error" class="text-danger" style="display:none;font-size:13px;"></div>
                <div id="pw-success" class="text-success" style="display:none;font-size:13px;"></div>
            </div>
            <div class="modal-footer" style="border:none;justify-content:center;padding-bottom:20px;">
                <button type="button" class="btn btn-default btn-round" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning btn-round" onclick="submitPasswordChange()">Update Password</button>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(patientId, patientName) {
    document.getElementById('delete-patient-name').textContent = patientName + ' (' + patientId + ')';
    document.getElementById('delete-confirm-btn').href = 'delete-patient.php?id=' + encodeURIComponent(patientId);
    $('#deleteModal').modal('show');
}

function openPasswordModal(entityId, entityEmail, entityType) {
    document.getElementById('pw-entity-id').value = entityId;
    document.getElementById('pw-entity-email').value = entityEmail;
    document.getElementById('pw-entity-type').value = entityType;
    document.getElementById('pw-entity-name').textContent = entityId;
    document.getElementById('new-password').value = '';
    document.getElementById('confirm-password').value = '';
    document.getElementById('pw-error').style.display = 'none';
    document.getElementById('pw-success').style.display = 'none';
    $('#passwordModal').modal('show');
}

function submitPasswordChange() {
    var pw = document.getElementById('new-password').value;
    var cpw = document.getElementById('confirm-password').value;
    var errEl = document.getElementById('pw-error');
    var sucEl = document.getElementById('pw-success');
    errEl.style.display = 'none';
    sucEl.style.display = 'none';

    if (pw.length < 6) { errEl.textContent = 'Password must be at least 6 characters.'; errEl.style.display = 'block'; return; }
    if (pw !== cpw) { errEl.textContent = 'Passwords do not match.'; errEl.style.display = 'block'; return; }

    fetch('../api/admin_change_password.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            entity_id: document.getElementById('pw-entity-id').value,
            entity_email: document.getElementById('pw-entity-email').value,
            entity_type: document.getElementById('pw-entity-type').value,
            new_password: pw
        }),
        credentials: 'same-origin'
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            sucEl.textContent = 'Password updated successfully. Email notification sent.';
            sucEl.style.display = 'block';
            document.getElementById('new-password').value = '';
            document.getElementById('confirm-password').value = '';
        } else {
            errEl.textContent = data.message || 'Failed to update password.';
            errEl.style.display = 'block';
        }
    })
    .catch(function() {
        errEl.textContent = 'Network error. Please try again.';
        errEl.style.display = 'block';
    });
}
</script>
</body>
</html>

