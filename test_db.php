<?php
$host = "aws-1-ap-southeast-2.pooler.supabase.com";
$port = "6543";
$dbname = "postgres";
$user = "postgres.jopkxezkpyfjixxtrfnw";
$password = "S!ddeeq5696";

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $conn = new PDO($dsn, $user, $password);
    echo "Connected successfully\n";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
?>
