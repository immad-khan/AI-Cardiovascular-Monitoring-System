import psycopg2

try:
    conn = psycopg2.connect(
        host="aws-1-ap-southeast-2.pooler.supabase.com",
        port=6543,
        dbname="postgres",
        user="postgres.jopkxezkpyfjixxtrfnw",
        password="S!ddeeq5696"
    )
    cur = conn.cursor()
    cur.execute("DELETE FROM monitoring_devices WHERE \"deviceID\" = 1;")
    cur.execute("DELETE FROM device_patient_link WHERE mac_address = '14.23.423.42';")
    conn.commit()
    print("Deleted device 1.")
except Exception as e:
    print(e)
