<?php
session_start();
header('Content-Type: application/json');

include("../config/DB_Config.php");

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'doctor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$userId = $_SESSION['user_id'];

$full_name = trim($input['full_name'] ?? '');
$specialization = trim($input['specialization'] ?? '');
$phone_number = trim($input['phone_number'] ?? '');
$description = trim($input['description'] ?? '');

if (empty($full_name) || empty($specialization)) {
    echo json_encode(['success' => false, 'message' => 'Full name and specialization are required.']);
    exit();
}

try {
    // Check if profile exists
    $check = $conn->prepare('SELECT 1 FROM "doctorProfile" WHERE "userID" = ?');
    $check->execute([$userId]);

    if ($check->fetch()) {
        $stmt = $conn->prepare('UPDATE "doctorProfile" SET full_name = ?, specialization = ?, phone_number = ?, description = ? WHERE "userID" = ?');
        $stmt->execute([$full_name, $specialization, $phone_number, $description, $userId]);
    } else {
        $stmt = $conn->prepare('INSERT INTO "doctorProfile" ("userID", full_name, specialization, phone_number, description) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$userId, $full_name, $specialization, $phone_number, $description]);
    }

    // Update session username if name changed
    $_SESSION['username'] = $full_name;

    echo json_encode(['success' => true, 'message' => 'Profile updated successfully.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
