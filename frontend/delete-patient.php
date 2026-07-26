<?php
include("../config/DB_Config.php");
session_start();

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: index.php?status=Access Denied&type=error");
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: patients.php?status=Invalid Request&type=error");
    exit();
}

$patientId = $_GET['id'];

try {
    $conn->beginTransaction();

    // Get the user email linked to this patient before deleting
    $userEmail = null;
    $stmt = $conn->prepare("SELECT email FROM patients WHERE \"patientID\" = ?");
    $stmt->execute([$patientId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) $userEmail = $row['email'];

    // Soft delete: mark as inactive
    $stmt = $conn->prepare("UPDATE patients SET \"isActive\" = FALSE WHERE \"patientID\" = ?");
    $stmt->execute([$patientId]);

    // Also deactivate the linked user account if it exists
    if ($userEmail) {
        $stmt2 = $conn->prepare("UPDATE users SET \"isActive\" = FALSE WHERE email = ? AND role = 'patient'");
        $stmt2->execute([$userEmail]);
    }

    $conn->commit();
    header("Location: patients.php?status=Patient " . urlencode($patientId) . " deleted successfully&type=success");
} catch (PDOException $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    header("Location: patients.php?status=Error: " . urlencode($e->getMessage()) . "&type=error");
}
exit();
?>
