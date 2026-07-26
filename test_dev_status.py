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
    cursor.execute('SELECT mac_address, status, "patientID" FROM monitoring_devices WHERE mac_address = %s', ('11:22:33:44:55:66',))
    print('Device:', cursor.fetchall())
except Exception as e:
    print('Error:', e)
