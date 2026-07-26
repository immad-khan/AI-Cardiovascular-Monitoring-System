-- Migration: Add profile_picture column to users, patients, and doctorProfile tables
-- Run this SQL against your Supabase/PostgreSQL database

-- Add profile_picture to users table (for admin accounts)
ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(500);

-- Add profile_picture to patients table
ALTER TABLE patients ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(500);

-- Add profile_picture to doctorProfile table (if not already present from ERD)
ALTER TABLE "doctorProfile" ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(500);
