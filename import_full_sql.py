import mysql.connector
import os

def drop_all_and_import():
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
    cursor.close()
    conn.close()
    print("All tables dropped")
    
    # Now use mysql command line to import the full dump
    import subprocess
    result = subprocess.run([
        'cmd', '/c', 
        'mysql -u root -proot database_ambalan < C:\\Users\\Advan\\database_ambalan.sql'
    ], capture_output=True, text=True, timeout=300)
    
    print("STDOUT:", result.stdout)
    print("STDERR:", result.stderr)
    print("Return code:", result.returncode)

if __name__ == '__main__':
    drop_all_and_import()