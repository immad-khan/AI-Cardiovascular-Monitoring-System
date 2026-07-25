<?php
session_start();
include("../config/DB_Config.php");

// Handle User Redirection Based on Role
if (isset($_SESSION["user_type"])) {
    if ($_SESSION["user_type"] === "admin") {
        header("Location: dashboard.php");
    } elseif ($_SESSION["user_type"] === "doctor") {
        header("Location: Doctor-Dashboard.php");
    } elseif ($_SESSION["user_type"] === "patient") {
        header("Location: Patient-Dashboard.php");
    } else {
        header("Location: sign-up.php?status=Invalid Role&type=error");
    }
} else {
    header("Location: index.php");
}
exit();
?>