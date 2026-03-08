<?php
// backend/patient_logic.php
include_once("../config/DB_Config.php");

/**
 * Fetches all MAC addresses that are currently linked to patients
 */
function getLinkedMacAddresses($conn) {
    $sql = "SELECT DISTINCT mac_address FROM device_patient_link WHERE delinked_at IS NULL";
    $result = $conn->query($sql);
    $mac_addresses = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $mac_addresses[] = $row['mac_address'];
        }
    }
    return $mac_addresses;
}

/**
 * Handles Patient Registration and Profile Updates
 */
function handlePatientAction($conn, $postData) {
    // Sanitize and collect data
    $patient_id = htmlspecialchars(trim($postData['patientId']));
    $phone_no = htmlspecialchars(trim($postData['phoneNo']));
    $email = htmlspecialchars(trim($postData['email']));
    $age = (int)$postData['age'];
    $gender = htmlspecialchars(trim($postData['gender']));
    $medical_history = htmlspecialchars(trim($postData['medicalHistory']));
    $associated_doctors = isset($postData['AssociatedDoctors']) ? implode(',', $postData['AssociatedDoctors']) : '';
    $staff_name = htmlspecialchars(trim($postData['StaffName']));
    $ward_no = htmlspecialchars(trim($postData['WardNo']));
    $date = htmlspecialchars(trim($postData['Date']));
    $mac_address = htmlspecialchars(trim($postData['mac_address']));

    if (empty($patient_id) || empty($phone_no) || empty($email) || empty($age) || empty($gender) || empty($date)) {
        return ["status" => "All required fields must be filled", "type" => "error"];
    }

    $conn->begin_transaction();
    try {
        if (isset($postData['editPatient'])) {
            // UPDATE EXISTING PATIENT
            $target_id = $postData['editPatient'];
            $sql = "UPDATE patients SET phone_no=?, email=?, age=?, gender=?, medical_history=?, associated_doctors=?, staff_name=?, ward_no=?, date=? WHERE patient_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssisssssss", $phone_no, $email, $age, $gender, $medical_history, $associated_doctors, $staff_name, $ward_no, $date, $target_id);
            $stmt->execute();
            $msg = "Patient record updated successfully!";
        } else {
            // INSERT NEW PATIENT
            $sql = "INSERT INTO patients (patient_id, phone_no, email, age, gender, medical_history, associated_doctors, staff_name, ward_no, date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssissssss", $patient_id, $phone_no, $email, $age, $gender, $medical_history, $associated_doctors, $staff_name, $ward_no, $date);
            $stmt->execute();
            $msg = "Patient record created successfully!";
        }

        // Handle Device Linking
        if (!empty($mac_address)) {
            // Check if this specific link already exists and is active
            $check_sql = "SELECT id FROM device_patient_link WHERE patient_id = ? AND mac_address = ? AND delinked_at IS NULL";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("ss", $patient_id, $mac_address);
            $check_stmt->execute();
            
            if ($check_stmt->get_result()->num_rows == 0) {
                // De-link any existing active devices for this patient first
                $delink_sql = "UPDATE device_patient_link SET delinked_at = NOW() WHERE patient_id = ? AND delinked_at IS NULL";
                $delink_stmt = $conn->prepare($delink_sql);
                $delink_stmt->bind_param("s", $patient_id);
                $delink_stmt->execute();

                // Create new link
                $link_sql = "INSERT INTO device_patient_link (patient_id, mac_address) VALUES (?, ?)";
                $link_stmt = $conn->prepare($link_sql);
                $link_stmt->bind_param("ss", $patient_id, $mac_address);
                $link_stmt->execute();
                $msg .= " Device linked successfully.";
            }
        } else {
            // If MAC address is empty, ensure patient is de-linked from any existing devices
            $delink_sql = "UPDATE device_patient_link SET delinked_at = NOW() WHERE patient_id = ? AND delinked_at IS NULL";
            $delink_stmt = $conn->prepare($delink_sql);
            $delink_stmt->bind_param("s", $patient_id);
            $delink_stmt->execute();
        }

        $conn->commit();
        return ["status" => $msg, "type" => "success", "redirect" => "patients.php"];
    } catch (Exception $e) {
        $conn->rollback();
        return ["status" => "Error: " . $e->getMessage(), "type" => "error"];
    }
}
?>
