<?php
session_start();
include("../config/DB_Config.php");
include_once(__DIR__ . "/../backend/notification_service.php");

header('Content-Type: application/json');

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(["success" => false, "message" => "Access denied"]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid request method"]);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$entityId = $input['entity_id'] ?? '';
$entityEmail = $input['entity_email'] ?? '';
$entityType = $input['entity_type'] ?? '';
$newPassword = $input['new_password'] ?? '';

if (empty($entityId) || empty($entityEmail) || empty($newPassword)) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit();
}

if (strlen($newPassword) < 6) {
    echo json_encode(["success" => false, "message" => "Password must be at least 6 characters"]);
    exit();
}

try {
    // Hash the new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Find and update the user account by email
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ? AND role = ?");
    $stmt->execute([$hashedPassword, $entityEmail, $entityType]);
    $affected = $stmt->rowCount();

    if ($affected === 0) {
        echo json_encode(["success" => false, "message" => "No matching user account found"]);
        exit();
    }

    // Send email notification
    $subject = "DigiHealth - Your Password Has Been Changed";
    $message = "
    <html>
    <body style='font-family:Arial,sans-serif;padding:20px;'>
        <div style='max-width:500px;margin:0 auto;border:1px solid #e0e0e0;border-radius:10px;overflow:hidden;'>
            <div style='background:#1565c0;color:#fff;padding:20px;text-align:center;'>
                <h2 style='margin:0;'>DigiHealth</h2>
                <p style='margin:5px 0 0;opacity:0.8;'>Password Changed Notification</p>
            </div>
            <div style='padding:25px;'>
                <p>Hello <strong>" . htmlspecialchars(ucfirst($entityType)) . "</strong>,</p>
                <p>Your password has been updated by the administrator.</p>
                <div style='background:#f5f5f5;border-radius:8px;padding:15px;margin:15px 0;'>
                    <p style='margin:0;'><strong>Your new login credentials:</strong></p>
                    <p style='margin:5px 0 0;'>Email: <code>" . htmlspecialchars($entityEmail) . "</code></p>
                    <p style='margin:5px 0 0;'>New Password: <code style='background:#fff;padding:2px 6px;border-radius:4px;'>" . htmlspecialchars($newPassword) . "</code></p>
                </div>
                <p style='color:#e53935;font-size:13px;'>Please change this password after your first login for security.</p>
                <p style='color:#999;font-size:12px;margin-top:20px;'>If you did not request this change, please contact the administrator immediately.</p>
            </div>
            <div style='background:#f5f5f5;text-align:center;padding:12px;color:#999;font-size:11px;'>
                &copy; " . date('Y') . " CUST DigiHealth. All rights reserved.
            </div>
        </div>
    </body>
    </html>";

    $emailSent = sendEmail($entityEmail, $subject, $message);

    echo json_encode([
        "success" => true,
        "message" => "Password updated successfully" . ($emailSent ? ". Email notification sent." : ". Email could not be sent.")
    ]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
