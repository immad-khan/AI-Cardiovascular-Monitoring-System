# DigiHealth — Honest Workflow Analysis
### Is This Industry-Ready? What's Broken? How to Fix It.

---

## The Core Problem in One Sentence

> The system has **three separate "create patient" paths** that don't talk to each other, no way for a patient to know who their doctor is, no way for a doctor to know when a new patient is assigned to them, and the IoT device (the entire point of the project) only works if someone manually goes into the admin panel and links it.

---

## What the Current Workflow Actually Looks Like

```
┌─────────────────────────────────────────────────────────────┐
│  PATH 1: Patient self-registers on sign-up.php              │
│  → Creates a row in users table (role='patient')            │
│  → Gets auto-redirected to Patient-Dashboard.php            │
│  → Has NO entry in patients table                           │
│  → Has NO assigned doctor                                   │
│  → Has NO linked IoT device                                 │
│  → Dashboard shows "Patient profile not linked. Contact     │
│    Administrator." — immediately broken on first login      │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│  PATH 2: Admin/Doctor creates patient via add-patient.php   │
│  → Creates a row in patients table (with patientID, doctor) │
│  → Creates NO entry in users table                          │
│  → The patient has no login credentials                     │
│  → They can never log in to see their own ECG data          │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│  PATH 3: Anyone registers as a "Doctor" on sign-up.php      │
│  → No verification, no approval, no admin gate              │
│  → Immediately gets access to patient data                  │
│  → A malicious actor can self-assign as doctor              │
└─────────────────────────────────────────────────────────────┘
```

---

## The 5 Critical Gaps

### Gap 1 — The "Split Brain" Patient Problem
**What happens:** When a patient self-registers, they exist in `users` but NOT in `patients`. The `Patient-Dashboard.php` query does:
```sql
SELECT patientID FROM patients WHERE email = ?
```
This returns nothing → dashboard is **instantly broken** for every self-registered patient.

**Real-world impact:** A patient buys a device, creates an account, logs in — and sees an error message. They have no doctor, no device, no readings. The system is completely unusable without admin intervention they don't know they need.

---

### Gap 2 — Patients Don't Know Their Doctor
**What happens:** The `patients` table has `assignedDoctorID`, but:
- The Patient Dashboard **never shows the doctor's name**
- There is no "My Doctor" section anywhere in the patient UI
- There is no notification sent to the patient when a doctor is assigned
- There is no way for a patient to contact their doctor through the system

**Real-world impact:** A patient logs in, sees their ECG chart, and has no idea who is monitoring them, who to call if something looks wrong, or even if anyone is watching at all.

---

### Gap 3 — Doctors Don't Know When Patients Are Assigned
**What happens:** When an admin assigns `assignedDoctorID = 5` to a patient, Doctor #5:
- Gets **no email, no notification, no alert**
- Only finds out if they happen to log in and see the count on their dashboard changed
- Has no way to know the patient's clinical urgency or why they were referred

**Real-world impact:** In a hospital with 20 doctors, a patient could be assigned and their doctor never knows for days. This is a patient safety issue.

---

### Gap 4 — IoT Device Assignment is Manual and Invisible
**What happens:**
1. ESP32 connects to Raspberry Pi, sends data to Azure
2. Azure inserts a new row in `monitoring_devices` with just the MAC address
3. The device **sits unassigned** — not linked to any patient
4. A human must go to Admin → Devices → manually assign it
5. Until that happens, ALL the ECG data is in the DB but visible to nobody

**Real-world impact:** When a nurse plugs in the device at a patient's bedside, nothing visible happens. The doctor's dashboard doesn't update. The patient's dashboard shows no readings. The ECG is being collected silently into a void.

---

### Gap 5 — No Appointment/Scheduling System
**What happens:** The `setup_appointments.php` exists as a file but:
- The database schema has no `appointments` table in `database_aligned.sql`
- Doctors have no way to schedule follow-ups based on ECG alerts
- Admins have no workflow for scheduling a consultation when the AI flags an abnormal rhythm
- There's a trigger for "Ventricular Tachycardia" detected → SMS sent → but no action taken in the system

**Real-world impact:** The AI correctly detects a dangerous heart rhythm. An SMS fires. Then what? There's no in-system ticket, no appointment, no escalation path. It's a dead end.

---

## What Industry-Ready Actually Looks Like

Here is how a real hospital HMS (like Epic, Cerner, or even open-source OpenMRS) handles this:

