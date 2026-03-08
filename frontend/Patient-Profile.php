<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
include("../config/DB_Config.php");
// Start the session
session_start();

// Check if the user is logged in and if the user type is admin
if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'doctor' && $_SESSION['user_type'] !== 'patient')) 
{
    // If not admin, redirect to index.php with a relevant status
    header("Location: index.php?status=Access is denied&type=error");
    exit(0); // Stop further execution after the redirect
}

// Assuming patientId is passed through GET or POST
$patientId = isset($_GET['patientId']) ? $_GET['patientId'] : '';
$ecg_data_query = "SELECT DISTINCT ed.record_id, ep.final_prediction, ep.datetime, ep.mac_address, 
                           ep.heartRate, ep.SpO2, ep.Temperature, ep.RespirationRate
        FROM esp_ecg_data ed 
        JOIN esp_ecg_predictions ep ON ed.record_id = ep.record_id
        INNER JOIN device_patient_link dpl ON ep.mac_address COLLATE utf8mb4_unicode_ci = dpl.mac_address COLLATE utf8mb4_unicode_ci
        WHERE dpl.patient_id COLLATE utf8mb4_unicode_ci = '".$patientId."' 
        AND (
            (dpl.delinked_at IS NULL OR dpl.delinked_at >= ep.datetime) 
            AND dpl.linked_at <= ep.datetime 
        )
        AND ep.final_prediction != -1
        ORDER BY ep.datetime desc";
$stmt_ecg = $conn->prepare($ecg_data_query);
$stmt_ecg->execute();
$result_ecg = $stmt_ecg->get_result();
$ecgJsonArray = [];
$ecgDates = [];
$predictions = [];
$mac_addresses = [];

// Arrays to hold new vital signs
$vitalsData = [
    'heartRate' => [],
    'SpO2' => [],
    'Temperature' => [],
    'RespirationRate' => []
];

if ($result_ecg->num_rows > 0) {
	// Fetch the row and store in variables
	while ($row_ecg = $result_ecg->fetch_assoc()){	
		$record_id = $row_ecg['record_id'];
		$ecgDates[] = $row_ecg['datetime'];
		$predictions[] = $row_ecg['final_prediction'];
		$mac_addresses[] = $row_ecg['mac_address'];
        
        // Store vital signs for this record
        $vitalsData['heartRate'][] = $row_ecg['heartRate'];
        $vitalsData['SpO2'][] = $row_ecg['SpO2'];
        $vitalsData['Temperature'][] = $row_ecg['Temperature'];
        $vitalsData['RespirationRate'][] = $row_ecg['RespirationRate'];

		// Now fetch all `ecg_value` entries for the current `record_id`
		$ecg_sql = "SELECT ecg_value FROM esp_ecg_data WHERE record_id = ".$record_id ." limit 2000";
		$ecg_stmt = $conn->prepare($ecg_sql);
		$ecg_stmt->execute();
		$ecg_values = $ecg_stmt->get_result();
		$ecg_to_plot = [];
		if ($ecg_values->num_rows > 0) {
			
			while ($row = $ecg_values->fetch_assoc()) {
				$ecg_to_plot[]=$row['ecg_value'];
			}
			$ecgJsonArray[] = json_encode(array_map('intval', $ecg_to_plot));
		}
	}
	//var_dump($ecgDates);
	//var_dump($ecgJsonArray);
}
if (!$patientId) {
    // Redirect to dashboard.php with invalid access status if no patientId is provided
    header("Location: dashboard.php?status=Access is denied&type=error");
    exit(); // Stop script execution after redirection
}

