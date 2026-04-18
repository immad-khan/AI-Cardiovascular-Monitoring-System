<?php
// backend/auth_logic.php
include_once(__DIR__ . "/../config/DB_Config.php");

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

    $stmt = $conn->prepare('SELECT "userID", username, email, password, role, "isActive" FROM users WHERE username = ?');
    $stmt->execute([$user]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        if (password_verify($pass, $row['password'])) {
            if ($row['isActive'] == false) {
                return ["status" => "Account is not active", "type" => "error", "redirect" => "index.php"];
            }

            $_SESSION['user_id'] = $row['userID'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['user_type'] = $row['role'];

            if ($row['role'] != "admin") {
                $_SESSION['email'] = $row['email'];
            }

            // Role-based redirects
            switch ($row['role']) {
                case 'admin':
                    return ["redirect" => "dashboard.php"];
                case 'doctor':
                    $stmt_doctor = $conn->prepare('SELECT "doctorID" FROM "doctorProfile" WHERE "userID" = ?');
                    $stmt_doctor->execute([$row['userID']]);
                    $res_doctor = $stmt_doctor->fetch(PDO::FETCH_ASSOC);
                    return $res_doctor
                        ? ["status" => "Doctor login successful", "redirect" => "patients.php"]
                        : ["redirect" => "add-doctor.php"];
                case 'patient':
                    $stmt_patient = $conn->prepare('SELECT "patientID" FROM patients WHERE email = ?');
                    $stmt_patient->execute([$row['email']]);
                    $res_patient = $stmt_patient->fetch(PDO::FETCH_ASSOC);
                    if ($res_patient) {
                        return ["redirect" => "Patient-Profile.php?patientId=" . $res_patient['patientID']];
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

    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");

    if ($stmt->execute([$username, $email, $hashed_password, $type])) {
        return ["status" => "Account Created Successfully", "type" => "success", "redirect" => "index.php"];
    } else {
        $err = $stmt->errorInfo();
        return ["status" => "Registration Failed: " . $err[2], "type" => "error", "redirect" => "sign-up.php"];
    }
}
?>
