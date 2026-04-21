<?php
include("config/DB_Config.php");

try {
    $stmt = $conn->query("SELECT * FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($users as $u) {
        echo "userID: {$u['userID']}, username: {$u['username']}, role: {$u['role']}\n";
    }

    $stmt2 = $conn->query("SELECT * FROM \"doctorProfile\"");
    $docs = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    echo "Doctors Profiles:\n";
    foreach ($docs as $d) {
        echo "doctorID: {$d['doctorID']}, userID: {$d['userID']}, full_name: {$d['full_name']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
