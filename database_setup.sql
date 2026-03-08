-- Database Initialization Script for AI-Powered Cardiovascular Patient Monitoring System V 1.0
-- Database: gatewayl_ecg_db

CREATE DATABASE IF NOT EXISTS `gatewayl_ecg_db`;
USE `gatewayl_ecg_db`;

-- 1. User Authentication Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(255) NOT NULL UNIQUE,
    `email` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `type` ENUM('admin', 'doctor', 'patient', 'tech-admin') NOT NULL,
    `isActive` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Doctor Profiles
CREATE TABLE IF NOT EXISTS `doctorProfile` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `full_name` VARCHAR(255) NOT NULL,
    `specialization` VARCHAR(255) NOT NULL,
    `phone_number` VARCHAR(20) NOT NULL,
    `profile_picture` VARCHAR(255),
    `description` TEXT,
    `website_url` VARCHAR(255),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Patient Records
CREATE TABLE IF NOT EXISTS `patients` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `patient_id` VARCHAR(255) UNIQUE,
    `phone_no` VARCHAR(20) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `age` INT NOT NULL,
    `gender` ENUM('Male', 'Female', 'Other') NOT NULL,
    `medical_history` TEXT,
    `associated_doctors` TEXT, -- Stores comma-separated doctor IDs or JSON
    `staff_name` VARCHAR(255),
    `ward_no` VARCHAR(50),
    `date` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `isActive` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. MONITORING_DEVICE (Expanded from ecg_devices)
CREATE TABLE IF NOT EXISTS `ecg_devices` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `mac_address` VARCHAR(255) NOT NULL UNIQUE,
    `serial_no` VARCHAR(255) UNIQUE, -- From documentation
    `model` VARCHAR(255),
    `location` VARCHAR(255), -- Ward/Room
    `status` ENUM('Online', 'Offline', 'Assigned') DEFAULT 'Offline',
    `last_heartbeat` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Device-Patient Mapping
CREATE TABLE IF NOT EXISTS `device_patient_link` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `patient_id` VARCHAR(255) NOT NULL,
    `mac_address` VARCHAR(255) NOT NULL,
    `linked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `delinked_at` DATETIME NULL,
    FOREIGN KEY (`patient_id`) REFERENCES `patients`(`patient_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. VITAL_SIGN_READING (Expanded from esp_ecg_predictions)
CREATE TABLE IF NOT EXISTS `esp_ecg_predictions` (
    `record_id` INT AUTO_INCREMENT PRIMARY KEY,
    `mac_address` VARCHAR(255) NOT NULL,
    `final_prediction` VARCHAR(255), -- AI Diagnosis
    `confidenceScore` FLOAT, -- From documentation
    `PDR` DOUBLE,
    `PLR` DOUBLE,
    `HighestDelay` VARCHAR(255),
    `AvgDelay` VARCHAR(255),
    `Throughput` VARCHAR(255),
    `reading_duration` VARCHAR(255),
    `fileSizeInBytes` VARCHAR(255),
    `totalReceivedSamples` VARCHAR(255),
    `processingStartTime` VARCHAR(255),
    `device` VARCHAR(255),
    `datetime` DATETIME DEFAULT CURRENT_TIMESTAMP,
    -- Expanded Vital Signs (Hardware Data Flow)
    `heartRate` INT,
    `SpO2` FLOAT, -- Blood Oxygen from MAX30102
    `Temperature` FLOAT, -- Body Temp from MLX90614
    `RespirationRate` INT, -- Resp from ADS1292R
    `ECG_24bit_data` LONGTEXT -- Raw signal fragment if needed
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Raw ECG Data Points (For visualization)
CREATE TABLE IF NOT EXISTS `esp_ecg_data` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `record_id` INT NOT NULL,
    `ecg_value` FLOAT NOT NULL,
    FOREIGN KEY (`record_id`) REFERENCES `esp_ecg_predictions`(`record_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. AI Prediction Logs (Planned Phase)
CREATE TABLE IF NOT EXISTS `AI_PREDICTION_LOG` (
    `predictionID` INT AUTO_INCREMENT PRIMARY KEY,
    `record_id` INT NOT NULL,
    `predictionClass` VARCHAR(100), -- Normal, Tachycardia, AFib
    `confidenceScore` FLOAT,
    `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`record_id`) REFERENCES `esp_ecg_predictions`(`record_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Critical Alerts
CREATE TABLE IF NOT EXISTS `CRITICAL_ALERT` (
    `alertID` INT AUTO_INCREMENT PRIMARY KEY,
    `mac_address` VARCHAR(255) NOT NULL,
    `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `message` TEXT,
    `status` ENUM('Active', 'Acknowledged', 'Resolved') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
