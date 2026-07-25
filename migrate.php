<?php
// migrate.php
include_once(__DIR__ . "/config/DB_Config.php");

try {
    $sql = "
    CREATE TABLE IF NOT EXISTS doctor_tasks (
        \"taskID\" SERIAL PRIMARY KEY,
        \"doctorID\" INT NOT NULL,
        \"patientID\" VARCHAR(255) NOT NULL,
        \"readingID\" INT,
        \"alertID\" INT,
        \"task_type\" VARCHAR(100) DEFAULT 'Review ECG',
        \"status\" VARCHAR(50) DEFAULT 'Pending',
        \"notes\" TEXT,
        \"created_at\" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        \"updated_at\" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (\"doctorID\") REFERENCES users(\"userID\") ON DELETE CASCADE,
        FOREIGN KEY (\"patientID\") REFERENCES patients(\"patientID\") ON DELETE CASCADE
    );
    ";
    
    $conn->exec($sql);
    echo "Migration successful. Table doctor_tasks created.\n";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
