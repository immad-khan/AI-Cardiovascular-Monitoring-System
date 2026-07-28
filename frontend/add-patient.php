<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
include("../config/DB_Config.php");
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

// ── Pre-fill from subscription (when coming via "Approve" button) ─────────────
$from_sub_id = intval($_GET['from_sub'] ?? 0);
$sub_data    = null;
if ($from_sub_id > 0) {
    try {
        $sub_stmt = $conn->prepare("SELECT * FROM subscriptions WHERE id = ?");
        $sub_stmt->execute([$from_sub_id]);
        $sub_data = $sub_stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
}

// Initializing variables - prefer POST, then subscription data, then empty
$patientId       = $_POST['patientId']      ?? ($_GET['patientId'] ?? '');
$name            = $_POST['name']           ?? ($sub_data['name']   ?? '');
$phoneNo         = $_POST['phoneNo']        ?? ($sub_data['phone']  ?? '');
$email           = $_POST['email']          ?? ($sub_data['email']  ?? '');
$age             = $_POST['age']            ?? ($sub_data['age']    ?? '');
$gender          = $_POST['gender']         ?? ($sub_data['gender'] ?? '');
$medicalHistory  = $_POST['medicalHistory'] ?? '';
$assignedDoctorID= ($_SESSION['user_type'] === 'doctor') ? $_SESSION['user_id'] : null;
$staffName       = $_POST['staff_name']     ?? '';
$wardNo          = $_POST['ward_no']        ?? '';
$date            = $_POST['Date']           ?? date('Y-m-d H:i:s');
$linked_mac_address = "";

// If editing an existing patient, load their data
if ($patientId && !$from_sub_id) {
    try {
        $sql = "SELECT * FROM patients WHERE \"patientID\" = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$patientId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $name            = $row['name'];
            $phoneNo         = $row['phone_no'];
            $email           = $row['email'];
            $age             = $row['age'];
            $gender          = $row['gender'];
            $medicalHistory  = $row['medical_history'];
            $assignedDoctorID= $row['assignedDoctorID'];
            $staffName       = $row['staff_name'];
            $wardNo          = $row['ward_no'];
            $date            = $row['date'];

            $link_sql  = "SELECT mac_address FROM device_patient_link WHERE patient_id = ? AND delinked_at IS NULL LIMIT 1";
            $link_stmt = $conn->prepare($link_sql);
            $link_stmt->execute([$patientId]);
            $link_row  = $link_stmt->fetch();
            if ($link_row) $linked_mac_address = $link_row['mac_address'];
        }
    } catch (PDOException $e) {
        $error = "Error fetching patient: " . $e->getMessage();
    }
}

// Fetch Doctors list
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

