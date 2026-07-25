import psycopg2
import os

try:
    conn = psycopg2.connect(
        host="aws-1-ap-southeast-2.pooler.supabase.com",
        port=6543,
        dbname="postgres",
        user="postgres.jopkxezkpyfjixxtrfnw",
        password="S!ddeeq5696"
    )
    cur = conn.cursor()
    sql = """
    CREATE TABLE IF NOT EXISTS doctor_tasks (
        "taskID" SERIAL PRIMARY KEY,
        "doctorID" INT NOT NULL,
        "patientID" VARCHAR(255) NOT NULL,
        "readingID" INT,
        "alertID" INT,
        "task_type" VARCHAR(100) DEFAULT 'Review ECG',
        "status" VARCHAR(50) DEFAULT 'Pending',
        "notes" TEXT,
        "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        "updated_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY ("doctorID") REFERENCES users("userID") ON DELETE CASCADE,
        FOREIGN KEY ("patientID") REFERENCES patients("patientID") ON DELETE CASCADE
    );
    """
    cur.execute(sql)
    conn.commit()
    cur.close()
    conn.close()
    print("Migration successful. Table doctor_tasks created.")
except Exception as e:
    print(f"Migration failed: {e}")
