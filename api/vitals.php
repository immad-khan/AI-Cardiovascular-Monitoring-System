<?php
/**
 * API Endpoint: /api/vitals.php (Supabase/Postgres Version)
 */
error_reporting(E_ALL);
ini_set("display_errors", 1);
header("Content-Type: application/json");

include_once("../config/DB_Config.php");
include_once("../backend/notification_service.php");

if (!isset($_POST["mac_address"])) {
    echo json_encode(["success" => false, "message" => "MAC address required."]);
    exit();
}

try {
    $mac = $_POST["mac_address"];
    $hr = isset($_POST["heartRate"]) ? (int) $_POST["heartRate"] : null;
    $temp = isset($_POST["Temperature"]) ? (float) $_POST["Temperature"] : null;
    $resp = isset($_POST["RespirationRate"]) ? (int) $_POST["RespirationRate"] : null;
    $ecg_raw = isset($_POST["ECG_Raw"]) ? $_POST["ECG_Raw"] : null;

    $conn->beginTransaction();

    // Get DeviceID
    $stmt = $conn->prepare('SELECT "deviceID" FROM monitoring_devices WHERE mac_address = ?');
    $stmt->execute([$mac]);
    $device = $stmt->fetch();

    if (!$device) {
        $stmt = $conn->prepare("INSERT INTO monitoring_devices (mac_address, status) VALUES (?, 'Online') RETURNING \"deviceID\"");
        $stmt->execute([$mac]);
        $device = $stmt->fetch();
    }
    $deviceID = $device["deviceID"];

    // --- AI Inference Block ---
    $ai_prediction = null;
    $confidence = 0.0;
    $inference_time = 0;
    $hrv_sdnn = null;
    $hrv_rmssd = null;
    $signal_quality = null;
    $arrhythmia_flags = null;

    if ($ecg_raw) {
        $python_path = "python";
        $script_path = "../Ai-Model/predict.py";
        $command = "python $script_path " . escapeshellarg($ecg_raw);
        $output = shell_exec($command);
        $ai_result = json_decode($output, true);
        if ($ai_result && isset($ai_result['success']) && $ai_result['success']) {
            $ai_prediction = $ai_result['predictionClass'];
            $confidence = $ai_result['confidenceScore'];
            $inference_time = $ai_result['inference_time_ms'];
            if (isset($ai_result['heartRate']) && $ai_result['heartRate'] > 0) {
                $hr = (int) round($ai_result['heartRate']);
            }
            // New Phase 1 fields
            $hrv_sdnn = $ai_result['hrv_sdnn'] ?? null;
            $hrv_rmssd = $ai_result['hrv_rmssd'] ?? null;
            $signal_quality = isset($ai_result['signal_quality']) ? (int)$ai_result['signal_quality'] : null;
            $arrhythmia_flags = !empty($ai_result['arrhythmia_flags']) ? implode('; ', $ai_result['arrhythmia_flags']) : null;
        }
    }

    // Insert Vitals (including new HRV + signal quality fields)
    $stmt = $conn->prepare('INSERT INTO vital_sign_readings 
        ("deviceID", "heartRate", "RespirationImpedance", device_type, final_prediction, "confidenceScore", hrv_sdnn, hrv_rmssd, signal_quality, arrhythmia_flags) 
        VALUES (?, ?, ?, \'Raspberry Pi 4\', ?, ?, ?, ?, ?, ?) RETURNING "readingID"');
    $stmt->execute([$deviceID, $hr, $resp, $ai_prediction, $confidence, $hrv_sdnn, $hrv_rmssd, $signal_quality, $arrhythmia_flags]);
    $reading = $stmt->fetch();
    $readingID = $reading["readingID"];

    // 3. Save Raw ECG to esp_ecg_data
    if ($ecg_raw) {
        $samples = json_decode($ecg_raw, true);
        if (!$samples) $samples = explode(',', $ecg_raw);
        if (is_array($samples) && !empty($samples)) {
            $slice = array_slice($samples, 0, 500);
            $placeholders = [];
            $params = [];
            foreach ($slice as $val) {
                $placeholders[] = '(?, ?)';
                $params[] = $readingID;
                $params[] = (float) trim($val);
            }
            $sql = 'INSERT INTO esp_ecg_data ("readingID", ecg_value) VALUES ' . implode(', ', $placeholders);
            $sample_stmt = $conn->prepare($sql);
            $sample_stmt->execute($params);
        }
    }

    // Log AI Prediction
    if ($ai_prediction) {
        $stmt = $conn->prepare('INSERT INTO "AI_PREDICTION_LOG" ("readingID", "predictionClass", "confidenceScore", inference_time_ms) VALUES (?, ?, ?, ?)');
        $stmt->execute([$readingID, $ai_prediction, $confidence, $inference_time]);
    }

    // Helper logic to find patient/doctor for alerts
    $patientInfo = null;
    $infoStmt = $conn->prepare('SELECT p."patientID", p."assignedDoctorID", u.email as doctor_email FROM monitoring_devices md JOIN patients p ON md."patientID" = p."patientID" LEFT JOIN users u ON p."assignedDoctorID" = u."userID" WHERE md."deviceID" = ?');
    $infoStmt->execute([$deviceID]);
    $patientInfo = $infoStmt->fetch(PDO::FETCH_ASSOC);

    // Alerts
    // AI based Critical Alert
    if ($ai_prediction && (strpos(strtolower($ai_prediction), 'ventricular') !== false || strpos(strtolower($ai_prediction), 'tachycardia') !== false)) {
        $msg = "AI ALERT: Abnormal Heart Rhythm detected ($ai_prediction) for device $mac";
        $stmt = $conn->prepare('INSERT INTO "CRITICAL_ALERT" ("deviceID", message, status) VALUES (?, ?, \'Active\') RETURNING "alertID"');
        $stmt->execute([$deviceID, $msg]);
        $alertRow = $stmt->fetch();
        if ($patientInfo && !empty($patientInfo['doctor_email'])) {
            sendEmail($patientInfo['doctor_email'], "AI Prediction Alert", "<p>$msg</p>");
        }
        
        if ($alertRow && $patientInfo && $patientInfo['assignedDoctorID']) {
            $taskStmt = $conn->prepare('INSERT INTO doctor_tasks ("doctorID", "patientID", "readingID", "alertID", "task_type") VALUES (?, ?, ?, ?, \'Review ECG AI Alert\')');
            $taskStmt->execute([$patientInfo['assignedDoctorID'], $patientInfo['patientID'], $readingID, $alertRow['alertID']]);
        }
    }

    $conn->commit();
    echo json_encode(["success" => true, "readingID" => $readingID, "ai" => ["prediction" => $ai_prediction, "confidence" => $confidence, "hr" => $hr], "message" => "Data analyzed and synced."]);
} catch (Exception $e) {
    if ($conn->inTransaction())
        $conn->rollBack();
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>