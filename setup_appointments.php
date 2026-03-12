<?php
include 'config/DB_Config.php';
try {
    $sql = "CREATE TABLE IF NOT EXISTS appointments (
        id SERIAL PRIMARY KEY,
        patient_id VARCHAR(255) NOT NULL,
        doctor_id INT NOT NULL,
        appointment_date DATE NOT NULL,
        appointment_time TIME NOT NULL,
        notes TEXT,
        status VARCHAR(50) DEFAULT 'Scheduled',
        created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (patient_id) REFERENCES patients(\"patientID\") ON DELETE CASCADE,
        FOREIGN KEY (doctor_id) REFERENCES users(\"userID\") ON DELETE CASCADE
    )";
    $conn->exec($sql);
    echo "Table 'appointments' created or already exists.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>