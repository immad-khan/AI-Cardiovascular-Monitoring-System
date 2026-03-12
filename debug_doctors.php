<?php
include("config/DB_Config.php");
try {
    echo "--- Users with role 'doctor' ---\n";
    $stmt = $conn->query("SELECT \"userID\", username, role, \"isActive\" FROM users WHERE role = 'doctor'");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($users as $u) {
        echo "ID: {$u['userID']} | Username: {$u['username']} | Active: " . ($u['isActive'] ? 'Yes' : 'No') . "\n";
    }

    echo "\n--- Records in doctorProfile ---\n";
    $stmt2 = $conn->query("SELECT \"userID\", full_name FROM \"doctorProfile\"");
    $profiles = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    foreach($profiles as $p) {
        echo "UserID: {$p['userID']} | Name: {$p['full_name']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
