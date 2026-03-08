<?php
// backend/doctor_logic.php
include_once("../config/DB_Config.php");

/**
 * Handles Doctor Registration and Profile Creation
 */
function handleDoctorRegistration($conn, $postData, $filesData, $sessionData) {
    // Sanitize and validate the input
    $username = htmlspecialchars(trim($postData['username']));
    $email = htmlspecialchars(trim($postData['email']));
    $password = htmlspecialchars(trim($postData['password']));
    $confirm_password = htmlspecialchars(trim($postData['confirm_password']));
    $full_name = htmlspecialchars(trim($postData['full_name']));
    $specialization = htmlspecialchars(trim($postData['specialization']));
    $phone_number = htmlspecialchars(trim($postData['phone_number']));
    $description = htmlspecialchars(trim($postData['description']));
    $website_url = htmlspecialchars(trim($postData['website_url']));
    $profile_picture = $filesData['profile_picture'];

    // Basic Validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($full_name) || empty($specialization) || empty($phone_number)) {
        return ["status" => "All fields are required", "type" => "error", "redirect" => "add-doctor.php"];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ["status" => "invalid_email", "type" => "error", "redirect" => "add-doctor.php"];
    }

    if ($password !== $confirm_password) {
        return ["status" => "password_mismatch", "type" => "error", "redirect" => "add-doctor.php"];
    }

    // Profile Picture Handling
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    $file_extension = pathinfo($profile_picture['name'], PATHINFO_EXTENSION);

    if ($profile_picture['error'] !== UPLOAD_ERR_OK) {
        return ["status" => "file_upload_error", "type" => "error", "redirect" => "add-doctor.php"];
    }

    if (!in_array(strtolower($file_extension), $allowed_extensions)) {
        return ["status" => "Invalid File Type", "type" => "error", "redirect" => "add-doctor.php"];
    }

    $upload_directory = '../uploads/';
    if (!is_dir($upload_directory)) {
        mkdir($upload_directory, 0777, true);
    }
    
    $profile_picture_db_path = 'uploads/' . uniqid() . '.' . $file_extension;
    $profile_picture_full_path = '../' . $profile_picture_db_path;

    if (!move_uploaded_file($profile_picture['tmp_name'], $profile_picture_full_path)) {
        return ["status" => "upload_failed", "type" => "error", "redirect" => "add-doctor.php"];
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    if ($sessionData["user_type"] === "doctor") {
        // Complete existing profile for a logged-in doctor user
        $user_id = $sessionData['user_id'];
        $stmt_profile = $conn->prepare("INSERT INTO doctorProfile (user_id, full_name, specialization, phone_number, profile_picture, description, website_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_profile->bind_param("issssss", $user_id, $full_name, $specialization, $phone_number, $profile_picture_db_path, $description, $website_url);
        
        if ($stmt_profile->execute()) {
            return ["status" => "Account Created", "type" => "success", "redirect" => "patients.php"];
        } else {
            return ["status" => "Profile creation failed", "type" => "error", "redirect" => "add-doctor.php"];
        }
    } else {	 
        // Admin creating a new doctor account
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, type) VALUES (?, ?, ?, 'doctor')");
            $stmt->bind_param("sss", $username, $email, $hashed_password);
            $stmt->execute();
            $user_id = $conn->insert_id;

            $stmt_profile = $conn->prepare("INSERT INTO doctorProfile (user_id, full_name, specialization, phone_number, profile_picture, description, website_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt_profile->bind_param("issssss", $user_id, $full_name, $specialization, $phone_number, $profile_picture_db_path, $description, $website_url);
            $stmt_profile->execute();

            $conn->commit();
            return ["status" => "Doctor added successfully", "type" => "success", "redirect" => "doctors.php"];
        } catch (Exception $e) {
            $conn->rollback();
            return ["status" => "Error: " . $e->getMessage(), "type" => "error", "redirect" => "add-doctor.php"];
        }
    }
}
?>
