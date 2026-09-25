import csv
import psycopg2
import os

# Supabase connection
SUPABASE_CONFIG = {
    'host': 'aws-0-ap-southeast-1.pooler.supabase.com',
    'port': 6543,
    'database': 'postgres',
    'user': 'postgres.ugbpqikqduptseeslfri',
    'password': 'RezaNeko26@'
}

CSV_DIR = r'C:\Kuliah Jaya Jaya Jaya\SKRIPSI GWEH\Project PWA\csv_export'

# Table name -> (CSV filename, column mapping, primary key)
TABLES = {
    'ambalans': ('ambalans.csv', None, 'id'),
    'angkatans': ('angkatans.csv', None, 'id'),
    'attendance_sessions': ('attendance_sessions.csv', None, 'id'),
    'audit_logs': ('audit_logs.csv', None, 'id'),
    'members': ('members.csv', None, 'id'),
    'migrations': ('migrations.csv', None, 'id'),
    'pengurus_positions': ('pengurus_positions.csv', None, 'id'),
    'sessions': ('sessions.csv', None, 'id'),
    'sku_points': ('sku_points.csv', None, 'id'),
    'tkk_points': ('tkk_points.csv', None, 'id'),
    'users': ('users.csv', None, 'id'),
}

def import_csv_to_table(cursor, table_name, csv_file, pk_column):
    filepath = os.path.join(CSV_DIR, csv_file)
    if not os.path.exists(filepath):
        print(f'  SKIP: {csv_file} not found')
        return
    
    with open(filepath, 'r', encoding='utf-8') as f:
        reader = csv.DictReader(f)
        rows = list(reader)
    
    if not rows:
        print(f'  EMPTY: {table_name}')
        return
    
    # Get columns from CSV
    columns = list(rows[0].keys())
    
    # Build INSERT with ON CONFLICT
    placeholders = ', '.join(['%s'] * len(columns))
    cols_str = ', '.join([f'"{c}"' for c in columns])
    pk_quoted = f'"{pk_column}"'
    
    insert_sql = f'''
        INSERT INTO "{table_name}" ({cols_str})
        VALUES ({placeholders})
        ON CONFLICT ({pk_quoted}) DO UPDATE SET
        {', '.join([f'"{c}" = EXCLUDED."{c}"' for c in columns if c != pk_column])}
    '''
    
    # Convert row values
    data = []
    for row in rows:
        vals = []
        for col in columns:
            val = row[col]
            if val == '' or val.lower() == 'null':
                vals.append(None)
            else:
                vals.append(val)
        data.append(vals)
    
    try:
        cursor.executemany(insert_sql, data)
        print(f'  OK: {table_name} - {len(data)} rows')
    except Exception as e:
        print(f'  ERROR: {table_name} - {e}')
        # Try without ON CONFLICT
        simple_sql = f'INSERT INTO "{table_name}" ({cols_str}) VALUES ({placeholders})'
        try:
            cursor.executemany(simple_sql, data)
            print(f'  OK (simple): {table_name} - {len(data)} rows')
        except Exception as e2:
            print(f'  FAILED: {table_name} - {e2}')

def main():
    conn = psycopg2.connect(**SUPABASE_CONFIG)
    conn.autocommit = False
    cursor = conn.cursor()
    
    print('Importing CSV data to Supabase...')
    print(f'CSV directory: {CSV_DIR}')
    print()
    
    for table_name, (csv_file, _, pk) in TABLES.items():
        print(f'Importing {table_name}...')
        import_csv_to_table(cursor, table_name, csv_file, pk)
    
    conn.commit()
    cursor.close()
    conn.close()
    print('\nDone!')

if __name__ == '__main__':
    main()