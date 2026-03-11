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
    $hr = isset($_POST["heartRate"]) ? (int)$_POST["heartRate"] : null;
    $spo2 = isset($_POST["SpO2"]) ? (float)$_POST["SpO2"] : null;
    $temp = isset($_POST["Temperature"]) ? (float)$_POST["Temperature"] : null;
    $resp = isset($_POST["RespirationRate"]) ? (int)$_POST["RespirationRate"] : null;
    $ecg_raw = isset($_POST["ECG_Raw"]) ? $_POST["ECG_Raw"] : null;

    $conn->beginTransaction();

    // Get DeviceID
    $stmt = $conn->prepare('SELECT "deviceID" FROM monitoring_devices WHERE mac_address = ?');
    $stmt->execute([$mac]);
    $device = $stmt->fetch();
    
    if (!$device) {
        $stmt = $conn->prepare('INSERT INTO monitoring_devices (mac_address, status) VALUES (?, ''Online'') RETURNING "deviceID"');
        $stmt->execute([$mac]);
        $device = $stmt->fetch();
    }
    $deviceID = $device["deviceID"];

    // Insert Vitals
    $stmt = $conn->prepare('INSERT INTO vital_sign_readings 
        ("deviceID", "heartRate", "SpO2", "RespirationImpedance", device_type) 
        VALUES (?, ?, ?, ?, ''Raspberry Pi 4'') RETURNING "readingID"');
    $stmt->execute([$deviceID, $hr, $spo2, $resp]);
    $reading = $stmt->fetch();
    $readingID = $reading["readingID"];

    // Alerts
    if ($spo2 !== null && $spo2 < 90) {
        $msg = "CRITICAL: Low SpO2 detected ($spo2%) for device $mac";
        $stmt = $conn->prepare('INSERT INTO "CRITICAL_ALERT" ("deviceID", message, status) VALUES (?, ?, ''Active'')');
        $stmt->execute([$deviceID, $msg]);
        sendCriticalSMS("+92XXXXXXXXXX", $msg);
    }

    $conn->commit();
    echo json_encode(["success" => true, "readingID" => $readingID, "message" => "Data synced to Supabase."]);
} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>