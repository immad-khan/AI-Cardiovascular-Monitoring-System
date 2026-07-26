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
    # Delete test devices I created
    cursor.execute("DELETE FROM monitoring_devices WHERE mac_address IN ('11:22:33:44:55:66', '12:34:56:78:90:AB')")
    
    # Also delete the test patient if any (P-TEST was created in python)
    cursor.execute("DELETE FROM patients WHERE \"patientID\" = 'P-TEST'")
    
    conn.commit()
    print('Deleted test devices and patients')
except Exception as e:
    print('Error:', e)
