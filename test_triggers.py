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
    cur.execute("SELECT trigger_name, event_manipulation, event_object_table, action_statement FROM information_schema.triggers")
    print(cur.fetchall())
except Exception as e:
    print(e)
