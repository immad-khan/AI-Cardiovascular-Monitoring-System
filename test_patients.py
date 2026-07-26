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
    cur.execute("SELECT * FROM patients WHERE \"patientID\" IN ('p-05', 'p-07')")
    print(cur.fetchall())
except Exception as e:
    print(e)
