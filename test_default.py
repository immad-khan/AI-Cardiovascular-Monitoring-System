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
    cur.execute("SELECT column_name, column_default FROM information_schema.columns WHERE table_name = 'device_patient_link'")
    print(cur.fetchall())
except Exception as e:
    print(e)