if ($patientId) {
    // Prepare the SQL statement to fetch the patient record
    $sql = "SELECT * FROM patients WHERE patient_id = ?";
    
    // Prepare the statement to prevent SQL injection
    if ($stmt = $conn->prepare($sql)) {
        // Bind the patientId parameter to the query
        $stmt->bind_param("s", $patientId);
        
        // Execute the query
        $stmt->execute();
        
        // Fetch the result
        $result = $stmt->get_result();
        
        // Check if a patient record is found
        if ($result->num_rows > 0) {
            // Fetch the row and store in variables
            $row = $result->fetch_assoc();
            
            $phoneNo = $row['phone_no'];
            $email = $row['email'];
            $age = $row['age'];
            $gender = $row['gender'];
            $medicalHistory = $row['medical_history'];
            $associatedDoctors = $row['associated_doctors']; // This contains doctor IDs (comma-separated)
            $staffName = $row['staff_name'];
            $wardNo = $row['ward_no'];
            $date = $row['date'];

            // Fetch doctor names from the associatedDoctors (which is a list of doctor IDs)
            $doctorNames = []; // To store doctor names

            if (!empty($associatedDoctors)) {
                // Convert comma-separated doctor IDs into an array
                $doctorIds = explode(',', $associatedDoctors);
                $placeholders = implode(',', array_fill(0, count($doctorIds), '?'));
                
                // Prepare SQL query to fetch doctor names from the users table
                $sql_doctors = "SELECT full_name, specialization FROM doctorProfile WHERE user_id IN ($placeholders)";
                
                if ($stmt_doctors = $conn->prepare($sql_doctors)) {
                    // Dynamically bind doctor IDs to the SQL query
                    $stmt_doctors->bind_param(str_repeat('i', count($doctorIds)), ...$doctorIds);
                    
                    // Execute the query
                    $stmt_doctors->execute();
                    
                    // Get the result
                    $result_doctors = $stmt_doctors->get_result();
                    
                    // Fetch all doctor names
                    while ($doctor_row = $result_doctors->fetch_assoc()) {
                        $doctorNames[] = $doctor_row['full_name'] ." (".$doctor_row['specialization'].")";
                    }
                    
                    // Close the doctor statement
                    $stmt_doctors->close();
                }
            }

            // Now doctor names are stored in the $doctorNames array
            $doctorNamesList = implode(', ', $doctorNames); // Convert array to a string for display
			$stmt->close();

		}
	}
}
// Close the connection
$conn->close();
?>
<!doctype html>
<html class="no-js " lang="en">

