<?php
// backend/patient_logic.php
include_once(__DIR__ . "/../config/DB_Config.php");

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
    
    // Ensure assignedDoctorID is null if not selected, rather than 0 which causes FK violations
    $assignedDoctorID = (isset($postData['AssociatedDoctors']) && !empty($postData['AssociatedDoctors'][0])) 
                        ? (int)$postData['AssociatedDoctors'][0] 
                        : null;
    
    $staff_name = htmlspecialchars(trim($postData['staff_name'] ?? ''));
    $ward_no = htmlspecialchars(trim($postData['ward_no'] ?? ''));
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
            
            // Auto-create User account for patient portal access
            $temp_password = "Patient@" . rand(1000, 9999);
            $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);
            
            // Check if user already exists
            $user_check = $conn->prepare("SELECT \"userID\" FROM users WHERE email = ?");
            $user_check->execute([$email]);
            if (!$user_check->fetch()) {
                // We use patient_id as username for uniqueness
                $user_sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'patient')";
                $user_stmt = $conn->prepare($user_sql);
                $user_stmt->execute([$patient_id, $email, $hashed_password]);
                
                // Send Welcome Email
                include_once(__DIR__ . "/../backend/notification_service.php");
                $subject = "Welcome to DigiHealth - Your Patient Portal Login";
                $htmlMessage = "<h3>Welcome to DigiHealth, $name!</h3>
                                <p>Your patient portal has been successfully created.</p>
                                <p><strong>Login URL:</strong> <a href='https://your-digihealth-url.com'>DigiHealth Portal</a></p>
                                <p><strong>Username:</strong> $patient_id</p>
                                <p><strong>Password:</strong> $temp_password</p>
                                <p>Please login and change your password as soon as possible.</p>";
                sendEmail($email, $subject, $htmlMessage);
                
                $msg = "Patient created & email sent! Portal Login - Username: $patient_id, Password: $temp_password";
            } else {
                $msg = "Patient record created successfully (User account already existed).";
            }
        }

        if ($assignedDoctorID) {
            $doc_stmt = $conn->prepare("SELECT phone_number, full_name FROM \"doctorProfile\" WHERE \"userID\" = ?");
            $doc_stmt->execute([$assignedDoctorID]);
            $docInfo = $doc_stmt->fetch(PDO::FETCH_ASSOC);
            if ($docInfo && !empty($docInfo['phone_number'])) {
                include_once(__DIR__ . "/../backend/notification_service.php");
                $docPhone = $docInfo['phone_number'];
                $docName = $docInfo['full_name'];
                $msg_body = "Dr. $docName, new patient assigned to you: $name ($patient_id). Please review their profile on DigiHealth.";
                sendCriticalSMS($docPhone, $msg_body);
                $msg .= " Doctor notified.";
            }
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
