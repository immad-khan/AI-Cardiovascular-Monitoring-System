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
    cursor.execute("INSERT INTO monitoring_devices (mac_address, model, status) VALUES ('11:22:33:44:55:66', 'ESP32 Wearable', 'Offline')")
    conn.commit()
    print('Inserted new device')
except Exception as e:
    print('Error:', e)
