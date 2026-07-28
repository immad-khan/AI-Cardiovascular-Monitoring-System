<?php
/**
 * API: /api/generate_patient_id.php
 * Returns a unique, auto-generated Patient ID (DH-XXXX format)
 * Checks the database to avoid any collisions
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['admin', 'doctor'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

include_once(__DIR__ . '/../config/DB_Config.php');

try {
    // Get all existing patientIDs that match our DH-XXXX pattern
    $stmt = $conn->query("SELECT \"patientID\" FROM patients WHERE \"patientID\" LIKE 'DH-%' ORDER BY \"patientID\" DESC");
    $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Extract numeric parts and find the max
    $maxNum = 0;
    foreach ($existing as $pid) {
        if (preg_match('/^DH-(\d+)$/', $pid, $m)) {
            $maxNum = max($maxNum, intval($m[1]));
        }
    }

    // Generate next candidate
    $candidate = '';
    $found = false;
    for ($i = 1; $i <= 1000; $i++) {
        $next = $maxNum + $i;
        $candidate = 'DH-' . str_pad($next, 4, '0', STR_PAD_LEFT);

        // Double-check it really doesn't exist
        $check = $conn->prepare("SELECT 1 FROM patients WHERE \"patientID\" = ?");
        $check->execute([$candidate]);
        if (!$check->fetch()) {
            $found = true;
            break;
        }
    }

    if (!$found) {
        echo json_encode(['success' => false, 'message' => 'Could not generate a unique Patient ID']);
        exit();
    }

    echo json_encode(['success' => true, 'patient_id' => $candidate]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
}
