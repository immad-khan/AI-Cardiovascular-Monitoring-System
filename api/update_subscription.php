<?php
/**
 * API: /api/update_subscription.php
 * Handles approve / reject / pending status changes for subscriptions.
 *
 * APPROVE  → updates DB to 'approved', returns redirect URL to add-patient
 * REJECT   → updates DB to 'rejected', sends rejection email with reason
 * PENDING  → resets status back to pending (AJAX JSON response)
 */
session_start();
header('Content-Type: application/json');
include('../config/DB_Config.php');

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$id     = intval($_POST['id'] ?? 0);
$status = trim($_POST['status'] ?? '');
$reason = trim($_POST['reason'] ?? '');

if (!$id || !in_array($status, ['approved', 'rejected', 'pending'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

try {
    // Fetch subscription details
    $sub_stmt = $conn->prepare('SELECT * FROM subscriptions WHERE id = ?');
    $sub_stmt->execute([$id]);
    $sub = $sub_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sub) {
        echo json_encode(['success' => false, 'message' => 'Subscription not found']);
        exit();
    }

    // Update status + reviewer metadata
    $adminId = $_SESSION['user_id'] ?? null;
    $upd = $conn->prepare(
        'UPDATE subscriptions SET status = ?, reviewed_at = NOW(), reviewed_by = ? WHERE id = ?'
    );
    $upd->execute([$status, $adminId, $id]);

    // ── APPROVE ─────────────────────────────────────────────────────────────
    if ($status === 'approved') {
        echo json_encode([
            'success'  => true,
            'message'  => 'Subscription approved.',
            'redirect' => '../frontend/add-patient.php?from_sub=' . $id
        ]);
        exit();
    }

    // ── REJECT ──────────────────────────────────────────────────────────────
    if ($status === 'rejected') {
        $patientName  = htmlspecialchars($sub['name']);
        $patientEmail = $sub['email'];
        $rejReason    = !empty($reason) ? htmlspecialchars($reason) : 'No specific reason provided.';

        // Send rejection email
        include_once(__DIR__ . '/../backend/notification_service.php');

        $subject = "DigiHealth – Your Subscription Request Was Not Approved";
        $htmlMessage = "
        <div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;background:#f9f9f9;border-radius:10px;overflow:hidden;'>
            <div style='background:linear-gradient(135deg,#e52d27,#b31217);padding:28px 32px;text-align:center;'>
                <h1 style='color:#fff;margin:0;font-size:22px;'>DigiHealth</h1>
                <p style='color:rgba(255,255,255,0.85);margin:6px 0 0;font-size:13px;'>AI-Powered Cardiovascular Monitoring</p>
            </div>
            <div style='padding:32px;background:#fff;'>
                <h2 style='color:#333;font-size:20px;margin-top:0;'>Hello, {$patientName}</h2>
                <p style='color:#555;line-height:1.7;'>Thank you for your interest in the <strong>DigiHealth</strong> platform. After reviewing your subscription request, we were unable to approve your account at this time.</p>
                <div style='background:#fff5f5;border-left:4px solid #e52d27;border-radius:6px;padding:16px 20px;margin:20px 0;'>
                    <strong style='color:#b31217;font-size:13px;text-transform:uppercase;letter-spacing:.5px;'>Reason for Rejection</strong>
                    <p style='margin:8px 0 0;color:#333;font-size:15px;'>{$rejReason}</p>
                </div>
                <p style='color:#555;line-height:1.7;'>If you believe this is an error or would like to reapply with updated information, please contact your healthcare provider or visit our website.</p>
                <p style='color:#555;line-height:1.7;'>We apologise for any inconvenience.</p>
                <hr style='border:none;border-top:1px solid #eee;margin:24px 0;'>
                <p style='color:#aaa;font-size:12px;text-align:center;'>DigiHealth · AI Cardiovascular Monitoring System<br>This is an automated message, please do not reply.</p>
            </div>
        </div>";

        $emailSent = sendEmail($patientEmail, $subject, $htmlMessage);

        // Persist rejection reason for record keeping
        try {
            $upd2 = $conn->prepare('UPDATE subscriptions SET rejection_reason = ? WHERE id = ?');
            $upd2->execute([$reason ?: 'No specific reason provided.', $id]);
        } catch (PDOException $ignored) {}

        echo json_encode([
            'success'    => true,
            'message'    => 'Subscription rejected.' . ($emailSent ? ' Rejection email sent to ' . $patientEmail . '.' : ' (Email could not be sent — check SMTP config)'),
            'email_sent' => $emailSent
        ]);
        exit();
    }

    // ── PENDING (reset) ──────────────────────────────────────────────────────
    echo json_encode(['success' => true, 'message' => 'Status reset to Pending.']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
}
