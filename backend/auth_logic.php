<?php
// backend/auth_logic.php
include_once("../config/DB_Config.php");

/**
 * Handles User Login
 */
function handleLogin($conn, $postData) {
    session_start();
    $user = htmlspecialchars(trim($postData['username']));
    $pass = htmlspecialchars(trim($postData['password']));

    if (empty($user) || empty($pass)) {
        return ["status" => "All fields are required", "type" => "error", "redirect" => "index.php"];
    }

    $stmt = $conn->prepare("SELECT id, username, email, password, type, isActive FROM users WHERE username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($pass, $row['password'])) {
            if ($row['isActive'] == 0) {
                return ["status" => "Account is not active", "type" => "error", "redirect" => "index.php"];
            }

            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['user_type'] = $row['type'];

            if($row['type'] != "admin"){
                $_SESSION['email'] = $row['email'];
                $_SESSION['password'] = $row['password'];
            }

            // Role-based redirects
            switch ($row['type']) {
                case 'admin':
                    return ["redirect" => "dashboard.php"];
                case 'doctor':
                    $stmt_doctor = $conn->prepare("SELECT id FROM doctorProfile WHERE user_id = ?");
                    $stmt_doctor->bind_param("i", $row['id']);
                    $stmt_doctor->execute();
                    $res_doctor = $stmt_doctor->get_result();
                    return ($res_doctor->num_rows > 0) 
                        ? ["status" => "Doctor login successful", "redirect" => "patients.php"]
                        : ["redirect" => "add-doctor.php"];
                case 'patient':
                    $stmt_patient = $conn->prepare("SELECT patient_id FROM patients WHERE email = ?");
                    $stmt_patient->bind_param("s", $row['email']);
                    $stmt_patient->execute();
                    $res_patient = $stmt_patient->get_result();
                    if ($res_patient->num_rows > 0) {
                        $p = $res_patient->fetch_assoc();
                        return ["redirect" => "Patient-Profile.php?patientId=" . $p['patient_id']];
                    }
                    return ["redirect" => "index.php"];
                case 'tech-admin':
                    return ["redirect" => "devices.php"];
                default:
                    return ["status" => "Unknown User Type", "type" => "error", "redirect" => "index.php"];
            }
        }
        return ["status" => "Invalid Password", "type" => "error", "redirect" => "index.php"];
    }
    return ["status" => "User Not Found", "type" => "error", "redirect" => "index.php"];
}

/**
 * Handles User Signup
 */
function handleSignup($conn, $postData) {
    $username = htmlspecialchars(trim($postData['username']));
    $email = htmlspecialchars(trim($postData['email']));
    $password = htmlspecialchars(trim($postData['password']));
    $type = htmlspecialchars(trim($postData['type']));

    if (empty($username) || empty($email) || empty($password) || empty($type)) {
        return ["status" => "All fields are required", "type" => "error", "redirect" => "sign-up.php"];
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, type) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $email, $hashed_password, $type);

    if ($stmt->execute()) {
        return ["status" => "Account Created Successfully", "type" => "success", "redirect" => "index.php"];
    } else {
        return ["status" => "Registration Failed", "type" => "error", "redirect" => "sign-up.php"];
    }
}
?>
