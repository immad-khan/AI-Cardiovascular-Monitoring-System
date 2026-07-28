-- Migration: Enhance subscriptions table for approval workflow
-- Run this against your Supabase/PostgreSQL database

-- Add rejection reason column (stores admin's reason for record keeping)
ALTER TABLE subscriptions ADD COLUMN IF NOT EXISTS rejection_reason TEXT DEFAULT NULL;

-- Add reference to the created patient ID (set when admin approves and creates patient)
ALTER TABLE subscriptions ADD COLUMN IF NOT EXISTS created_patient_id VARCHAR(255) DEFAULT NULL;

-- Add reviewed_at timestamp (when admin approved or rejected)
ALTER TABLE subscriptions ADD COLUMN IF NOT EXISTS reviewed_at TIMESTAMP DEFAULT NULL;

-- Add reviewed_by (admin userID who took action)
ALTER TABLE subscriptions ADD COLUMN IF NOT EXISTS reviewed_by INT DEFAULT NULL;
