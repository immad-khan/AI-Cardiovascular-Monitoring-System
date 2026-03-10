<?php
// backend/alert_logic.php
include_once("../config/DB_Config.php");

/**
 * Fetches recent critical alerts for the logged-in doctor/admin
 */
function getActiveAlerts($conn) {
    $sql = 'SELECT ca."alertID", md.mac_address, ca.message, ca.timestamp,
                   p."patientID" AS patient_id, p.phone_no
            FROM "CRITICAL_ALERT" ca
            LEFT JOIN monitoring_devices md ON ca."deviceID" = md."deviceID"
            LEFT JOIN device_patient_link dpl ON md.mac_address = dpl.mac_address
                AND dpl.delinked_at IS NULL
            LEFT JOIN patients p ON dpl.patient_id = p."patientID"
            WHERE ca.status = \'Active\'
            ORDER BY ca.timestamp DESC LIMIT 5';

    try {
        $result = $conn->query($sql);
        return $result ? $result->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Marks an alert as acknowledged
 */
function acknowledgeAlert($conn, $alertID) {
    try {
        $stmt = $conn->prepare('UPDATE "CRITICAL_ALERT" SET status = \'Acknowledged\' WHERE "alertID" = ?');
        return $stmt->execute([$alertID]);
    } catch (PDOException $e) {
        return false;
    }
}
?>
