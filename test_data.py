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
    cur.execute("SELECT \"deviceID\", mac_address, \"patientID\", status, model FROM monitoring_devices;")
    print("Devices:")
    for row in cur.fetchall():
        print(row)
        
    cur.execute("SELECT \"patientID\", name FROM patients;")
    print("\nPatients:")
    for row in cur.fetchall():
        print(row)
except Exception as e:
    print(e)
