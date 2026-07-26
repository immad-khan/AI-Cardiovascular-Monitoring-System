<?php
// api/chat.php
session_start();
header('Content-Type: application/json');

include("../config/DB_Config.php");

if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['patient', 'doctor'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$userType = $_SESSION['user_type'];
$userId = (string)$_SESSION['user_id'];

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

switch ($action) {

    // ---- Send a message ----
    case 'send':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'POST required']);
            exit();
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $message = trim($input['message'] ?? '');
        $patientId = trim($input['patient_id'] ?? '');
        $receiverId = trim($input['receiver_id'] ?? '');

        if (empty($message) || empty($patientId) || empty($receiverId)) {
            echo json_encode(['success' => false, 'message' => 'Missing fields']);
            exit();
        }

        if ($userType === 'patient') {
            $senderId = $patientId;
            $receiverType = 'doctor';
        } else {
            $senderId = (string)$userId;
            $receiverType = 'patient';
        }

        try {
            $stmt = $conn->prepare("INSERT INTO chat_messages (sender_type, sender_id, receiver_type, receiver_id, patient_id, message) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userType, $senderId, $receiverType, $receiverId, $patientId, $message]);
            echo json_encode(['success' => true, 'id' => $conn->lastInsertId('chat_messages_id_seq')]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'DB error']);
        }
        exit;

    // ---- Fetch messages for a conversation ----
    case 'fetch':
        $patientId = $_GET['patient_id'] ?? '';
        $otherId = $_GET['other_id'] ?? '';

        if (empty($patientId)) {
            echo json_encode(['success' => false, 'message' => 'Missing patient_id']);
            exit();
        }

        try {
            if ($userType === 'patient') {
                $stmt = $conn->prepare("
                    SELECT id, sender_type, sender_id, message, is_read, created_at 
                    FROM chat_messages 
                    WHERE patient_id = ? AND (sender_id = ? OR receiver_id = ?)
                    ORDER BY created_at ASC
                ");
                $stmt->execute([$patientId, $patientId, $patientId]);
            } else {
                if (empty($otherId)) {
                    echo json_encode(['success' => false, 'message' => 'Missing other_id']);
                    exit();
                }
                $stmt = $conn->prepare("
                    SELECT id, sender_type, sender_id, message, is_read, created_at 
                    FROM chat_messages 
                    WHERE patient_id = ? AND (sender_id = ? OR receiver_id = ?)
                    ORDER BY created_at ASC
                ");
                $stmt->execute([$patientId, $otherId, $otherId]);
            }
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'messages' => $messages]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'DB error']);
        }
        exit;

    // ---- Mark messages as read ----
    case 'mark_read':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'POST required']);
            exit();
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $patientId = trim($input['patient_id'] ?? '');
        $senderId = trim($input['sender_id'] ?? '');

        if (empty($patientId) || empty($senderId)) {
            echo json_encode(['success' => false, 'message' => 'Missing fields']);
            exit();
        }

        try {
            $stmt = $conn->prepare("UPDATE chat_messages SET is_read = TRUE WHERE patient_id = ? AND sender_id = ? AND receiver_id = ? AND is_read = FALSE");
            if ($userType === 'patient') {
                $stmt->execute([$patientId, $senderId, $patientId]);
            } else {
                $stmt->execute([$patientId, $senderId, (string)$userId]);
            }
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'DB error']);
        }
        exit;

    // ---- List conversations (for doctor) ----
    case 'list_conversations':
        try {
            $stmt = $conn->prepare("
                SELECT DISTINCT
                    p.\"patientID\",
                    p.name,
                    p.gender,
                    COALESCE(p.profile_picture, '') as profile_picture,
                    (SELECT cm.message FROM chat_messages cm 
                     WHERE cm.patient_id = p.\"patientID\" 
                     ORDER BY cm.created_at DESC LIMIT 1) as last_message,
                    (SELECT cm.created_at FROM chat_messages cm 
                     WHERE cm.patient_id = p.\"patientID\" 
                     ORDER BY cm.created_at DESC LIMIT 1) as last_time,
                    (SELECT COUNT(*) FROM chat_messages cm 
                     WHERE cm.patient_id = p.\"patientID\" 
                     AND cm.sender_type = 'patient' 
                     AND cm.receiver_id = ?
                     AND cm.is_read = FALSE) as unread_count
                FROM patients p
                WHERE p.\"assignedDoctorID\" = ?
                AND p.\"isActive\" = TRUE
                ORDER BY last_time DESC NULLS LAST
            ");
            $stmt->execute([$userId, $userId]);
            $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'conversations' => $conversations]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'DB error']);
        }
        exit;

    // ---- Get unread counts (for patient sidebar) ----
    case 'unread_count':
        try {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM chat_messages WHERE receiver_id = ? AND receiver_type = ? AND is_read = FALSE");
            $stmt->execute([$userId, $userType]);
            $count = (int)$stmt->fetchColumn();
            echo json_encode(['success' => true, 'count' => $count]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'count' => 0]);
        }
        exit;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
        exit;
}
?>
