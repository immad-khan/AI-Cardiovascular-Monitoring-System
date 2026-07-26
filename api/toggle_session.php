<?php
// api/toggle_session.php
session_start();
header('Content-Type: application/json');
include('../config/DB_Config.php');

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'patient') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$patientEmail = $_SESSION['email'] ?? '';
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

try {
    // 1. Get patient's patientID
    $stmt = $conn->prepare('SELECT "patientID" FROM patients WHERE email = ?');
    $stmt->execute([$patientEmail]);
    $patientID = $stmt->fetchColumn();

    if (!$patientID) {
        echo json_encode(['success' => false, 'message' => 'Patient record not found.']);
        exit();
    }

    // 2. Get patient's assigned monitoring device (default session is OFF / FALSE)
    $stmt = $conn->prepare('SELECT "deviceID", COALESCE("is_monitoring", FALSE) as is_monitoring FROM monitoring_devices WHERE "patientID" = ?');
    $stmt->execute([$patientID]);
    $device = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fallback: If device not linked directly in monitoring_devices, link patient's latest reading device or primary device
    if (!$device) {
        $stmt = $conn->prepare('SELECT "deviceID" FROM vital_sign_readings WHERE "patientID" = ? ORDER BY timestamp DESC LIMIT 1');
        $stmt->execute([$patientID]);
        $deviceID = $stmt->fetchColumn();
        
        if (!$deviceID) {
            $stmt = $conn->query('SELECT "deviceID" FROM monitoring_devices ORDER BY "deviceID" ASC LIMIT 1');
            $deviceID = $stmt->fetchColumn();
        }

        if ($deviceID) {
            $conn->prepare('UPDATE monitoring_devices SET "patientID" = ? WHERE "deviceID" = ?')->execute([$patientID, $deviceID]);
            $stmt = $conn->prepare('SELECT "deviceID", COALESCE("is_monitoring", FALSE) as is_monitoring FROM monitoring_devices WHERE "patientID" = ?');
            $stmt->execute([$patientID]);
            $device = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }

    if (!$device) {
        echo json_encode(['success' => false, 'message' => 'No monitoring device assigned to your account.']);
        exit();
    }

    if ($action === 'status') {
        echo json_encode([
            'success' => true,
            'is_monitoring' => (bool)$device['is_monitoring'],
            'deviceID' => $device['deviceID']
        ]);
        exit();
    }

    if ($action === 'toggle' || $action === 'start' || $action === 'stop') {
        $newStatus = ($action === 'start') ? true : (($action === 'stop') ? false : !(bool)$device['is_monitoring']);
        
        $update = $conn->prepare('UPDATE monitoring_devices SET "is_monitoring" = ? WHERE "deviceID" = ?');
        $update->execute([$newStatus ? 1 : 0, $device['deviceID']]);

        echo json_encode([
            'success' => true,
            'is_monitoring' => $newStatus,
            'message' => $newStatus ? 'ECG Monitoring Session Started!' : 'ECG Monitoring Session Stopped.'
        ]);
        exit();
    }

    echo json_encode(['success' => false, 'message' => 'Invalid action']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
