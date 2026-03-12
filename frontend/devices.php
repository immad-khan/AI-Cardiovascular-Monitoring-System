<?php 
include("../config/DB_Config.php");
// Start the session
session_start();
// Check if the user is logged in and if the user type is admin
if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'tech-admin')) {
    // If not admin, redirect to index.php with a relevant status
    header("Location: index.php?status=Access is denied&type=error");
    exit(); // Stop further execution after the redirect
}
?>
<!doctype html>
<html class="no-js " lang="en">

<!-- Mirrored from hms.cognisun.net/CUST Digihealth/html/light/patients.html by HTTrack Website Copier/3.x [XR&CO'2014], Sun, 06 Oct 2024 17:19:29 GMT -->
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
<meta name="description" content="Responsive Bootstrap 4 and web Application ui kit.">

<title>Patients - CUST Digihealth </title>
<link rel="icon" href="favicon.ico" type="image/x-icon">
<!-- Favicon-->
<link rel="stylesheet" href="../assets/plugins/bootstrap/css/bootstrap.min.css">
<!-- Custom Css -->
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/color_skins.css">
</head>
<body class="theme-cyan">
<!-- Page Loader -->
<div class="page-loader-wrapper">
    <div class="loader">
        <div class="m-t-30"><img class="zmdi-hc-spin" src="../assets/images/logo.svg" width="48" height="48" alt="CUST Digihealth"></div>
        <p>Please wait...</p>
    </div>
</div>
<!-- Overlay For Sidebars -->
<div class="overlay"></div>
<!-- Top Bar -->
<nav class="navbar p-l-5 p-r-5">
    <?php include("top_nav.php") ?>
</nav>
<!-- Left Sidebar -->
<aside id="leftsidebar" class="sidebar">
    <?php include("admin_sidebar.php") ?>
</aside>
<!-- Right Sidebar -->

<?php include("rightsidebar.php") ?>

<section class="content">
    <div class="block-header">
        <div class="row">
            <div class="col-lg-7 col-md-5 col-sm-12">
                <h2>All ECG Devices
                <small class="text-muted">Welcome to CUST Digihealth</small>
                </h2>
            </div>
            <div class="col-lg-5 col-md-7 col-sm-12">
                <button class="btn btn-primary btn-icon btn-round d-none d-md-inline-block float-right m-l-10" type="button">
                    <i class="zmdi zmdi-plus"></i>
                </button>
                <ul class="breadcrumb float-md-right">
                    <li class="breadcrumb-item"><a href="dashboard.php"><i class="zmdi zmdi-home"></i> Home</a></li>
                    <li class="breadcrumb-item"><a href="javascript:void(0);">Patients</a></li>
                    <li class="breadcrumb-item active">All ECG Devices</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row clearfix">
            <div class="col-md-12">
                <div class="card patients-list">
                    <div class="header">
                        <h2><strong>ECG Devices</strong> List</h2>
                        <ul class="header-dropdown">
                            <li class="dropdown"> <a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"> <i class="zmdi zmdi-more"></i> </a>
                                <ul class="dropdown-menu dropdown-menu-right slideUp">
                                    <li><a href="add-device.php">Add New ECG Device</a></li>
                                </ul>
                            </li>
                            <li class="remove">
                                <a role="button" class="boxs-close"><i class="zmdi zmdi-close"></i></a>
                            </li>
                        </ul>
                    </div>
                    <div class="body">

                        <!-- Tab panes -->
                        <div class="tab-content m-t-10">
                            <div class="tab-pane table-responsive active" id="All">
                                <table class="table m-b-0 table-hover">
                                    <thead>
                                        <tr>                  
                                            <th>Device ID</th>
											<th>MAC Address</th>
											<th>Model</th>
											<th>Assigned Patient</th>
											<th>Status</th>
											<th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
									
									<?php 
										// Fetch all patients for the assignment dropdown
										try {
											$patientStmt = $conn->query("SELECT \"patientID\", name FROM patients WHERE \"isActive\" = TRUE ORDER BY name ASC");
											$all_patients = $patientStmt->fetchAll(PDO::FETCH_ASSOC);
										} catch (PDOException $e) { $all_patients = []; }

										// Fetch all monitoring devices (Corrected table and column names)
										try {
											$sql = "SELECT md.\"deviceID\", md.mac_address, md.model, md.status, md.\"patientID\", p.name as patient_name 
													FROM monitoring_devices md
													LEFT JOIN patients p ON md.\"patientID\" = p.\"patientID\"";
											$stmt = $conn->prepare($sql);
											$stmt->execute();
											$devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

											if ($devices) {
												foreach ($devices as $row) {
													$badge = ($row['status'] == 'Online') ? 'info' : (($row['status'] == 'Offline') ? 'danger' : 'warning');
													echo "<tr>";
													echo "<td>" . htmlspecialchars($row['deviceID']) . "</td>";
													echo "<td>" . htmlspecialchars($row['mac_address']) . "</td>";
													echo "<td>" . htmlspecialchars($row['model']) . "</td>";
													echo "<td>" . ($row['patient_name'] ? htmlspecialchars($row['patient_name']) : '<span class="text-muted">Unassigned</span>') . "</td>";
													echo "<td><span class='badge badge-$badge'>" . htmlspecialchars($row['status'] ?? 'Offline') . "</span></td>";
													echo '<td>
															<button class="btn btn-primary btn-sm btn-round btn-simple edit-device-btn" 
																data-id="'.$row['deviceID'].'" 
																data-mac="'.$row['mac_address'].'"
																data-model="'.$row['model'].'"
																data-status="'.$row['status'].'"
																data-patient="'.$row['patientID'].'">
																Edit
															</button>
														  </td>';
													echo "</tr>";
												}
											} else {
												echo "<tr><td colspan='6' class='text-center'>No monitoring devices found in the database.</td></tr>";
											}
										} catch (PDOException $e) {
											echo "<tr><td colspan='6' class='text-center text-danger'>Database Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
										}
									?>
                                       
                                    </tbody>
                                </table>  
                            </div>
                            
                        </div>
						
                    </div>
                </div>
				
					
								<a href="./add-device.php" class="btn btn-primary float-right" >Add New Device</a>
            </div>
        </div>
    </div>
</section>

<!-- Device Edit Modal -->
<div class="modal fade" id="editDeviceModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="title m-b-0">Manage Device Configuration</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editDeviceForm">
                <style>
                    .modern-form-group { margin-bottom: 20px; }
                    .custom-label { font-weight: 600; font-size: 0.75rem; text-transform: uppercase; color: #999; display: block; margin-bottom: 8px; }
                    .modern-input { width: 100%; border: 2px solid #f4f4f4; border-radius: 8px; padding: 12px; background: #fcfcfc; }
                    .modern-input:focus { border-color: #00cfd1; outline: none; background: #fff; }
                </style>
                <div class="modal-body p-4">
                    <input type="hidden" name="deviceID" id="edit_device_id">
                    
                    <div class="modern-form-group">
                        <label class="custom-label">MAC Address (Read Only)</label>
                        <input type="text" id="edit_mac" class="modern-input" readonly style="background: #eee; border:none;">
                    </div>

                    <div class="modern-form-group">
                        <label class="custom-label">Assign to Patient</label>
                        <select name="patientID" id="edit_patient_id" class="modern-input">
                            <option value="">-- No Patient Assigned --</option>
                            <?php foreach($all_patients as $pt): ?>
                                <option value="<?php echo $pt['patientID']; ?>"><?php echo htmlspecialchars($pt['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="modern-form-group">
                        <label class="custom-label">Device Status</label>
                        <select name="status" id="edit_status" class="modern-input">
                            <option value="Online">Online (Gateway Active)</option>
                            <option value="Offline">Offline (Inactive)</option>
                            <option value="Assigned">Assigned (Awaiting Sync)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 p-3 bg-light">
                    <button type="button" class="btn btn-simple btn-round" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary btn-round">Update Device</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Jquery Core Js --> 
<script src="../assets/bundles/libscripts.bundle.js"></script> <!-- Bootstrap JS and jQuery v3.2.1 -->
<script src="../assets/bundles/vendorscripts.bundle.js"></script> <!-- slimscroll, waves Scripts Plugin Js -->  

<script src="../assets/bundles/mainscripts.bundle.js"></script><!-- Custom Js --> 

<script>
$(document).ready(function() {
    $('.edit-device-btn').on('click', function() {
        const data = $(this).data();
        $('#edit_device_id').val(data.id);
        $('#edit_mac').val(data.mac);
        $('#edit_status').val(data.status);
        $('#edit_patient_id').val(data.patient);
        $('#editDeviceModal').modal('show');
    });

    $('#editDeviceForm').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        
        $.ajax({
            url: 'edit-device-logic.php',
            type: 'POST',
            data: formData,
            success: function(response) {
                if(response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('An error occurred during update.');
            }
        });
    });
});
</script>

<link rel="stylesheet" href="../assets/plugins/toast/jquery.toast.min.css">
<script src="../assets/plugins/toast/jquery.toast.min.js"></script>

<?php 
if(isset($_GET['status'])){ 
	$type="success";
	if(isset($_GET['type'])){
		$type = "error";
		
	}
 ?>

<script>
$.toast({
	text: '<?php echo $_GET["status"] ?>',
	showHideTransition: 'slide',
	position: 'bottom-right', 
	hideAfter: 4000, 
	icon: '<?php echo $type ?>'
})
</script>
<?php } ?>

<script>
	$(function(){
		<?php if($_SESSION["user_type"] !== "admin"){ ?>
			$(".toggle-sidebar").click();
			$(".toggle-sidebar").addClass("d-none");
			$(".notification-box").addClass("d-none");
			
		<?php } ?>
		
		
	})
</script>
</body>

<!-- Mirrored from hms.cognisun.net/CUST Digihealth/html/light/patients.html by HTTrack Website Copier/3.x [XR&CO'2014], Sun, 06 Oct 2024 17:19:29 GMT -->
</html>
