<?php
// backend/patient_logic.php
include_once("../config/DB_Config.php");

/**
 * Fetches all MAC addresses that are currently linked to patients
 */
function getLinkedMacAddresses($conn) {
    try {
        $sql = "SELECT DISTINCT mac_address FROM device_patient_link WHERE delinked_at IS NULL";
        $stmt = $conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Handles Patient Registration and Profile Updates
 */
function handlePatientAction($conn, $postData) {
    // Sanitize and collect data
    $patient_id = htmlspecialchars(trim($postData['patientId'] ?? ''));
    $name = htmlspecialchars(trim($postData['name'] ?? ''));
    $phone_no = htmlspecialchars(trim($postData['phoneNo'] ?? ''));
    $email = htmlspecialchars(trim($postData['email'] ?? ''));
    $age = (int)($postData['age'] ?? 0);
    $gender = htmlspecialchars(trim($postData['gender'] ?? ''));
    $medical_history = htmlspecialchars(trim($postData['medicalHistory'] ?? ''));
    
    // Note: In Postgres, we should ideally use assignedDoctorID (int) instead of a comma-separated list
    // For now, we take the FIRST doctor if multiple selected, but if we want to support many, 
    // we should use a junction table. Following current ERD:
    $assignedDoctorID = isset($postData['AssociatedDoctors']) ? (int)$postData['AssociatedDoctors'][0] : null;
    
    $staff_name = htmlspecialchars(trim($postData['StaffName'] ?? ''));
    $ward_no = htmlspecialchars(trim($postData['WardNo'] ?? ''));
    $date = htmlspecialchars(trim($postData['Date'] ?? date('Y-m-d H:i:s')));
    $mac_address = htmlspecialchars(trim($postData['mac_address'] ?? ''));

    if (empty($patient_id) || empty($phone_no) || empty($email) || empty($age) || empty($gender)) {
        return ["status" => "All required fields must be filled", "type" => "error"];
    }

    try {
        $conn->beginTransaction();

        if (isset($postData['editPatient'])) {
            // UPDATE EXISTING PATIENT
            $target_id = $postData['editPatient'];
            $sql = "UPDATE patients SET 
                    name = ?, phone_no = ?, email = ?, age = ?, 
                    gender = ?, medical_history = ?, \"assignedDoctorID\" = ?, 
                    staff_name = ?, ward_no = ?, date = ? 
                    WHERE \"patientID\" = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$name, $phone_no, $email, $age, $gender, $medical_history, $assignedDoctorID, $staff_name, $ward_no, $date, $target_id]);
            $msg = "Patient record updated successfully!";
        } else {
            // INSERT NEW PATIENT
            $sql = "INSERT INTO patients (\"patientID\", name, phone_no, email, age, gender, medical_history, \"assignedDoctorID\", staff_name, ward_no, date) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$patient_id, $name, $phone_no, $email, $age, $gender, $medical_history, $assignedDoctorID, $staff_name, $ward_no, $date]);
            $msg = "Patient record created successfully!";
        }

        // Handle Device Linking
        if (!empty($mac_address)) {
            // Check if this specific link already exists and is active
            $check_sql = "SELECT id FROM device_patient_link WHERE patient_id = ? AND mac_address = ? AND delinked_at IS NULL";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->execute([$patient_id, $mac_address]);
            
            if (!$check_stmt->fetch()) {
                // De-link any existing active devices for this patient first
                $delink_sql = "UPDATE device_patient_link SET delinked_at = CURRENT_TIMESTAMP WHERE patient_id = ? AND delinked_at IS NULL";
                $conn->prepare($delink_sql)->execute([$patient_id]);

                // Create new link
                $link_sql = "INSERT INTO device_patient_link (patient_id, mac_address) VALUES (?, ?)";
                $conn->prepare($link_sql)->execute([$patient_id, $mac_address]);
                $msg .= " Device linked successfully.";
                
                // Also update monitoring_devices table status
                $device_sql = "UPDATE monitoring_devices SET status = 'Assigned', \"patientID\" = ? WHERE mac_address = ?";
                $conn->prepare($device_sql)->execute([$patient_id, $mac_address]);
            }
        }

        $conn->commit();
        return ["status" => $msg, "type" => "success", "redirect" => "patients.php"];
    } catch (PDOException $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        return ["status" => "Database Error: " . $e->getMessage(), "type" => "error"];
    } catch (Exception $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        return ["status" => "General Error: " . $e->getMessage(), "type" => "error"];
    }
}
?>
