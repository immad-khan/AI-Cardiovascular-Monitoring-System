<?php 
include("../config/DB_Config.php");
session_start();

// Access Control: ONLY Admins and Doctors can view prediction history
if (!isset($_SESSION["user_type"]) || ($_SESSION["user_type"] !== "admin" && $_SESSION["user_type"] !== "doctor")) {
    header("Location: index.php?status=Unauthorized Access&type=error");
    exit();
}

$doctorID = $_SESSION["user_id"];

try {
    // 1. Fetch AI Prediction History joined with Patient Data (respecting historical reading patientID lock)
    $sql = "SELECT apl.*, COALESCE(p.name, 'Unassigned') as patient_name, vr.timestamp as reading_time, COALESCE(md.model, 'Raspberry Pi 4') as device_model
            FROM \"AI_PREDICTION_LOG\" apl
            JOIN vital_sign_readings vr ON apl.\"readingID\" = vr.\"readingID\"
            LEFT JOIN monitoring_devices md ON vr.\"deviceID\" = md.\"deviceID\"
            LEFT JOIN patients p ON (vr.\"patientID\" = p.\"patientID\" OR (vr.\"patientID\" IS NULL AND md.\"patientID\" = p.\"patientID\")) ";
    
    // Filter for specific doctor if they aren't admin
    if ($_SESSION["user_type"] === "doctor") {
        $sql .= " WHERE p.\"assignedDoctorID\" = ? ";
    }
    
    $sql .= " ORDER BY apl.timestamp DESC LIMIT 50";
    
    $stmt = $conn->prepare($sql);
    
    if ($_SESSION["user_type"] === "doctor") {
        $stmt->execute([$doctorID]);
    } else {
        $stmt->execute();
    }
    
    $predictions = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!doctype html>
<html class="no-js " lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
<title>AI Prediction Hub - Digihealth</title>
<link rel="icon" href="favicon.ico" type="image/x-icon">
<link rel="stylesheet" href="../assets/plugins/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../assets/plugins/jquery-datatable/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/color_skins.css">
</head>
<body class="theme-cyan">

<!-- Overlay For Sidebars -->
<div class="overlay"></div>

<?php include("top_nav.php"); ?>

<!-- Left Sidebar -->
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

<!-- Right Sidebar -->
<?php include("rightsidebar.php"); ?>

<section class="content">
    <div class="block-header">
        <div class="row">
            <div class="col-lg-7 col-md-6 col-sm-12">
                <h2>Clinical Evaluation Hub <small>AI Engine Insights</small></h2>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row clearfix">
            <div class="col-lg-12">
                <div class="card">
                    <div class="header">
                        <h2><strong>Recent</strong> Predictions History</h2>
                    </div>
                    <div class="body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover js-basic-example dataTable">
                                <thead>
                                    <tr>
                                        <th>Prediction ID</th>
                                        <th>Patient Name</th>
                                        <th>AI Result (Classification)</th>
                                        <th>Confidence (0-1)</th>
                                        <th>Device Used</th>
                                        <th>Reading Time</th>
                                        <th>Analyzed At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($predictions as $p): ?>
                                    <tr>
                                        <td>#<?php echo $p['predictionID']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($p['patient_name']); ?></strong></td>
                                        <td>
                                            <?php 
                                            $class = (strpos(strtolower($p['predictionClass']), 'normal') !== false) ? 'badge-success' : 'badge-danger';
                                            echo "<span class='badge $class'>".strtoupper($p['predictionClass'])."</span>";
                                            ?>
                                        </td>
                                        <td>
                                            <div class="progress">
                                                <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="<?php echo $p['confidenceScore']*100; ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $p['confidenceScore']*100; ?>%;">
                                                    <span class="sr-only"><?php echo round($p['confidenceScore']*100, 2); ?>%</span>
                                                </div>
                                            </div>
                                            <small><?php echo round($p['confidenceScore']*100, 2); ?>%</small>
                                        </td>
                                        <td><?php echo htmlspecialchars($p['device_model']); ?></td>
                                        <td><?php echo date('M d, H:i', strtotime($p['reading_time'])); ?></td>
                                        <td><?php echo date('M d, H:i:s', strtotime($p['timestamp'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
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
<script src="../assets/bundles/vendorscripts.bundle.js"></script>
<script src="../assets/bundles/datatablescripts.bundle.js"></script>
<script src="../assets/bundles/mainscripts.bundle.js"></script>
<script>
$(function () {
    $('.js-basic-example').DataTable();
});
</script>
</body>
</html>