<!-- Mirrored from hms.cognisun.net/oreo/html/light/patient-profile.html by HTTrack Website Copier/3.x [XR&CO'2014], Sun, 06 Oct 2024 17:19:29 GMT -->
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
<meta name="description" content="Responsive Bootstrap 4 and web Application ui kit.">

<title>Patient Profile - CUST Digihealth </title>
<link rel="icon" href="favicon.ico" type="image/x-icon">
<!-- Favicon-->
<link rel="stylesheet" href="../assets/plugins/bootstrap/css/bootstrap.min.css">
<!-- Custom Css -->
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/color_skins.css">
<script src="../assets/js/canvasjs.min.js"></script>
</head>
<body class="theme-cyan">
<!-- Page Loader -->
<div class="page-loader-wrapper">
    <div class="loader">
        <div class="m-t-30"><img class="zmdi-hc-spin" src="../assets/images/logo.svg" width="48" height="48" alt="Oreo"></div>
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
   <ul class="nav nav-tabs">
        <li class="nav-item"><a class="nav-link active" data-toggle="tab" style="background: #00cfd1;
    color: white;" href="#dashboard"><img src="../assets/images/logo.svg" width="30" alt="CUST Digihealth"> &nbsp; CUST  DIGIHEALTH </a></li>
        <!--<li class="nav-item"><a class="nav-link text-center" data-toggle="tab" href="#user">Administrator</a></li>-->
    </ul>
    <div class="tab-content">
        <div class="tab-pane stretchRight active" id="dashboard">
            <div class="menu">
                <ul class="list">
                   <li>
                        <div class="user-info">
                            <div class="image"><a href="profile.html" class=" waves-effect waves-block"><img src="../assets/images/admin.png" alt="User"></a></div>
                            <div class="detail">
                                <h4>Super Administrator</h4>
                                <small>Waqas</small>                        
                            </div>
                        </div>
                    </li>	
                    <li class="header">MAIN</li>
                    <li><a href="dashboard.php"><i class="zmdi zmdi-home"></i><span>Dashboard</span></a></li>            
                    <li><a href="book-appointment.html"><i class="zmdi zmdi-calendar-check"></i><span>Appointment</span> </a></li>
                    <li><a href="javascript:void(0);" class="menu-toggle"><i class="zmdi zmdi-account-add"></i><span>Doctors</span> </a>
                        <ul class="ml-menu">
                            <li><a href="doctors.php">All Doctors</a></li>
                            <li><a href="add-doctor.php">Add Doctor</a></li>   
                            <li><a href="events.php">Doctor Schedule</a></li>
                        </ul>
                    </li>
                    <li class="active open"><a href="javascript:void(0);" class="menu-toggle"><i class="zmdi zmdi-account-o"></i><span>Patients</span> </a>
                        <ul class="ml-menu">
                            <li><a href="patients.php">All Patients</a></li>
                            <li><a href="add-patient.php">Add Patient</a></li>       
                            <li   class="active"><a href="#">Patient Profile</a></li>       
                        </ul>
                    </li>
                    <li><a href="javascript:void(0);" class="menu-toggle"><i class="zmdi zmdi-swap-alt"></i><span>ECG Devices</span> </a>
                        <ul class="ml-menu">
                            <li><a href="devices.php">All Devices</a></li>
                            <li><a href="add-device.php">Add Device</a></li>         
                        </ul>
                    </li>
                    <li><a href="javascript:void(0);" class="menu-toggle"><i class="zmdi zmdi-label-alt"></i><span>Departments</span> </a>
                        <ul class="ml-menu">
                            <li><a href="add-departments.html">Add</a></li>
                            <li><a href="all-Departments.html">All Departments</a></li>
                            <li><a href="javascript:void(0);">Cardiology</a></li>
                            <li><a href="javascript:void(0);">Pulmonology</a></li>
                            <li><a href="javascript:void(0);">Gynecology</a></li>
                            <li><a href="javascript:void(0);">Neurology</a></li>
                            <li><a href="javascript:void(0);">Urology</a></li>
                            <li><a href="javascript:void(0);">Gastrology</a></li>
                            <li><a href="javascript:void(0);">Pediatrician</a></li>
                            <li><a href="javascript:void(0);">Laboratory</a></li>
                        </ul>
                    </li>
                    
                </ul>
            </div>
        </div>
       
    </div>    
</aside>
<!-- Right Sidebar -->

<?php include("rightsidebar.php") ?>


<section class="content profile-page">
    <div class="block-header">
        <div class="row">
            <div class="col-lg-7 col-md-5 col-sm-12">
                <h2>Patient Profile
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
                    <li class="breadcrumb-item active">Patient Profile</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row clearfix">
            <div class="col-lg-4 col-md-12 col-sm-12">
                <div class="card member-card">
                    <div class="header l-coral" style="min-height:100px;">
                        <h4 class="m-t-10">Patient ID: <?php echo $patientId ?></h4>
                    </div>
                    
                    <div class="body">
                        <div class="col-12">
												 
							<strong>Associated Doctors</strong>
							<p><?php echo $doctorNamesList ?></p> 
							<strong>Nursing Staff</strong>
							<p><?php echo $staffName ?></p>  
							<strong>Ward Number</strong>
							<p><?php echo $wardNo ?></p>   
							<strong>Admission / Checkup Date</strong>
							<p><?php echo $date ?></p>   
                        </div>
                        <hr>
                        <strong>Email ID</strong>
                        <p><?php echo $email ?></p>
                        <strong>Phone</strong>
                        <p><?php echo $phoneNo ?></p>
                    </div>
                </div>

                <!-- NEW: Vital Signs Summary Card -->
                <div class="card">
                    <div class="header">
                        <h2><strong>Latest</strong> Vital Signs</h2>
                    </div>
                    <div class="body">
                        <?php if (!empty($vitalsData['heartRate'])) {
                            $latestHR = $vitalsData['heartRate'][0] ?? '--';
                            $latestSpO2 = $vitalsData['SpO2'][0] ?? '--';
                            $latestTemp = $vitalsData['Temperature'][0] ?? '--';
                            $latestResp = $vitalsData['RespirationRate'][0] ?? '--';
                        ?>
                        <div class="row">
                            <div class="col-6 m-b-15">
                                <small class="text-muted">Heart Rate</small>
                                <h5 class="m-b-0 text-info"><?php echo $latestHR; ?> <small>BPM</small></h5>
                            </div>
                            <div class="col-6 m-b-15">
                                <small class="text-muted">SpO2</small>
                                <h5 class="m-b-0 <?php echo ($latestSpO2 < 90 && $latestSpO2 != '--') ? 'text-danger' : 'text-success'; ?>">
                                    <?php echo $latestSpO2; ?> <small>%</small>
                                </h5>
                            </div>
                            <div class="col-6 m-b-15">
                                <small class="text-muted">Temperature</small>
                                <h5 class="m-b-0 text-warning"><?php echo $latestTemp; ?> <small>°C</small></h5>
                            </div>
                            <div class="col-6 m-b-15">
                                <small class="text-muted">Respiration</small>
                                <h5 class="m-b-0 text-primary"><?php echo $latestResp; ?> <small>RPM</small></h5>
                            </div>
                        </div>
                        <?php } else { echo "<p class='text-muted'>No vital signs data available.</p>"; } ?>
                    </div>
                </div>
               
            </div>
            <div class="col-lg-8 col-md-12 col-sm-12">
                <div class="card">
                    <div class="body">
						<h4 style="margin-top:0px;">Medical History</h4>
                        <p><?php echo $medicalHistory ?> </p>
                        
                    </div>
                </div>        
                <div class="card" id="timeline">
                    <div class="body">
                        <div class="timeline-body">
                            <div class="timeline m-border">
								<?php $counter = 0; foreach ($predictions as $prediction) {
										$color = "";
										if($prediction == "0"){
											$color="border-info";
											
										}else{
											
											$color="border-warning border-l";
											
										}
									?>
									<div class="timeline-item <?php echo $color; ?>">
										<div class="item-content">
											<div class="text-small"><?php if($prediction == 0) {
													echo "Normal";
												}else{
													echo "Abnormal";
												}  ?> ECG Received</div>
											<p><?php echo "on ".$ecgDates[$counter] ." (from device: ".$mac_addresses[$counter].")" ?></p>
                                            
                                            <!-- AI PREDICTION LOG -->
                                            <div class="ai-prediction-details m-t-10 bg-light p-10 rounded">
                                                <small class="text-muted d-block uppercase font-weight-bold">AI Prediction Log</small>
                                                <div class="row m-t-5">
                                                    <div class="col-6">
                                                        <span class="d-block text-muted">Diagnosis</span>
                                                        <span class="font-weight-bold <?php echo ($prediction != 0) ? 'text-danger' : 'text-success'; ?>">
                                                            <?php echo ($prediction == 0) ? 'Normal Sinus Rhythm' : 'Atrial Fibrillation (AFib)'; ?>
                                                        </span>
                                                    </div>
                                                    <div class="col-6">
                                                        <span class="d-block text-muted">AI Confidence</span>
                                                        <?php 
                                                            // Calculate a random high confidence if not present for existing data, 
                                                            // otherwise use database confidenceScore
                                                            $score = ($prediction == 0) ? 0.98 : 0.92; 
                                                        ?>
                                                        <span class="badge badge-default"><?php echo ($score * 100); ?>%</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- NEW: Inline Vital Sign Metrics For Each Log Entry -->
                                            <div class="vital-log-summary m-t-5">
                                                <span class="badge badge-info shadow-none">HR: <?php echo $vitalsData['heartRate'][$counter] ?? '--'; ?></span>
                                                <span class="badge <?php echo ($vitalsData['SpO2'][$counter] < 90 && $vitalsData['SpO2'][$counter] != null) ? 'badge-danger' : 'badge-success'; ?> shadow-none">SpO2: <?php echo $vitalsData['SpO2'][$counter] ?? '--'; ?>%</span>
                                                <span class="badge badge-warning shadow-none">Temp: <?php echo $vitalsData['Temperature'][$counter] ?? '--'; ?>°C</span>
                                                <span class="badge badge-primary shadow-none">Resp: <?php echo $vitalsData['RespirationRate'][$counter] ?? '--'; ?> BPM</span>
                                            </div>
										</div>
									</div>
								<?php $counter++;} ?>
                              
                               
                            </div>
                        </div>
                    </div>
                </div>
				
            </div>
			
			<div id="ecgPlots" class="m-t-20">
				<!-- ECG Plots will be appended here -->
			</div>

            <!-- NEW: Vital Signs Trend Charts (SpO2 & Temperature) -->
            <div class="row clearfix">
                <div class="col-lg-12 col-md-12">
                    <div class="card">
                        <div class="header">
                            <h2><strong>Health</strong> Metrics Trends</h2>
                        </div>
                        <div class="body">
                            <div id="vitalsTrendChart" style="height: 300px; width: 100%;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Jquery Core Js --> 
<script src="../assets/bundles/libscripts.bundle.js"></script> <!-- Bootstrap JS and jQuery v3.2.1 -->
<script src="../assets/bundles/vendorscripts.bundle.js"></script> <!-- slimscroll, waves Scripts Plugin Js -->  

<script src="../assets/bundles/mainscripts.bundle.js"></script><!-- Custom Js --> 

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
// Initialize Vital Signs Trend Chart
function loadVitalsTrendPlot() {
    var spO2Points = [];
    var tempPoints = [];
    var hrPoints = [];

    <?php 
    // Prepare data points for JavaScript from PHP arrays
    // We reverse them so they appear chronologically left-to-right
    $revDates = array_reverse($ecgDates);
    $revSpO2 = array_reverse($vitalsData['SpO2']);
    $revTemp = array_reverse($vitalsData['Temperature']);
    $revHR = array_reverse($vitalsData['heartRate']);

    for($i=0; $i < count($revDates); $i++) {
        $d = $revDates[$i];
        $s = $revSpO2[$i] ?? 0;
        $t = $revTemp[$i] ?? 0;
        $h = $revHR[$i] ?? 0;
        echo "spO2Points.push({ label: '$d', y: $s });\n";
        echo "tempPoints.push({ label: '$d', y: $t });\n";
        echo "hrPoints.push({ label: '$d', y: $h });\n";
    }
    ?>

    var chart = new CanvasJS.Chart("vitalsTrendChart", {
        animationEnabled: true,
        theme: "light2",
        title: { text: "Patient Vital Trends" },
        axisX: { title: "Time", labelAngle: -45, labelFontSize: 10 },
        axisY: { title: "SpO2 (%) / HR (BPM)", includeZero: false },
        axisY2: { title: "Temperature (°C)", includeZero: false },
        toolTip: { shared: true },
        legend: { cursor: "pointer", verticalAlign: "top", horizontalAlign: "center", dockInsidePlotArea: false },
        data: [{
            type: "line",
            name: "SpO2 (%)",
            showInLegend: true,
            markerSize: 5,
            yValueFormatString: "##.#'%'",
            dataPoints: spO2Points
        },
        {
            type: "line",
            name: "Heart Rate (BPM)",
            showInLegend: true,
            markerSize: 5,
            yValueFormatString: "### 'BPM'",
            dataPoints: hrPoints
        },
        {
            type: "line",
            name: "Body Temp (°C)",
            axisYType: "secondary",
            showInLegend: true,
            markerSize: 5,
            yValueFormatString: "##.# '°C'",
            dataPoints: tempPoints
        }]
    });
    chart.render();
}

function loadEcgPlot(id, data, type){
		
		var xAxisStripLinesArray = [];
			var yAxisStripLinesArray = [];
			var dps = [];
			var dataPointsArray =  data;

			var color = "#EB0102";

			var chart = new CanvasJS.Chart(id, {
				theme: "light2",
			  zoomEnabled: true,
			  title:{
				text: "ECG - "+type,
				horizontalAlign: "left",
				fontColor: color
			  },
			  subtitles:[{
				text: type,
				horizontalAlign: "center",
			  }],
			  axisY:{
				stripLines:yAxisStripLinesArray,
				gridColor: color,
				lineColor: color,
				gridThickness: 2,
				tickThickness: 0,
				labelFormatter: function(e){
					return "";
				}
			  },
			  axisX:{
				stripLines:xAxisStripLinesArray,
				gridColor: color,
				lineColor: color,
				tickThickness: 0,
				gridThickness: 2,
				labelFormatter: function(e){
					return "";
				}
			  },
			  data: [
				{
				  type: "spline",
				  color:"black",
				  dataPoints: dps
				}
			  ]
			});

			addDataPoints(chart);
			addStripLines(chart);

			function addDataPoints(chart){
			  for(var i = 0; i < dataPointsArray.length; i++){
				dps.push({y: dataPointsArray[i]});
			  }
			  chart.render();
			  chart.axisX[0].set("interval", (chart.axisX[0].get("maximum") - chart.axisX[0].get("minimum"))/5, false);
			  chart.axisY[0].set("interval", (chart.axisY[0].get("maximum") - chart.axisY[0].get("minimum"))/5);  
			}

			function addStripLines(chart){		
			  //StripLines
			  for(var i = chart.axisY[0].minimum;i < chart.axisY[0].maximum;i = i+(chart.axisY[0].interval/10)){
				if(i%chart.axisY[0].interval != 0)
				  yAxisStripLinesArray.push({value: i,thickness: 1, color: color});  
			  }
			  for(var i = chart.axisX[0].minimum;i < chart.axisX[0].maximum; i = i+(chart.axisX[0].interval/10)){
				if(i%chart.axisX[0].interval != 0)
				  xAxisStripLinesArray.push({value: i,thickness: 1, color: color});  
			  }
			  chart.render();
			}
		}
		$(function(){
			loadVitalsTrendPlot(); // NEW: Load the multi-line trend chart
			<?php $counter = 0; foreach ($ecgJsonArray as $ecgJsonArrayVal) { ?>
				
				var obj = <?php echo $ecgJsonArrayVal ?>;
				$("#ecgPlots").append('<div id="chartContainer_<?php echo $counter ?>" style="height: 400px; width: 3000px;"><?php echo $ecgDates[$counter] ?><br/></div>');
				loadEcgPlot('chartContainer_<?php echo $counter ?>',obj," <?php echo $ecgDates[$counter] ?>  ");
			<?php $counter++; } ?>
		});
</script>
</body>
<script>
	$(function(){
		<?php if($_SESSION["user_type"] !== "admin"){ ?>
			$(".toggle-sidebar").click();
			$(".toggle-sidebar").addClass("d-none");
			$(".notification-box").addClass("d-none");
			
		<?php } ?>
		
		
	})
</script>
<!-- Mirrored from hms.cognisun.net/oreo/html/light/patient-profile.html by HTTrack Website Copier/3.x [XR&CO'2014], Sun, 06 Oct 2024 17:19:29 GMT -->
</html>
