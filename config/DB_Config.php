<?php
// Supabase (Postgres) Configuration
$host = "aws-1-ap-southeast-2.pooler.supabase.com";
$port = "6543";
$dbname = "postgres";
$user = "postgres.jopkxezkpyfjixxtrfnw";
$password = "S!ddeeq5696";

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $conn = new PDO($dsn, $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
