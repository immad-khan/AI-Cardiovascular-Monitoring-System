<?php
// backend/doctor_logic.php
include_once("../config/DB_Config.php");

function handleDoctorRegistration($conn, $postData, $filesData, $sessionData) {
    $username = htmlspecialchars(trim($postData['username'] ?? ''));
    $email = htmlspecialchars(trim($postData['email'] ?? ''));
    $password = $postData['password'] ?? '';
    $full_name = htmlspecialchars(trim($postData['full_name'] ?? ''));
    $specialization = htmlspecialchars(trim($postData['specialization'] ?? ''));
    $phone_number = htmlspecialchars(trim($postData['phone_number'] ?? ''));
    $description = htmlspecialchars(trim($postData['description'] ?? ''));
    $website_url = htmlspecialchars(trim($postData['website_url'] ?? ''));

    $upload_dir = '../uploads/';
    $profile_pic = 'assets/images/sm/avatar1.jpg';
    
    if (isset($filesData['profile_picture']) && $filesData['profile_picture']['error'] == 0) {
        $ext = pathinfo($filesData['profile_picture']['name'], PATHINFO_EXTENSION);
        $new_name = uniqid() . '.' . $ext;
        if (move_uploaded_file($filesData['profile_picture']['tmp_name'], $upload_dir . $new_name)) {
            $profile_pic = 'uploads/' . $new_name;
        }
    }

    try {
        $conn->beginTransaction();
        
        $sql_u = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'doctor') RETURNING \"userID\"";
        $stmt_u = $conn->prepare($sql_u);
        $stmt_u->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT)]);
        $user_id = $stmt_u->fetchColumn();

        $sql_p = "INSERT INTO \"doctorProfile\" (\"userID\", full_name, specialization, phone_number, profile_picture, description, website_url) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_p = $conn->prepare($sql_p);
        $stmt_p->execute([$user_id, $full_name, $specialization, $phone_number, $profile_pic, $description, $website_url]);

        $conn->commit();
        return ["status" => "Doctor added successfully", "type" => "success", "redirect" => "doctors.php"];
    } catch (PDOException $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        return ["status" => "Database Error: " . $e->getMessage(), "type" => "error", "redirect" => "add-doctor.php"];
    }
}
?>
