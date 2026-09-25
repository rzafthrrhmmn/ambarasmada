import psycopg2
conn = psycopg2.connect(
    host='aws-0-ap-southeast-1.pooler.supabase.com',
    port=6543,
    database='postgres',
    user='postgres.ugbpqikqduptseeslfri',
    password='RezaNeko26@'
)
cursor = conn.cursor()
tables = ['ambalans', 'angkatans', 'attendance_sessions', 'audit_logs', 'members', 
          'migrations', 'pengurus_positions', 'sessions', 'sku_points', 'tkk_points', 'users']
for t in tables:
    cursor.execute('SELECT COUNT(*) FROM "' + t + '"')
    count = cursor.fetchone()[0]
    print(t + ': ' + str(count) + ' rows')
cursor.close()
conn.close()