$isNewFromSub = ($from_sub_id > 0 && !$patientId);
$isEdit       = ($patientId && !$from_sub_id);
?>
<!doctype html>
<html class="no-js " lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
<title><?= $isEdit ? 'Edit' : 'Add' ?> Patient - DigiHealth</title>
<link rel="icon" href="favicon.ico" type="image/x-icon">
<link rel="stylesheet" href="../assets/plugins/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../assets/plugins/bootstrap-select/css/bootstrap-select.css"/>
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/color_skins.css">
<style>
    /* Patient ID field styling */
    #patientIdWrap { position: relative; }
    #patientId { padding-right: 120px; font-family: monospace; font-size: 15px; letter-spacing: 1px; background: #f0f4ff !important; }
    #pidStatus { position:absolute; right:10px; top:50%; transform:translateY(-50%); font-size:12px; font-weight:700; white-space:nowrap; pointer-events:none; }
    #pidStatus.ok   { color:#4caf50; }
    #pidStatus.err  { color:#f44336; }
    #pidStatus.chk  { color:#888; }
    .id-hint { font-size:11px; color:#888; margin-top:4px; }

    /* Subscription banner */
    .sub-banner { background:linear-gradient(135deg,#11998e,#38ef7d); border-radius:12px; padding:16px 22px; margin-bottom:20px; display:flex; align-items:center; gap:14px; color:#fff; }
    .sub-banner i { font-size:28px; }
    .sub-banner strong { font-size:15px; display:block; }
    .sub-banner span  { font-size:13px; opacity:.9; }

    .card { border-radius:12px; }
    .form-control:focus { border-color:#1565c0; box-shadow: 0 0 0 2px rgba(21,101,192,.12); }
    .btn-primary { background:linear-gradient(135deg,#1565c0,#1e88e5); border:none; }
    .btn-primary:hover { opacity:.9; }
</style>
<!-- Mobile CSS Fix --><link rel="stylesheet" href="../assets/css/mobile.css">
</head>
<body class="theme-cyan">
<nav class="navbar p-l-5 p-r-5">
    <?php include("top_nav.php") ?>
</nav>
<aside id="leftsidebar" class="sidebar">
    <div class="menu">
        <ul class="list">
             <li><a href="dashboard.php"><i class="zmdi zmdi-home"></i><span>Dashboard</span></a></li>
             <li class="active open"><a href="patients.php"><i class="zmdi zmdi-account-o"></i><span>Patients</span></a></li>
             <?php if ($_SESSION['user_type'] === 'admin'): ?>
             <li><a href="Admin-Subscriptions.php"><i class="zmdi zmdi-card-membership"></i><span>Subscriptions</span></a></li>
             <?php endif; ?>
        </ul>
    </div>
</aside>

<section class="content">
    <div class="block-header">
        <div class="row">
            <div class="col-lg-7 col-md-5 col-sm-12">
                <h2><?= $isEdit ? 'Edit' : 'Add' ?> Patient
                <small class="text-muted">
                    <?= $isNewFromSub ? 'Creating account from subscription request' : 'Fill in the clinical details below' ?>
                </small>
                </h2>
            </div>
        </div>
    </div>
    <div class="container-fluid">

        <?php if ($isNewFromSub && $sub_data): ?>
        <!-- Subscription source banner -->
        <div class="sub-banner">
            <i class="zmdi zmdi-card-membership"></i>
            <div>
                <strong>Creating patient from subscription #<?= $from_sub_id ?></strong>
                <span>Fields pre-filled from their subscription form. A Patient ID has been auto-generated — review and complete the profile below.</span>
            </div>
        </div>
        <?php endif; ?>

        <?php if(isset($status_msg)): ?>
            <div class="alert alert-<?= $type_msg == 'success' ? 'success' : 'danger' ?>">
                <?= $status_msg ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="patientForm">
            <?php if ($from_sub_id): ?>
                <input type="hidden" name="from_sub_id" value="<?= $from_sub_id ?>">
            <?php endif; ?>

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
                                        <label style="font-weight:600;font-size:13px;color:#555;">Patient ID <span style="color:#f44336;">*</span></label>
                                        <div id="patientIdWrap">
                                            <input type="text"
                                                   name="patientId"
                                                   id="patientId"
                                                   class="form-control"
                                                   placeholder="Auto-generating…"
                                                   value="<?= htmlspecialchars($patientId) ?>"
                                                   <?= $isEdit ? 'readonly' : 'required' ?>
                                                   style="<?= $isEdit ? 'background:#e9ecef;' : '' ?>"
                                                   autocomplete="off">
                                            <span id="pidStatus" class="chk"></span>
                                            <?php if($isEdit) echo '<input type="hidden" name="editPatient" value="'.htmlspecialchars($patientId).'">'; ?>
                                        </div>
                                        <div class="id-hint" id="pidHint">
                                            <?= $isEdit ? 'Patient ID cannot be changed.' : 'Auto-generated. You may customise it — uniqueness is checked live.' ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label style="font-weight:600;font-size:13px;color:#555;">Phone Number <span style="color:#f44336;">*</span></label>
                                        <input type="text" name="phoneNo" class="form-control" placeholder="Phone Number" value="<?= htmlspecialchars($phoneNo) ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row clearfix">
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label style="font-weight:600;font-size:13px;color:#555;">Email Address <span style="color:#f44336;">*</span></label>
                                        <input type="email" name="email" class="form-control" placeholder="Email Address" value="<?= htmlspecialchars($email) ?>" required>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="form-group">
                                        <label style="font-weight:600;font-size:13px;color:#555;">Age <span style="color:#f44336;">*</span></label>
                                        <input type="number" name="age" class="form-control" placeholder="Age" value="<?= htmlspecialchars($age) ?>" required min="1" max="150">
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="form-group">
                                        <label style="font-weight:600;font-size:13px;color:#555;">Gender <span style="color:#f44336;">*</span></label>
                                        <select name="gender" class="form-control show-tick" required>
                                            <option value="">- Gender -</option>
                                            <option value="Male"   <?= ($gender == 'Male')   ? 'selected' : '' ?>>Male</option>
                                            <option value="Female" <?= ($gender == 'Female') ? 'selected' : '' ?>>Female</option>
                                            <option value="Other"  <?= ($gender == 'Other')  ? 'selected' : '' ?>>Other</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row clearfix">
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label style="font-weight:600;font-size:13px;color:#555;">Medical History</label>
                                        <textarea name="medicalHistory" rows="4" class="form-control no-resize" placeholder="Patient's family history, lifestyle, and other medical conditions..."><?= htmlspecialchars($medicalHistory) ?></textarea>
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
                                    <div class="form-group">
                                        <label style="font-weight:600;font-size:13px;color:#555;">Assigned Doctor</label>
                                        <select name="AssociatedDoctors[]" class="form-control show-tick" data-live-search="true">
                                            <option value="">-- Select Doctor --</option>
                                            <?php foreach($doctors_list as $doc): ?>
                                                <option value="<?= $doc['userID'] ?>" <?= ($assignedDoctorID == $doc['userID']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($doc['full_name'] . " (" . $doc['specialization'] . ")") ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label style="font-weight:600;font-size:13px;color:#555;">Link IoT Device</label>
                                        <select name="mac_address" class="form-control show-tick" data-live-search="true">
                                            <option value="">-- Link IoT Device --</option>
                                            <?php foreach($available_devices as $dev): ?>
                                                <option value="<?= $dev['mac_address'] ?>" <?= ($linked_mac_address == $dev['mac_address']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($dev['mac_address'] . " - " . $dev['model']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row clearfix m-t-20">
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label style="font-weight:600;font-size:13px;color:#555;">Patient Full Name <span style="color:#f44336;">*</span></label>
                                        <input type="text" name="name" class="form-control" placeholder="Patient Name (Real Name)" value="<?= htmlspecialchars($name) ?>" required>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label style="font-weight:600;font-size:13px;color:#555;">Ward / Staff Name</label>
                                        <input type="text" name="ward_no" class="form-control" placeholder="Ward# / Staff Name" value="<?= htmlspecialchars($wardNo) ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="row clearfix">
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label style="font-weight:600;font-size:13px;color:#555;">Registration Date</label>
                                        <input type="text" name="Date" class="form-control" value="<?= htmlspecialchars($date) ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="row clearfix m-t-20">
                                <div class="col-sm-12">
                                    <button type="submit" class="btn btn-primary btn-round" id="submitBtn">
                                        <i class="zmdi zmdi-check m-r-5"></i>
                                        <?= $isEdit ? 'Update Patient' : 'Create Patient &amp; Send Login Email' ?>
                                    </button>
                                    <a href="<?= $isNewFromSub ? 'Admin-Subscriptions.php' : 'patients.php' ?>" class="btn btn-default btn-round btn-simple m-l-10">
                                        <i class="zmdi zmdi-arrow-left m-r-5"></i>
                                        <?= $isNewFromSub ? 'Back to Subscriptions' : 'Go back to patients list' ?>
                                    </a>
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
<script>
(function(){
    var pidInput  = document.getElementById('patientId');
    var pidStatus = document.getElementById('pidStatus');
    var submitBtn = document.getElementById('submitBtn');
    var isEdit    = <?= $isEdit ? 'true' : 'false' ?>;
    var pidValid  = false;

    if (!pidInput || isEdit) return; // nothing to do in edit mode

    var checkTimer = null;

    function setPidStatus(msg, cls) {
        pidStatus.textContent = msg;
        pidStatus.className   = cls;
    }

    function checkAvailability(id) {
        if (!id) {
            setPidStatus('', 'chk');
            submitBtn.disabled = false;
            return;
        }
        setPidStatus('checking…', 'chk');
        submitBtn.disabled = true;

        fetch('../api/check_patient_id.php?patient_id=' + encodeURIComponent(id), { credentials:'same-origin' })
        .then(function(r){ return r.json(); })
        .then(function(data){
            if (data.available) {
                setPidStatus('✓ Available', 'ok');
                submitBtn.disabled = false;
                pidValid = true;
            } else {
                setPidStatus('✗ Already used', 'err');
                submitBtn.disabled = true;
                pidValid = false;
            }
        })
        .catch(function(){
            setPidStatus('', 'chk');
            submitBtn.disabled = false;
        });
    }

    // Debounced live check on typing
    pidInput.addEventListener('input', function(){
        clearTimeout(checkTimer);
        var val = pidInput.value.trim();
        if (!val) { setPidStatus('', 'chk'); submitBtn.disabled = false; return; }
        checkTimer = setTimeout(function(){ checkAvailability(val); }, 450);
    });

    // Auto-generate on load if field is empty
    if (!pidInput.value) {
        setPidStatus('generating…', 'chk');
        fetch('../api/generate_patient_id.php', { credentials:'same-origin' })
        .then(function(r){ return r.json(); })
        .then(function(data){
            if (data.success) {
                pidInput.value = data.patient_id;
                setPidStatus('✓ Available', 'ok');
                pidValid = true;
                submitBtn.disabled = false;
            } else {
                setPidStatus('', 'chk');
                pidInput.placeholder = 'Enter patient ID manually';
                submitBtn.disabled = false;
            }
        })
        .catch(function(){
            setPidStatus('', 'chk');
            submitBtn.disabled = false;
        });
    } else {
        // Pre-filled value (edit mode from sub data shouldn't happen, but safety check)
        checkAvailability(pidInput.value);
    }

    // Block form submit if ID taken
    document.getElementById('patientForm').addEventListener('submit', function(e){
        if (!isEdit && pidStatus.classList.contains('err')) {
            e.preventDefault();
            alert('The Patient ID "' + pidInput.value + '" is already assigned. Please choose a different ID or let the system generate one.');
        }
    });
})();
</script>
</body>
</html>
