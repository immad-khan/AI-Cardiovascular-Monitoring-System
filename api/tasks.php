<?php
/**
 * API Endpoint: /api/tasks.php
 * Purpose: Handle doctor task status updates (Reviewed / Escalated)
 */
header("Content-Type: application/json");
include_once("../config/DB_Config.php");
session_start();

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'doctor') {
    echo json_encode(["success" => false, "message" => "Unauthorized."]);
    exit();
}

$doctorID = $_SESSION['user_id'];
$taskID   = $_POST['taskID'] ?? null;
$status   = $_POST['status'] ?? null; // 'Reviewed' or 'Escalated'
$notes    = $_POST['notes'] ?? '';

if (!$taskID || !$status || !in_array($status, ['Reviewed', 'Escalated'])) {
    echo json_encode(["success" => false, "message" => "Invalid parameters."]);
    exit();
}

try {
    // Verify the task belongs to this doctor
    $check = $conn->prepare('SELECT "taskID" FROM doctor_tasks WHERE "taskID" = ? AND "doctorID" = ?');
    $check->execute([$taskID, $doctorID]);
    if (!$check->fetch()) {
        echo json_encode(["success" => false, "message" => "Task not found."]);
        exit();
    }

    $stmt = $conn->prepare('UPDATE doctor_tasks SET status = ?, notes = ?, updated_at = NOW() WHERE "taskID" = ?');
    $stmt->execute([$status, $notes, $taskID]);

    echo json_encode(["success" => true, "message" => "Task marked as $status."]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
