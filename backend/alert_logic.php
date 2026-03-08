<?php
// backend/alert_logic.php
include_once("../config/DB_Config.php");

/**
 * Fetches recent critical alerts for the logged-in doctor/admin
 */
function getActiveAlerts($conn) {
    // In a real scenario, we might want to filter alerts based on patients assigned to this doctor
    // For now, we fetch all active alerts as per the admin/doctor requirement
    $sql = "SELECT ca.alertID, ca.mac_address, ca.message, ca.timestamp, p.patient_id, p.phone_no
            FROM CRITICAL_ALERT ca
            LEFT JOIN device_patient_link dpl ON ca.mac_address = dpl.mac_address AND dpl.delinked_at IS NULL
            LEFT JOIN patients p ON dpl.patient_id = p.patient_id
            WHERE ca.status = 'Active'
            ORDER BY ca.timestamp DESC LIMIT 5";
    
    $result = $conn->query($sql);
    $alerts = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $alerts[] = $row;
        }
    }
    return $alerts;
}

/**
 * Marks an alert as acknowledged
 */
function acknowledgeAlert($conn, $alertID) {
    $stmt = $conn->prepare("UPDATE CRITICAL_ALERT SET status = 'Acknowledged' WHERE alertID = ?");
    $stmt->bind_param("i", $alertID);
    return $stmt->execute();
}
?>
