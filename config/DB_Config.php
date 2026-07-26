<?php
// Supabase (Postgres) Configuration
// Workaround: Using IP direct because of DNS resolution issues (Unknown host)
$host = "aws-1-ap-southeast-2.pooler.supabase.com"; // was "13.239.87.90"
$port = "6543";
$dbname = "postgres";
$user = "postgres.jopkxezkpyfjixxtrfnw";
$password = "S!ddeeq5696";

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $conn = new PDO($dsn, $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
