<?php 
// Start the session at the very beginning
session_start();
include("../config/DB_Config.php");

// Check if the user is logged in and if the user type is admin or doctor
if (!isset($_SESSION["user_type"]) || ($_SESSION["user_type"] !== "admin" && $_SESSION["user_type"] !== "doctor")) {
    header("Location: index.php?status=Access is denied&type=error");
    exit();
}

// If user is a doctor, check if profile exists
if ($_SESSION["user_type"] == "doctor") {
    $stmt = $conn->prepare('SELECT "doctorID" FROM "doctorProfile" WHERE "userID" = ?');
    $stmt->execute([$_SESSION["user_id"]]);
    if ($stmt->fetch()) {
        header("Location: patients.php?status=Doctor login successful");
        exit();
    }
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include_once("../backend/doctor_logic.php");
    $result = handleDoctorRegistration($conn, $_POST, $_FILES, $_SESSION);
    
    $status = urlencode($result["status"]);
    $type = urlencode($result["type"]);
    $redirect = $result["redirect"];
    
    header("Location: $redirect?status=$status&type=$type");
    exit();
}
?>

<!doctype html>
<html class="no-js " lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
<meta name="description" content="Responsive Bootstrap 4 and web Application ui kit.">

<title>Add Doctor - CUST Digihealth</title>
<link rel="icon" href="favicon.ico" type="image/x-icon">
<link rel="stylesheet" href="../assets/plugins/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../assets/plugins/dropzone/dropzone.css">
<link rel="stylesheet" href="../assets/plugins/bootstrap-select/css/bootstrap-select.css"/>
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/color_skins.css">
</head>
<body class="theme-cyan">
<div class="page-loader-wrapper">
    <div class="loader">
        <div class="m-t-30"><img class="zmdi-hc-spin" src="../assets/images/logo.svg" width="48" height="48" alt="Oreo"></div>
        <p>Please wait...</p>
    </div>
</div>
<!-- Rest of the HTML follows... -->