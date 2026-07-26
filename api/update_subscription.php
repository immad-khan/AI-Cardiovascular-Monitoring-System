<?php
session_start();
header('Content-Type: application/json');
include('../config/DB_Config.php');

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$id     = intval($_POST['id'] ?? 0);
$status = trim($_POST['status'] ?? '');

if (!$id || !in_array($status, ['approved', 'rejected', 'pending'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

try {
    $stmt = $conn->prepare('UPDATE subscriptions SET status = ? WHERE id = ?');
    $stmt->execute([$status, $id]);
    echo json_encode(['success' => true, 'message' => ucfirst($status) . ' successfully.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
}
