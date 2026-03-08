-- Supabase (PostgreSQL) Migration Script for AI-Powered Cardiovascular Patient Monitoring System V 1.0
-- Aligned with ERD Documentation (March 2026)

-- Enable uuid-ossp extension for UUID generation if needed (Supabase has this by default)
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- 1. USER Table
CREATE TABLE IF NOT EXISTS users (
    "userID" SERIAL PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) CHECK (role IN ('admin', 'doctor', 'patient', 'tech-admin')) NOT NULL,
    "isActive" BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- 2. DOCTOR Table
CREATE TABLE IF NOT EXISTS "doctorProfile" (
    "doctorID" SERIAL PRIMARY KEY,
    "userID" INT NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    specialization VARCHAR(255) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    profile_picture VARCHAR(255),
    description TEXT,
    website_url VARCHAR(255),
    FOREIGN KEY ("userID") REFERENCES users("userID") ON DELETE CASCADE
);

-- 3. PATIENT Table
CREATE TABLE IF NOT EXISTS patients (
    id SERIAL PRIMARY KEY,
    "patientID" VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    phone_no VARCHAR(20) NOT NULL,
    email VARCHAR(255) NOT NULL,
    age INT NOT NULL,
    gender VARCHAR(20) CHECK (gender IN ('Male', 'Female', 'Other')) NOT NULL,
    medical_history TEXT,
    "assignedDoctorID" INT, -- FK to users."userID"
    "assignedAdminID" INT,  -- FK to users."userID"
    staff_name VARCHAR(255),
    ward_no VARCHAR(50),
    date TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    "isActive" BOOLEAN DEFAULT TRUE,
    FOREIGN KEY ("assignedDoctorID") REFERENCES users("userID"),
    FOREIGN KEY ("assignedAdminID") REFERENCES users("userID")
);

-- 4. MONITORING_DEVICE Table
CREATE TABLE IF NOT EXISTS monitoring_devices (
    "deviceID" SERIAL PRIMARY KEY,
    mac_address VARCHAR(255) NOT NULL UNIQUE,
    "serialNo" VARCHAR(255) UNIQUE,
    model VARCHAR(255),
    location VARCHAR(255),
    "patientID" VARCHAR(255), -- FK to patients."patientID"
    status VARCHAR(50) DEFAULT 'Offline' CHECK (status IN ('Online', 'Offline', 'Assigned')),
    last_heartbeat TIMESTAMPTZ,
    FOREIGN KEY ("patientID") REFERENCES patients("patientID") ON DELETE SET NULL
);

-- 5. DEVICE_PATIENT_LINK
CREATE TABLE IF NOT EXISTS device_patient_link (
    id SERIAL PRIMARY KEY,
    patient_id VARCHAR(255) NOT NULL,
    mac_address VARCHAR(255) NOT NULL,
    linked_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    delinked_at TIMESTAMPTZ NULL,
    FOREIGN KEY (patient_id) REFERENCES patients("patientID") ON DELETE CASCADE
);

-- 6. VITAL_SIGN_READING Table
CREATE TABLE IF NOT EXISTS vital_sign_readings (
    "readingID" SERIAL PRIMARY KEY,
    "deviceID" INT NOT NULL, -- FK to monitoring_devices."deviceID"
    timestamp TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    "heartRate" INT,
    "SpO2" FLOAT,
    "RespirationImpedance" FLOAT,
    "ecgRawRef" TEXT, -- Text in Postgres for long data
    final_prediction VARCHAR(255),
    "confidenceScore" FLOAT,
    "PDR" DOUBLE PRECISION,
    "PLR" DOUBLE PRECISION,
    device_type VARCHAR(100) DEFAULT 'Raspberry Pi 4',
    FOREIGN KEY ("deviceID") REFERENCES monitoring_devices("deviceID") ON DELETE CASCADE
);

-- 7. Raw ECG Samples Table
CREATE TABLE IF NOT EXISTS esp_ecg_data (
    id SERIAL PRIMARY KEY,
    "readingID" INT NOT NULL,
    ecg_value FLOAT NOT NULL,
    FOREIGN KEY ("readingID") REFERENCES vital_sign_readings("readingID") ON DELETE CASCADE
);

-- 8. AI_PREDICTION_LOG Table
CREATE TABLE IF NOT EXISTS "AI_PREDICTION_LOG" (
    "predictionID" SERIAL PRIMARY KEY,
    "readingID" INT NOT NULL, -- FK to vital_sign_readings."readingID"
    "predictionClass" VARCHAR(100),
    "confidenceScore" FLOAT,
    inference_time_ms INT,
    model_version VARCHAR(50) DEFAULT 'CardioNet-V1.0',
    timestamp TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY ("readingID") REFERENCES vital_sign_readings("readingID") ON DELETE CASCADE
);

-- 9. CRITICAL_ALERT Table
CREATE TABLE IF NOT EXISTS "CRITICAL_ALERT" (
    "alertID" SERIAL PRIMARY KEY,
    "deviceID" INT NOT NULL, -- FK to monitoring_devices."deviceID"
    timestamp TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    message TEXT,
    status VARCHAR(50) DEFAULT 'Active' CHECK (status IN ('Active', 'Acknowledged', 'Resolved')),
    FOREIGN KEY ("deviceID") REFERENCES monitoring_devices("deviceID") ON DELETE CASCADE
);
