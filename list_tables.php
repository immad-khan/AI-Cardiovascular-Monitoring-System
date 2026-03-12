<?php
include("config/DB_Config.php");
$stmt = $conn->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'monitoring_devices'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>