import mysql.connector
import csv
import os

def export_tables_to_csv():
    # MySQL connection
    conn = mysql.connector.connect(
        host='127.0.0.1',
        port=3306,
        user='root',
        password='root',
        database='database_ambalan'
    )
    cursor = conn.cursor()
    
    # Get all table names
    cursor.execute("SHOW TABLES")
    tables = [row[0] for row in cursor.fetchall()]
    
    output_dir = "C:/Kuliah Jaya Jaya Jaya/SKRIPSI GWEH/Project PWA/csv_export"
    os.makedirs(output_dir, exist_ok=True)
    
    for table in tables:
        print(f"Exporting {table}...")
        try:
            cursor.execute(f"SELECT * FROM `{table}`")
            rows = cursor.fetchall()
            
            if rows:
                # Get column names
                columns = [desc[0] for desc in cursor.description]
                
                # Write to CSV
                csv_file = os.path.join(output_dir, f"{table}.csv")
                with open(csv_file, 'w', newline='', encoding='utf-8') as f:
                    writer = csv.writer(f)
                    writer.writerow(columns)  # Header
                    writer.writerows(rows)
                
                print(f"  -> {len(rows)} rows exported to {csv_file}")
            else:
                print(f"  -> {table} is empty")
                
        except Exception as e:
            print(f"  ERROR exporting {table}: {e}")
    
    cursor.close()
    conn.close()
    print(f"\nAll tables exported to {output_dir}")

if __name__ == '__main__':
    export_tables_to_csv()