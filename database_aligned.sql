-- Database Initialization Script for AI-Powered Cardiovascular Patient Monitoring System V 1.0
-- Aligned with ERD Documentation (March 2026)
-- Database: gatewayl_ecg_db

CREATE DATABASE IF NOT EXISTS `gatewayl_ecg_db`;
USE `gatewayl_ecg_db`;

-- 1. USER Table
CREATE TABLE IF NOT EXISTS `users` (
    `userID` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(255) NOT NULL UNIQUE,
    `email` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'doctor', 'patient', 'tech-admin') NOT NULL,
    `isActive` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. DOCTOR Table
CREATE TABLE IF NOT EXISTS `doctorProfile` (
    `doctorID` INT AUTO_INCREMENT PRIMARY KEY,
    `userID` INT NOT NULL,
    `full_name` VARCHAR(255) NOT NULL,
    `specialization` VARCHAR(255) NOT NULL,
    `phone_number` VARCHAR(20) NOT NULL,
    `profile_picture` VARCHAR(255),
    `description` TEXT,
    `website_url` VARCHAR(255),
    FOREIGN KEY (`userID`) REFERENCES `users`(`userID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. PATIENT Table
CREATE TABLE IF NOT EXISTS `patients` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `patientID` VARCHAR(255) UNIQUE NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `phone_no` VARCHAR(20) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `age` INT NOT NULL,
    `gender` ENUM('Male', 'Female', 'Other') NOT NULL,
    `medical_history` TEXT,
    `assignedDoctorID` INT, -- FK to User.userID (as per diagram)
    `assignedAdminID` INT,  -- FK to User.userID (as per diagram)
    `staff_name` VARCHAR(255),
    `ward_no` VARCHAR(50),
    `date` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `isActive` TINYINT(1) DEFAULT 1,
    FOREIGN KEY (`assignedDoctorID`) REFERENCES `users`(`userID`),
    FOREIGN KEY (`assignedAdminID`) REFERENCES `users`(`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. MONITORING_DEVICE Table
CREATE TABLE IF NOT EXISTS `monitoring_devices` (
    `deviceID` INT AUTO_INCREMENT PRIMARY KEY,
    `mac_address` VARCHAR(255) NOT NULL UNIQUE,
    `serialNo` VARCHAR(255) UNIQUE,
    `model` VARCHAR(255),
    `location` VARCHAR(255),
    `patientID` VARCHAR(255), -- FK to Patient.patientID
    `status` ENUM('Online', 'Offline', 'Assigned') DEFAULT 'Offline',
    `last_heartbeat` TIMESTAMP NULL,
    FOREIGN KEY (`patientID`) REFERENCES `patients`(`patientID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. DEVICE_PATIENT_LINK (Internal Mapping for historical tracking)
CREATE TABLE IF NOT EXISTS `device_patient_link` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `patient_id` VARCHAR(255) NOT NULL,
    `mac_address` VARCHAR(255) NOT NULL,
    `linked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `delinked_at` DATETIME NULL,
    FOREIGN KEY (`patient_id`) REFERENCES `patients`(`patientID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. VITAL_SIGN_READING Table
CREATE TABLE IF NOT EXISTS `vital_sign_readings` (
    `readingID` INT AUTO_INCREMENT PRIMARY KEY,
    `deviceID` INT NOT NULL, -- FK to monitoring_devices.deviceID
    `timestamp` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `heartRate` INT,
    `SpO2` FLOAT, -- Blood Oxygen from MAX30102
    `RespirationImpedance` FLOAT, -- From ADS1292R (specifically mentioned in doc)
    `ecgRawRef` LONGTEXT, -- 24-bit raw signal reference
    `final_prediction` VARCHAR(255), -- AI Diagnosis Summary
    `confidenceScore` FLOAT,
    `PDR` DOUBLE,
    `PLR` DOUBLE,
    `device_type` VARCHAR(100) DEFAULT 'Raspberry Pi 4',
    FOREIGN KEY (`deviceID`) REFERENCES `monitoring_devices`(`deviceID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Raw ECG Samples Table (Sub-entities for visualization)
CREATE TABLE IF NOT EXISTS `esp_ecg_data` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `readingID` INT NOT NULL,
    `ecg_value` FLOAT NOT NULL,
    FOREIGN KEY (`readingID`) REFERENCES `vital_sign_readings`(`readingID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. AI_PREDICTION_LOG Table
CREATE TABLE IF NOT EXISTS `AI_PREDICTION_LOG` (
    `predictionID` INT AUTO_INCREMENT PRIMARY KEY,
    `readingID` INT NOT NULL, -- FK to VitalSignReading.readingID
    `predictionClass` VARCHAR(100), -- Normal, Tachycardia, AFib
    `confidenceScore` FLOAT,
    `inference_time_ms` INT,
    `model_version` VARCHAR(50) DEFAULT 'CardioNet-V1.0',
    `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`readingID`) REFERENCES `vital_sign_readings`(`readingID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. CRITICAL_ALERT Table
CREATE TABLE IF NOT EXISTS `CRITICAL_ALERT` (
    `alertID` INT AUTO_INCREMENT PRIMARY KEY,
    `deviceID` INT NOT NULL, -- FK to monitoring_devices.deviceID
    `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `message` TEXT,
    `status` ENUM('Active', 'Acknowledged', 'Resolved') DEFAULT 'Active',
    FOREIGN KEY (`deviceID`) REFERENCES `monitoring_devices`(`deviceID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
