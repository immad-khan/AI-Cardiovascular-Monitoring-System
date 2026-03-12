<?php
include("config/DB_Config.php");

$admin_user = "admin";
$admin_email = "admin@digihealth.com"; 
$admin_pass = "admin123";
$hashed_pass = password_hash($admin_pass, PASSWORD_DEFAULT);

try {
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, \"isActive\") VALUES (?, ?, ?, 'admin', true)");
    $stmt->execute([$admin_user, $admin_email, $hashed_pass]);
    echo "Admin account created successfully!\n";
    echo "Username: $admin_user\n";
    echo "Password: $admin_pass\n";
} catch (PDOException $e) {
    echo "Error creating admin: " . $e->getMessage() . "\n";
}
?>
