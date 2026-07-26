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
    cursor.execute('SELECT mac_address, model, status, "patientID" FROM monitoring_devices')
    devices = cursor.fetchall()
    print('Devices:', devices)
    
    cursor.execute('SELECT "patientID" FROM patients LIMIT 5')
    print('Patients:', cursor.fetchall())
except Exception as e:
    print('Error:', e)
