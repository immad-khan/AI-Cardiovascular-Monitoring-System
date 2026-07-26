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
    cur.execute("SELECT \"deviceID\", COUNT(*) FROM vital_sign_readings GROUP BY \"deviceID\";")
    print("Readings per device:")
    print(cur.fetchall())
except Exception as e:
    print(e)
