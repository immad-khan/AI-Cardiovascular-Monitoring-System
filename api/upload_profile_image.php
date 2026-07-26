<?php
// api/upload_profile_image.php
session_start();
header('Content-Type: application/json');

include("../config/DB_Config.php");
include("../config/cloudinary_config.php");

if (!isset($_SESSION['user_type'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No image file provided']);
    exit();
}

$userType = $_SESSION['user_type'];
$userId = $_SESSION['user_id'];

// Upload to Cloudinary
$result = uploadToCloudinary($_FILES['profile_image'], 'digihealth/profiles');

if (isset($result['error'])) {
    echo json_encode(['success' => false, 'message' => $result['error']]);
    exit();
}

$imageUrl = $result['url'];

try {
    if ($userType === 'admin') {
        // For admin, we store in the users table - we'll add a profile_picture column
        // First check if column exists, if not we store in session for now
        try {
            $stmt = $conn->prepare('ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(500)');
            $stmt->execute();
        } catch (PDOException $e) {
            // Column might already exist, ignore
        }
        $stmt = $conn->prepare('UPDATE users SET profile_picture = ? WHERE "userID" = ?');
        $stmt->execute([$imageUrl, $userId]);
        $_SESSION['profile_picture'] = $imageUrl;

    } elseif ($userType === 'doctor') {
        // For doctor, update doctorProfile table
        $stmt = $conn->prepare('UPDATE "doctorProfile" SET profile_picture = ? WHERE "userID" = ?');
        $stmt->execute([$imageUrl, $userId]);
        $_SESSION['profile_picture'] = $imageUrl;

    } elseif ($userType === 'patient') {
        // For patient, update patients table - need to add profile_picture column
        try {
            $stmt = $conn->prepare('ALTER TABLE patients ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(500)');
            $stmt->execute();
        } catch (PDOException $e) {
            // Column might already exist, ignore
        }
        // Find patient by email
        $stmt = $conn->prepare('UPDATE patients SET profile_picture = ? WHERE email = ?');
        $stmt->execute([$imageUrl, $_SESSION['email']]);
        $_SESSION['profile_picture'] = $imageUrl;

    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid user type']);
        exit();
    }

    echo json_encode([
        'success' => true,
        'message' => 'Profile image updated successfully',
        'url' => $imageUrl,
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
