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
    cursor.execute("INSERT INTO monitoring_devices (mac_address, model, status) VALUES ('12:34:56:78:90:AB', 'Test Device', 'Offline')")
    conn.commit()
    
    patientId = ''
    cursor.execute('SELECT mac_address, model FROM monitoring_devices WHERE status != %s OR "patientID" = %s', ('Assigned', patientId))
    devices = cursor.fetchall()
    print('Available devices for empty patientId:', devices)
except Exception as e:
    print('Error:', e)
