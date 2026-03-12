<?php
include("config/DB_Config.php");

try {
    // Create departments table
    $sql = "
    CREATE TABLE IF NOT EXISTS departments (
        id SERIAL PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        "headOfDepartment" VARCHAR(255),
        "totalDoctors" INT DEFAULT 0,
        "isActive" BOOLEAN DEFAULT TRUE,
        "createdAt" TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    // Insert sample data if empty
    $check = $conn->query(\"SELECT COUNT(*) FROM departments\");
    if ($check->fetchColumn() == 0) {
        $conn->exec(\"
            INSERT INTO departments (name, description, \"headOfDepartment\", \"totalDoctors\") VALUES 
            ('Cardiology', 'Heart and cardiovascular health monitoring', 'Dr. Ehsan Ullah', 4),
            ('Neurology', 'Brain and nervous system diagnostics', 'Dr. Sarah Ahmed', 3),
            ('Pathology', 'Clinical diagnostic laboratory services', 'Dr. Kashif Malik', 2),
            ('General Medicine', 'Primary care and general health checkups', 'Dr. Fatima Khan', 6)
        \");
        echo \"Departments table created and seeded successfully!<br>\";
    } else {
        echo \"Departments table already exists and contains data.<br>\";
    }

} catch (PDOException $e) {
    die(\"Error creating table: \" . $e->getMessage());
}
?>
