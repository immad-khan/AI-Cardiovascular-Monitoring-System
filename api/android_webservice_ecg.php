<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
include("./config/DB_Config.php");

// Check if the required fields are set
if (isset($_POST['mac_address'], $_POST['final_prediction'], $_POST['PDR'], $_POST['PLR'], $_POST['Delay'],$_POST['AvgDelay'], $_POST['Throughput'], $_POST['reading_duration'],$_POST['fileSizeInBytes'],$_POST['totalReceivedPackets'],$_POST['processingStartTime'])) {
    
    include_once("../config/DB_Config.php");

    // Retrieve data from POST
    $mac_address = $conn->real_escape_string($_POST['mac_address']);
    $final_prediction = $conn->real_escape_string($_POST['final_prediction']);
    $PDR = $conn->real_escape_string($_POST['PDR']);
    $PLR = $conn->real_escape_string($_POST['PLR']);
    
    // Vitals Expansion (Hardware Data Flow)
    $heartRate = isset($_POST['heartRate']) ? (int)$_POST['heartRate'] : null;
    $spO2 = isset($_POST['SpO2']) ? (float)$_POST['SpO2'] : null;
    $temp = isset($_POST['Temperature']) ? (float)$_POST['Temperature'] : null;
    $resp = isset($_POST['RespirationRate']) ? (int)$_POST['RespirationRate'] : null;
    $confidence = isset($_POST['confidenceScore']) ? (float)$_POST['confidenceScore'] : 0.0;

    if($PDR  > 100){
        $PDR = 100;
        $PLR = 0;
    }
    $Delay = $conn->real_escape_string($_POST['Delay']) ;
    $AvgDelay = $conn->real_escape_string($_POST['AvgDelay']) ;
    
    $Throughput = $conn->real_escape_string($_POST['Throughput']);
    $reading_duration = $conn->real_escape_string($_POST['reading_duration']);
    $fileSizeInBytes = $conn->real_escape_string($_POST['fileSizeInBytes']);
    $totalReceivedPackets = $conn->real_escape_string($_POST['totalReceivedPackets']);
    $processingStartTime = $conn->real_escape_string($_POST['processingStartTime']);

    // SQL query to insert data
    $stmt = $conn->prepare("INSERT INTO esp_ecg_predictions (mac_address, final_prediction, confidenceScore, PDR, PLR, HighestDelay, AvgDelay, Throughput, reading_duration, fileSizeInBytes, totalReceivedSamples, processingStartTime, device, heartRate, SpO2, Temperature, RespirationRate)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Edge Gateway', ?, ?, ?, ?)");
    
    $stmt->bind_param("ssdsssssssssiid i", $mac_address, $final_prediction, $confidence, $PDR, $PLR, $Delay, $AvgDelay, $Throughput, $reading_duration, $fileSizeInBytes, $totalReceivedPackets, $processingStartTime, $heartRate, $spO2, $temp, $resp);

    if ($stmt->execute()) {
        // Critical Alert Check (Simplified)
        if ($spO2 !== null && $spO2 < 90) {
            $alertMsg = "Critical SpO2 Level Detected: " . $spO2 . "%";
            $conn->query("INSERT INTO CRITICAL_ALERT (mac_address, message) VALUES ('$mac_address', '$alertMsg')");
        }
        
        echo json_encode(["success" => true, "message" => "Vital signs synchronized successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Error: " . $stmt->error]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Required fields are missing."]);
}

// Close the connection
$conn->close();
?>
