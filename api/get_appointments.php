<?php
header('Content-Type: application/json');
include("../config/DB_Config.php");

try {
    $stmt = $conn->query("SELECT a.id, p.name as patient_name, COALESCE(dp.full_name, u.username) as doctor_name, 
                                 a.appointment_date, a.appointment_time, a.status, a.notes
                          FROM appointments a 
                          JOIN patients p ON a.patient_id = p.\"patientID\"
                          JOIN users u ON a.doctor_id = u.\"userID\"
                          LEFT JOIN \"doctorProfile\" dp ON u.\"userID\" = dp.\"userID\"
                          WHERE a.status != 'Cancelled'");
    
    $events = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $events[] = [
            'id' => $row['id'],
            'title' => $row['patient_name'] . " with " . $row['doctor_name'],
            'start' => $row['appointment_date'] . 'T' . $row['appointment_time'],
            'className' => $row['status'] == 'Completed' ? 'bg-success' : 'bg-info',
            'description' => $row['notes']
        ];
    }
    
    echo json_encode($events);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>