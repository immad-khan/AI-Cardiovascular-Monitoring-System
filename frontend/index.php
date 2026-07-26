<?php
session_start();
include("../config/DB_Config.php");

// If already logged in, redirect to respective dashboard
if (isset($_SESSION["user_type"])) {
    if ($_SESSION["user_type"] === "admin") {
        header("Location: dashboard.php");
        exit();
    } elseif ($_SESSION["user_type"] === "doctor") {
        header("Location: Doctor-Dashboard.php");
        exit();
    } elseif ($_SESSION["user_type"] === "patient") {
        header("Location: Patient-Dashboard.php");
        exit();
    }
}

// Handle Login Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = htmlspecialchars(trim($_POST['username']));
    $pass = htmlspecialchars(trim($_POST['password']));

    if (empty($user) || empty($pass)) {
        header("Location: landing/index.html?status=All fields are required&type=error#signin");
        exit();
    }

    try {
        $stmt = $conn->prepare('SELECT "userID", username, email, password, role, "isActive" FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$user, $user]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            if (password_verify($pass, $row['password'])) {
                if ($row['isActive'] == false) {
                    header("Location: landing/index.html?status=Account is not active&type=error#signin");
                    exit();
                }

                $_SESSION['user_id'] = $row['userID'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['user_type'] = $row['role'];
                $_SESSION['email'] = $row['email'];

                if ($row['role'] === 'admin') {
                    header("Location: dashboard.php");
                } elseif ($row['role'] === 'doctor') {
                    header("Location: Doctor-Dashboard.php");
                } elseif ($row['role'] === 'patient') {
                    header("Location: Patient-Dashboard.php");
                } else {
                    header("Location: landing/index.html#signin");
                }
                exit();
            }
            header("Location: landing/index.html?status=Invalid Password&type=error#signin");
            exit();
        }
        header("Location: landing/index.html?status=User Not Found&type=error#signin");
        exit();
    } catch (PDOException $e) {
        header("Location: landing/index.html?status=Database Error&type=error#signin");
        exit();
    }
}

// If GET request with status (like from logout), pass it along
$query = "";
if (isset($_GET['status'])) {
    $status = urlencode($_GET['status']);
    $type = isset($_GET['type']) ? urlencode($_GET['type']) : 'success';
    $query = "?status=$status&type=$type";
}

header("Location: landing/index.html" . $query . "#signin");
exit();
?>
