<?php
include("config/DB_Config.php");

try {
    // Create departments table
    $createTable = 'CREATE TABLE IF NOT EXISTS departments (
        id SERIAL PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        "headOfDepartment" VARCHAR(255),
        "totalDoctors" INT DEFAULT 0,
        "isActive" BOOLEAN DEFAULT TRUE,
        "createdAt" TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );';
    $conn->exec($createTable);
    echo "Departments table created successfully.<br>";

    // Insert sample data
    $count = $conn->query("SELECT count(*) FROM departments")->fetchColumn();
    if ($count == 0 || $count < 4) {
        $insertData = 'INSERT INTO departments (name, description, "headOfDepartment", "totalDoctors") VALUES 
            (\'Cardiology\', \'Heart and cardiovascular health monitoring\', \'Dr. Ehsan Ullah\', 4),
            (\'Neurology\', \'Brain and nervous system diagnostics\', \'Dr. Sarah Ahmed\', 3),
            (\'Pathology\', \'Clinical diagnostic laboratory services\', \'Dr. Kashif Malik\', 2),
            (\'General Medicine\', \'Primary care and general health checkups\', \'Dr. Fatima Khan\', 6)
            ON CONFLICT (name) DO NOTHING';
        $conn->exec($insertData);
        echo "Sample data inserted.<br>";
    } else {
        echo "Departments already seeded.<br>";
    }

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
