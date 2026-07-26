-- Migration: Create chat_messages table for doctor-patient messaging
CREATE TABLE IF NOT EXISTS chat_messages (
    id SERIAL PRIMARY KEY,
    sender_type VARCHAR(10) NOT NULL CHECK (sender_type IN ('patient', 'doctor')),
    sender_id VARCHAR(255) NOT NULL,
    receiver_type VARCHAR(10) NOT NULL CHECK (receiver_type IN ('patient', 'doctor')),
    receiver_id VARCHAR(255) NOT NULL,
    patient_id VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_chat_sender ON chat_messages(sender_id, sender_type);
CREATE INDEX IF NOT EXISTS idx_chat_receiver ON chat_messages(receiver_id, receiver_type);
CREATE INDEX IF NOT EXISTS idx_chat_patient ON chat_messages(patient_id);
CREATE INDEX IF NOT EXISTS idx_chat_unread ON chat_messages(receiver_id, receiver_type, is_read);
