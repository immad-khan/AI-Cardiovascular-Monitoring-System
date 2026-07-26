<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit();
}

include_once(__DIR__ . '/../config/DB_Config.php');
include_once(__DIR__ . '/../config/cloudinary_config.php');

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$age = intval($_POST['age'] ?? 0);
$gender = trim($_POST['gender'] ?? '');
$note = trim($_POST['note'] ?? '');

if (empty($name) || empty($email) || empty($phone) || $age <= 0 || empty($gender)) {
    echo json_encode(['status' => 'error', 'message' => 'All required fields must be filled.']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email address.']);
    exit();
}

$paymentUrl = '';
$paymentPublicId = '';

if (isset($_FILES['payment_screenshot']) && $_FILES['payment_screenshot']['error'] === UPLOAD_ERR_OK) {
    $result = uploadToCloudinary($_FILES['payment_screenshot'], 'digihealth/subscriptions');
    if (isset($result['error'])) {
        echo json_encode(['status' => 'error', 'message' => 'Payment screenshot upload failed: ' . $result['error']]);
        exit();
    }
    $paymentUrl = $result['url'];
    $paymentPublicId = $result['public_id'];
}

try {
    $stmt = $conn->prepare('INSERT INTO subscriptions (name, email, phone, age, gender, note, payment_screenshot_url, payment_screenshot_public_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$name, $email, $phone, $age, $gender, $note, $paymentUrl, $paymentPublicId, 'pending']);

    echo json_encode(['status' => 'success', 'message' => 'Subscription submitted successfully.']);
} catch (PDOException $e) {
    error_log('Subscription insert error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Failed to save subscription. Please try again.']);
}
?>
