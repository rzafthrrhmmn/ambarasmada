import mysql.connector

def import_sql_file():
    # Connect without database first
    conn = mysql.connector.connect(
        host='127.0.0.1',
        port=3306,
        user='root',
        password='root'
    )
    conn.autocommit = True
    cursor = conn.cursor()
    
    # Drop and recreate database
    cursor.execute('DROP DATABASE IF EXISTS database_ambalan')
    cursor.execute('CREATE DATABASE database_ambalan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci')
    cursor.execute('USE database_ambalan')
    
    # Read the SQL file
    with open("C:/Users/Advan/database_ambalan.sql", 'r', encoding='utf-8') as f:
        sql = f.read()
    
    # Split statements properly - handle DELIMITER changes
    # The file uses standard semicolon delimiter
    statements = []
    current = []
    
    for line in sql.split('\n'):
        line = line.strip()
        if not line or line.startswith('--'):
            continue
        if line.startswith('/*!'):
            continue
        if line.startswith('SET SQL_MODE') or line.startswith('SET time_zone') or line.startswith('START TRANSACTION') or line.startswith('COMMIT'):
            continue
            
        current.append(line)
        if line.endswith(';'):
            statements.append(' '.join(current))
            current = []
    
    # Add any remaining
    if current:
        statements.append(' '.join(current))
    
    print(f"Total statements to execute: {len(statements)}")
    
    executed = 0
    errors = 0
    
    for i, stmt in enumerate(statements):
        stmt = stmt.strip()
        if not stmt:
            continue
            
        try:
            cursor.execute(stmt)
            executed += 1
            if executed % 50 == 0:
                print(f"Executed {executed} statements...")
        except Exception as e:
            errors += 1
            if errors <= 15:
                print(f"Error {errors} at statement {i+1}: {e}")
                print(f"  Statement: {stmt[:200]}...")
    
    cursor.close()
    conn.close()
    print(f"Done! Executed: {executed}, Errors: {errors}")

if __name__ == '__main__':
    import_sql_file()