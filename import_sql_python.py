import mysql.connector

def import_sql_file():
    conn = mysql.connector.connect(
        host='127.0.0.1',
        port=3306,
        user='root',
        password='root',
        database='database_ambalan'
    )
    conn.autocommit = False
    cursor = conn.cursor()
    
    # Read the SQL file
    with open("C:/Users/Advan/database_ambalan.sql", 'r', encoding='utf-8') as f:
        sql = f.read()
    
    # Remove START TRANSACTION and COMMIT - we'll handle transactions ourselves
    sql = sql.replace('START TRANSACTION;', '')
    sql = sql.replace('COMMIT;', '')
    
    # Split by semicolon but be careful with multi-line statements
    # Better approach: split by ';\n' or ';\r\n'
    statements = sql.split(';')
    
    print(f"Total statements: {len(statements)}")
    
    executed = 0
    errors = 0
    
    for i, stmt in enumerate(statements):
        stmt = stmt.strip()
        if not stmt or stmt.startswith('--') or stmt.startswith('/*!'):
            continue
        
        # Skip SET statements that might cause issues
        if stmt.startswith('SET SQL_MODE') or stmt.startswith('SET time_zone'):
            continue
            
        try:
            cursor.execute(stmt)
            executed += 1
            if executed % 50 == 0:
                print(f"Executed {executed} statements...")
        except Exception as e:
            errors += 1
            if errors <= 10:
                print(f"Error at statement {i+1}: {e}")
                print(f"Statement preview: {stmt[:300]}")
    
    conn.commit()
    cursor.close()
    conn.close()
    print(f"Done! Executed: {executed}, Errors: {errors}")

if __name__ == '__main__':
    import_sql_file()