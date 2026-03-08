-- 1. Correct Tables/Column names to strictly match Class Diagram ERD

-- Adding 'serialNo' and 'lastSeenTimestamp' to monitoring_devices
ALTER TABLE monitoring_devices RENAME COLUMN last_heartbeat TO "lastSeenTimestamp";

-- 2. Align VitalSignReading Table (Adding missing fields or aliasing)
-- heartRate, spo2, temperature, ecgRaw, respirationImpedance
DO $$ 
BEGIN 
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='vital_sign_readings' AND column_name='temperature') THEN
        ALTER TABLE vital_sign_readings ADD COLUMN temperature FLOAT DEFAULT 37.0;
    END IF;
END $$;

-- 3. Align AI_PREDICTION_LOG
-- Adding 'criticalAlertFlag'
DO $$ 
BEGIN 
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='AI_PREDICTION_LOG' AND column_name='criticalAlertFlag') THEN
        ALTER TABLE "AI_PREDICTION_LOG" ADD COLUMN "criticalAlertFlag" BOOLEAN DEFAULT FALSE;
    END IF;
END $$;

-- 4. Align CRITICAL_ALERT table 
-- status: New/Closed\nacknowledgedBy: DoctorID (FK)
DO $$ 
BEGIN 
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='CRITICAL_ALERT' AND column_name='acknowledgedBy') THEN
        ALTER TABLE "CRITICAL_ALERT" ADD COLUMN "acknowledgedBy" INT;
        ALTER TABLE "CRITICAL_ALERT" ADD CONSTRAINT fk_doctor_ack FOREIGN KEY ("acknowledgedBy") REFERENCES users("userID");
    END IF;
END $$;

-- Standardizing Alert Status types
ALTER TABLE "CRITICAL_ALERT" DROP CONSTRAINT IF EXISTS "CRITICAL_ALERT_status_check";
ALTER TABLE "CRITICAL_ALERT" ADD CONSTRAINT "CRITICAL_ALERT_status_check" CHECK (status IN ('New', 'Closed', 'Acknowledged'));

