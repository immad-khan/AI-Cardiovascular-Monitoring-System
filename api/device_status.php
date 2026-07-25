<?php
/**
 * API: /api/device_status.php
 * Returns real-time stats for the monitoring panel:
 *   - Device last seen, total readings, latest vitals, last prediction
 * Called by the Device Status page via polling.
 */
header("Content-Type: application/json");
include_once("../config/DB_Config.php");
session_start();

if (!isset($_SESSION['user_type'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

try {
    // Fetch all devices with latest reading and linked patient
    $sql = "
        SELECT
            md.\"deviceID\",
            md.mac_address,
            md.model,
            md.status,
            p.\"patientID\",
            p.name as patient_name,
            vr.\"heartRate\",
            vr.\"SpO2\",
            vr.final_prediction,
            vr.\"confidenceScore\",
            vr.timestamp as last_seen
        FROM monitoring_devices md
        LEFT JOIN patients p ON md.\"patientID\" = p.\"patientID\"
        LEFT JOIN LATERAL (
            SELECT \"heartRate\", \"SpO2\", final_prediction, \"confidenceScore\", timestamp
            FROM vital_sign_readings
            WHERE \"deviceID\" = md.\"deviceID\"
            ORDER BY timestamp DESC
            LIMIT 1
        ) vr ON true
        ORDER BY vr.timestamp DESC NULLS LAST
    ";

    $stmt = $conn->query($sql);
    $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count recent readings in last 60 seconds (to show live status)
    foreach ($devices as &$d) {
        $recent = $conn->prepare("SELECT COUNT(*) FROM vital_sign_readings WHERE \"deviceID\" = ? AND timestamp > NOW() - INTERVAL '60 seconds'");
        $recent->execute([$d['deviceID']]);
        $d['readings_last_60s'] = (int)$recent->fetchColumn();
        $d['is_live'] = $d['readings_last_60s'] > 0;
    }

    // Global stats
    $total_readings = $conn->query("SELECT COUNT(*) FROM vital_sign_readings")->fetchColumn();
    $total_alerts   = $conn->query("SELECT COUNT(*) FROM \"CRITICAL_ALERT\" WHERE status = 'Active'")->fetchColumn();
    $last_reading   = $conn->query("SELECT MAX(timestamp) FROM vital_sign_readings")->fetchColumn();

    echo json_encode([
        "success"       => true,
        "devices"       => $devices,
        "global"        => [
            "total_readings" => (int)$total_readings,
            "active_alerts"  => (int)$total_alerts,
            "last_reading"   => $last_reading
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
