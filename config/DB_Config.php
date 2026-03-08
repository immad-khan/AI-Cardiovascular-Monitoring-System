<?php
$servername = "localhost";
$username = "gatewayl_gatewayl";
$password = "a%;n&9(4B]LD";
$dbname = "gatewayl_ecg_db";


// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

 ?>