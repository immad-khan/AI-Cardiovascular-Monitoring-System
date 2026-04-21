<?php
include("config/DB_Config.php");

try {
    $docID = 16; // doctor_test's userID
    $stmt = $conn->prepare("UPDATE patients SET \"assignedDoctorID\" = ? WHERE \"assignedDoctorID\" IS NULL");
    $stmt->execute([$docID]);
    echo "Successfully assigned existing patients to Doctor ID: $docID\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
