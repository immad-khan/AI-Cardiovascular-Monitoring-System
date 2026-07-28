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
$username = $_SESSION['username'] ?? '';
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

try {
    // 1. Resolve Patient ID
    $patientID = null;
    if ($patientEmail) {
        $stmt = $conn->prepare('SELECT "patientID" FROM patients WHERE LOWER(email) = LOWER(?)');
        $stmt->execute([$patientEmail]);
        $patientID = $stmt->fetchColumn();
    }
    if (!$patientID && $username) {
        $stmt = $conn->prepare('SELECT "patientID" FROM patients WHERE "patientID" = ? OR LOWER(email) = LOWER(?)');
        $stmt->execute([$username, $username]);
        $patientID = $stmt->fetchColumn();
    }
    if (!$patientID) {
        $patientID = $username;
    }

    // 2. Find or link monitoring device for this patient
    $stmt = $conn->prepare('SELECT "deviceID", COALESCE("is_monitoring", FALSE) as is_monitoring FROM monitoring_devices WHERE "patientID" = ? LIMIT 1');
    $stmt->execute([$patientID]);
    $device = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fallback A: Check latest vitals reading for device
    if (!$device && $patientID) {
        $stmt = $conn->prepare('SELECT "deviceID" FROM vital_sign_readings WHERE "patientID" = ? ORDER BY timestamp DESC LIMIT 1');
        $stmt->execute([$patientID]);
        $devId = $stmt->fetchColumn();
        if ($devId) {
            $conn->prepare('UPDATE monitoring_devices SET "patientID" = ? WHERE "deviceID" = ?')->execute([$patientID, $devId]);
            $stmt = $conn->prepare('SELECT "deviceID", COALESCE("is_monitoring", FALSE) as is_monitoring FROM monitoring_devices WHERE "deviceID" = ?');
            $stmt->execute([$devId]);
            $device = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }

    // Fallback B: Pick any available device in system and link to patient
    if (!$device) {
        $stmt = $conn->query('SELECT "deviceID", COALESCE("is_monitoring", FALSE) as is_monitoring FROM monitoring_devices ORDER BY "deviceID" ASC LIMIT 1');
        $device = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($device && $patientID) {
            $conn->prepare('UPDATE monitoring_devices SET "patientID" = ? WHERE "deviceID" = ?')->execute([$patientID, $device['deviceID']]);
        }
    }

    // Fallback C: Create device row if table is empty
    if (!$device) {
        $stmt = $conn->prepare("INSERT INTO monitoring_devices (mac_address, status, \"patientID\", is_monitoring) VALUES ('00:00:00:00:00:00', 'Online', ?, TRUE) RETURNING \"deviceID\", \"is_monitoring\"");
        $stmt->execute([$patientID]);
        $device = $stmt->fetch(PDO::FETCH_ASSOC);
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
        $boolVal = $newStatus ? 'true' : 'false';

        // Update target device
        $update = $conn->prepare('UPDATE monitoring_devices SET "is_monitoring" = ?::boolean WHERE "deviceID" = ?');
        $update->execute([$boolVal, $device['deviceID']]);

        // Also update any devices linked to this patientID
        if ($patientID) {
            $conn->prepare('UPDATE monitoring_devices SET "is_monitoring" = ?::boolean WHERE "patientID" = ?')
                 ->execute([$boolVal, $patientID]);
        }

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

