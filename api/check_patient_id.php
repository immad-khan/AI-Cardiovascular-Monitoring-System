<?php
/**
 * API: /api/check_patient_id.php
 * Checks in real-time if a patient ID is already taken
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['admin', 'doctor'])) {
    echo json_encode(['available' => false, 'message' => 'Unauthorized']);
    exit();
}

include_once(__DIR__ . '/../config/DB_Config.php');

$pid = trim($_GET['patient_id'] ?? '');

if (empty($pid)) {
    echo json_encode(['available' => false, 'message' => 'No patient ID provided']);
    exit();
}

try {
    $stmt = $conn->prepare("SELECT 1 FROM patients WHERE \"patientID\" = ?");
    $stmt->execute([$pid]);
    $exists = $stmt->fetch();

    echo json_encode([
        'available' => !$exists,
        'patient_id' => $pid,
        'message' => $exists ? "Patient ID '$pid' is already assigned." : "Patient ID '$pid' is available."
    ]);
} catch (PDOException $e) {
    echo json_encode(['available' => false, 'message' => 'DB error: ' . $e->getMessage()]);
}