```
REGISTRATION FLOW (Industry Standard)
======================================

1. Admin creates the patient record (demographics, history)
   → System auto-generates a patient portal account (email + temp password)
   → Patient gets an email: "Welcome to DigiHealth. Your patient ID is P-1023.
      Your doctor is Dr. Ahmed. Login here."

2. Admin assigns a doctor
   → Doctor gets an in-system notification + email
   → Doctor's dashboard immediately shows the new patient

3. Admin assigns an IoT device to the patient
   → Device is pre-registered with a serial number
   → Once assigned, ALL data from that MAC auto-routes to that patient
   → Doctor gets notified: "Device linked. Monitoring is active."

4. When AI detects abnormal rhythm
   → Alert is created in system (already done ✅)
   → Doctor sees it on dashboard (partially done ✅)  
   → System auto-creates an "Urgent Review" task for the doctor
   → Doctor can mark it "Reviewed", add notes, schedule appointment
   → Patient sees "Your doctor has been notified" on their dashboard
```

---

## The Fix Plan (Prioritized)

### 🔴 Priority 1 — Fix the Split-Brain Patient Problem
**2 changes needed:**

1. When a patient self-registers in `sign-up.php`, also auto-insert into `patients` table:
```php
// After INSERT into users...
$stmt = $conn->prepare("INSERT INTO patients (patientID, name, email, phone_no, age, gender) 
                         VALUES (?, ?, ?, '', 0, 'Other')");
$stmt->execute(['P-' . $newUser['userID'], $user, $email]);
```

2. OR — remove patient self-registration entirely. Force patients to be created by admin/doctor. Send them a welcome email with credentials. This is actually the correct hospital model.

---

### 🔴 Priority 2 — Show Doctor Info to Patients
**1 page change:** Add to `Patient-Dashboard.php`:
```sql
SELECT dp.full_name, dp.specialization, dp.phone_number 
FROM doctorProfile dp 
JOIN patients p ON dp.userID = p.assignedDoctorID 
WHERE p.patientID = ?
```
Display: **"Your cardiologist: Dr. Sara Ali (Cardiology) — 0321-XXXXXXX"**

---

### 🟠 Priority 3 — Notify Doctor on Patient Assignment
**1 backend change:** In `patient_logic.php`, after saving `assignedDoctorID`, send a notification:
```php
// Fetch doctor email
// Send: "A new patient [Name] has been assigned to you. View their profile here."
// Also insert into a notifications table for in-app badge
```

---

### 🟠 Priority 4 — Auto-Link Device on First Contact
**1 API change:** In `vitals.php`, after creating/finding the device, check if it should auto-assign. At minimum, if a device is "Offline" and starts sending data, flip it to "Online" so the doctor's dashboard reflects reality immediately.

---

### 🟡 Priority 5 — Add a Minimal "Tasks" System for Alerts
**New table + UI:** When the AI fires a critical alert, instead of just an SMS:
```sql
-- New table
CREATE TABLE doctor_tasks (
    taskID SERIAL PRIMARY KEY,
    doctorID INT,
    patientID VARCHAR,
    readingID INT,
    task_type VARCHAR DEFAULT 'Review ECG',
    status ENUM('Pending', 'Reviewed', 'Escalated'),
    created_at TIMESTAMP DEFAULT NOW()
);
```
Doctor sees a "Pending Reviews" count on their dashboard. They can click, see the ECG, mark reviewed, add a note.

---

## Summary Table

| Gap | Severity | Fix Effort | Status |
|-----|----------|-----------|--------|
| Patient self-signup creates broken account | 🔴 Critical | 1 hour | ❌ Broken now |
| Patient can't see their doctor | 🔴 Critical | 30 min | ❌ Missing |
| Doctor not notified on assignment | 🔴 Critical | 2 hours | ❌ Missing |
| IoT device is invisible until manually assigned | 🟠 High | 1 hour | ❌ Missing |
| AI alerts are a dead end (no task/follow-up) | 🟠 High | 4 hours | ❌ Missing |
| Anyone can self-register as a doctor | 🟠 High | 30 min | ❌ Security gap |
| No appointment scheduling | 🟡 Medium | 1 day | ❌ Missing |
| ECG data visible on Patient Profile | 🟢 Working | — | ✅ Done |
| AI prediction + DB insert | 🟢 Working (after fix) | — | ✅ Fixed today |
| Critical alert → SMS | 🟢 Working | — | ✅ Done |
