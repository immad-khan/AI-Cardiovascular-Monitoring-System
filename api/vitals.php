<?php
/**
 * API Endpoint: /api/vitals.php
 * Purpose: Receive full sensor payload from Raspberry Pi Edge Gateway
 * Sensors: ADS1292R (ECG/Resp), MAX30102 (SpO2/Pulse), MLX90614 (Temp)
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

include_once("../config/DB_Config.php");
include_once("../backend/ai_inference_service.php");
include_once("../backend/notification_service.php");

// Required Hardware Identification
if (!isset($_POST['mac_address'])) {
    echo json_encode(["success" => false, "message" => "MAC address is required for device identification."]);
    exit();
}

$mac_address = $conn->real_escape_string($_POST['mac_address']);

// Hardware Payload Extraction
$heartRate = isset($_POST['heartRate']) ? (int)$_POST['heartRate'] : null;
$spO2 = isset($_POST['SpO2']) ? (float)$_POST['SpO2'] : null;
$temperature = isset($_POST['Temperature']) ? (float)$_POST['Temperature'] : null;
$respirationRate = isset($_POST['RespirationRate']) ? (int)$_POST['RespirationRate'] : null;
$ecg_raw = isset($_POST['ECG_Raw']) ? $_POST['ECG_Raw'] : null; // Can be a CSV string of values

// AI Prediction Data (Optional from Edge)
$final_prediction = isset($_POST['prediction']) ? $conn->real_escape_string($_POST['prediction']) : 'Processing';
$confidenceScore = isset($_POST['confidence']) ? (float)$_POST['confidence'] : 0.0;

// Network Metrics
$pdr = isset($_POST['PDR']) ? (float)$_POST['PDR'] : 100.0;
$plr = isset($_POST['PLR']) ? (float)$_POST['PLR'] : 0.0;

$conn->begin_transaction();

try {
    // 1. Insert into esp_ecg_predictions (VITAL_SIGN_READING)
    $stmt = $conn->prepare("INSERT INTO esp_ecg_predictions 
        (mac_address, final_prediction, confidenceScore, heartRate, SpO2, Temperature, RespirationRate, PDR, PLR, device) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Raspberry Pi 4')");
    
    $stmt->bind_param("ssdiidd d", $mac_address, $final_prediction, $confidenceScore, $heartRate, $spO2, $temperature, $respirationRate, $pdr, $plr);
    $stmt->execute();
    $record_id = $conn->insert_id;

    // 2. Insert Raw ECG Samples (if provided)
    if ($ecg_raw) {
        $samples = explode(',', $ecg_raw);
        $sample_stmt = $conn->prepare("INSERT INTO esp_ecg_data (record_id, ecg_value) VALUES (?, ?)");
        foreach ($samples as $val) {
            $float_val = (float)$val;
            $sample_stmt->bind_param("id", $record_id, $float_val);
            $sample_stmt->execute();
        }
    }

    // 3. Automated Critical Alert Logic
    if ($spO2 !== null && $spO2 < 90) {
        $msg = "CRITICAL: Low SpO2 level detected ($spO2%) for device $mac_address";
        $conn->query("INSERT INTO CRITICAL_ALERT (mac_address, message, status) VALUES ('$mac_address', '$msg', 'Active')");
        sendCriticalSMS("+92XXXXXXXXXX", $msg); // Placeholder for Primary In-Charge
    }
    
    if ($heartRate !== null && ($heartRate > 120 || $heartRate < 50)) {
        $msg = "ALERT: Abnormal Heart Rate ($heartRate BPM) detected for device $mac_address";
        $conn->query("INSERT INTO CRITICAL_ALERT (mac_address, message, status) VALUES ('$mac_address', '$msg', 'Active')");
    }

    // 4. Hook for AI Inference
    $vitalsPayload = [
        'heartRate' => $heartRate,
        'SpO2' => $spO2,
        'Temperature' => $temperature,
        'RespirationRate' => $respirationRate
    ];
    processAIPrediction($conn, $record_id, $vitalsPayload);

    $conn->commit();
    
    // NEW: Refresh $record_id metadata if needed (optional)
    
    echo json_encode(["success" => true, "record_id" => $record_id, "message" => "Vitals, ECG, and AI Logs processed successfully.", "alert_triggered" => ($spO2 < 90 || $heartRate > 120 || $heartRate < 50)]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}

$conn->close();
?>
