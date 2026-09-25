import mysql.connector

def drop_and_reimport():
    conn = mysql.connector.connect(
        host='127.0.0.1',
        port=3306,
        user='root',
        password='root',
        database='database_ambalan'
    )
    cursor = conn.cursor()
    
    # Disable foreign key checks
    cursor.execute("SET FOREIGN_KEY_CHECKS=0")
    
    # Get all tables
    cursor.execute("SHOW TABLES")
    tables = [row[0] for row in cursor.fetchall()]
    
    # Drop all tables
    for table in tables:
        cursor.execute(f"DROP TABLE IF EXISTS `{table}`")
        print(f"Dropped {table}")
    
    cursor.execute("SET FOREIGN_KEY_CHECKS=1")
    conn.commit()
    
    # Now import the SQL file
    with open("C:/Users/Advan/database_ambalan.sql", 'r', encoding='utf-8') as f:
        sql = f.read()
    
    # Split by semicolon and execute
    statements = sql.split(';')
    for i, stmt in enumerate(statements):
        stmt = stmt.strip()
        if not stmt or stmt.startswith('--') or stmt.startswith('/*!'):
            continue
        try:
            cursor.execute(stmt)
            if i % 50 == 0:
                print(f"Executed statement {i}")
        except Exception as e:
            print(f"Error at statement {i}: {e}")
            print(f"Statement: {stmt[:200]}")
    
    conn.commit()
    cursor.close()
    conn.close()
    print("Import complete!")

if __name__ == '__main__':
    drop_and_reimport()