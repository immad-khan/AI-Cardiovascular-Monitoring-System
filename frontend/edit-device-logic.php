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

        $sql = "UPDATE monitoring_devices SET status = ?, \"patientID\" = ? WHERE \"deviceID\" = ? RETURNING mac_address";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$status, $patientID, $deviceID]);
        $deviceRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($deviceRow) {
            $mac_address = $deviceRow['mac_address'];
            
            // De-link this device from any old patients in history
            $delink_dev_sql = "UPDATE device_patient_link SET delinked_at = CURRENT_TIMESTAMP WHERE mac_address = ? AND delinked_at IS NULL";
            $conn->prepare($delink_dev_sql)->execute([$mac_address]);

            // If assigned to a new patient, create a new link
            if ($patientID !== null) {
                // Also ensure this patient isn't actively linked to another device (optional, but consistent with add-patient)
                $delink_pat_sql = "UPDATE device_patient_link SET delinked_at = CURRENT_TIMESTAMP WHERE patient_id = ? AND delinked_at IS NULL";
                $conn->prepare($delink_pat_sql)->execute([$patientID]);

                $link_sql = "INSERT INTO device_patient_link (patient_id, mac_address) VALUES (?, ?)";
                $conn->prepare($link_sql)->execute([$patientID, $mac_address]);
            }
        }

        header("Content-Type: application/json");
        echo json_encode(['success' => true, 'message' => 'Device updated successfully']);
    } catch (PDOException $e) {
        header("Content-Type: application/json");
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}
?>
