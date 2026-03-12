<?php
// frontend/delete-doctor.php
include("../config/DB_Config.php");
session_start();

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: index.php?status=Access Denied&type=error");
    exit();
}

if (isset($_GET['id'])) {
    $userID = $_GET['id'];
    
    try {
        $conn->beginTransaction();
        
        // Delete from doctorProfile first due to foreign key (if applicable)
        $stmt1 = $conn->prepare("DELETE FROM \"doctorProfile\" WHERE \"userID\" = ?");
        $stmt1->execute([$userID]);
        
        // Delete from users
        $stmt2 = $conn->prepare("DELETE FROM users WHERE \"userID\" = ? AND role = 'doctor'");
        $stmt2->execute([$userID]);
        
        $conn->commit();
        header("Location: doctors.php?status=Doctor deleted successfully&type=success");
    } catch (PDOException $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        header("Location: doctors.php?status=Error: " . $e->getMessage() . "&type=error");
    }
} else {
    header("Location: doctors.php?status=Invalid Request&type=error");
}
exit();
?>