<?php 
include("./config/DB_Config.php");
session_start();

if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'tech-admin')) {
    header("Location: index.php?status=denied");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mac = $_POST['mac_address'];
    $model = $_POST['model'];
    try {
        $stmt = $conn->prepare("INSERT INTO monitoring_devices (mac_address, model, status) VALUES (?, ?, 'Offline')");
        $stmt->execute([$mac, $model]);
        header("Location: devices.php?status=DeviceAdded");
        exit();
    } catch(PDOException $e) {
        $error = $e->getMessage();
    }
}
?>
<!doctype html>
<html class="no-js " lang="en">
<head>
<meta charset="utf-8">
<title>Add Device</title>
<link rel="stylesheet" href="./assets/plugins/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="./assets/css/main.css">
</head>
<body class="theme-cyan">
<div class="container m-t-50">
    <div class="card"><div class="header"><h2>Add ECG Device</h2></div><div class="body">
        <?php if(isset($error)) echo "<p class='text-danger'>$error</p>"; ?>
        <form method="POST">
            <input type="text" name="mac_address" class="form-control m-b-10" placeholder="MAC (e.g. AA:BB:CC...)" required>
            <select name="model" class="form-control m-b-10">
                <option value="RPI-Kit">Raspberry Pi 4 (IoT Gateway)</option>
                <option value="ESP32-ECG">ESP32 Wearable</option>
            </select>
            <button type="submit" class="btn btn-primary">Add Device</button>
        </form>
    </div></div>
</div>
</body>
</html>
