<?php
include("config/DB_Config.php");
try {
    $stmt = $conn->query("SELECT username, email, role, \"isActive\" FROM users WHERE role = 'admin'");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Found " . count($admins) . " admin users:\n";
    foreach ($admins as $admin) {
        echo "- Username: " . $admin['username'] . " | Email: " . $admin['email'] . " | Active: " . ($admin['isActive'] ? 'Yes' : 'No') . "\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
