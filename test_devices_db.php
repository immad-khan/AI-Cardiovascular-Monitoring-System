<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include("config/DB_Config.php");
try {
    $stmt = $conn->query("SELECT * FROM ecg_devices");
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "SUCCESS: Found " . count($res) . " devices.";
} catch (Exception $e) {
    echo "DATABASE ERROR: " . $e->getMessage();
}
?>