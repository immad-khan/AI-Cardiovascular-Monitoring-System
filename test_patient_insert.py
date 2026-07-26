import psycopg2
import os

try:
    conn = psycopg2.connect(
        host='aws-1-ap-southeast-2.pooler.supabase.com',
        port=6543,
        dbname='postgres',
        user='postgres.jopkxezkpyfjixxtrfnw',
        password='S!ddeeq5696'
    )
    cursor = conn.cursor()
    # Mocking patient logic
    patient_id = "P-TEST"
    mac_address = "11:22:33:44:55:66"
    
    # 1. Insert patient
    cursor.execute("""
        INSERT INTO patients ("patientID", name, phone_no, email, age, gender) 
        VALUES (%s, %s, %s, %s, %s, %s)
    """, (patient_id, 'Test', '123', 'test@test.com', 30, 'Male'))
    
    # 2. device_patient_link
    cursor.execute("INSERT INTO device_patient_link (patient_id, mac_address) VALUES (%s, %s)", (patient_id, mac_address))
    
    # 3. monitoring_devices
    cursor.execute("""
        UPDATE monitoring_devices SET status = 'Assigned', "patientID" = %s WHERE mac_address = %s
    """, (patient_id, mac_address))
    
    conn.commit()
    print("Success")
except Exception as e:
    print('Error:', e)
