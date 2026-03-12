<?php
include("../config/DB_Config.php");
session_start();

if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'tech-admin')) {
    header("Content-Type: application/json");
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deviceID = $_POST['deviceID'] ?? null;
    $status = $_POST['status'] ?? null;
    $patientID = $_POST['patientID'] ?? null;

    if (!$deviceID) {
        header("Content-Type: application/json");
        echo json_encode(['success' => false, 'message' => 'Device ID missing']);
        exit();
    }

    try {
        // Handle empty patientID (unassigning)
        if ($patientID === "") {
            $patientID = null;
        }

        $sql = "UPDATE monitoring_devices SET status = ?, \"patientID\" = ? WHERE \"deviceID\" = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$status, $patientID, $deviceID]);

        header("Content-Type: application/json");
        echo json_encode(['success' => true, 'message' => 'Device updated successfully']);
    } catch (PDOException $e) {
        header("Content-Type: application/json");
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}
?>
