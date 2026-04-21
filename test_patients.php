<?php
include("config/DB_Config.php");

try {
    $stmt = $conn->query("SELECT * FROM patients");
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Total Patients: " . count($patients) . "\n\n";
    foreach ($patients as $p) {
        echo "id: {$p['id']}, patientID: {$p['patientID']}, name: {$p['name']}, assignedDoctorID: {$p['assignedDoctorID']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
