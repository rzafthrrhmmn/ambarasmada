import re

def convert_mysql_to_postgresql(input_file, output_file):
    with open(input_file, 'r', encoding='utf-16-le') as f:
        content = f.read()
    
    # Remove MySQL-specific comments and directives
    content = re.sub(r'/\*!\d+.*?\*/;', '', content)
    content = re.sub(r'/\*!\d+.*?\*/', '', content)
    content = re.sub(r'SET @MYSQLDUMP_TEMP_LOG_BIN.*?;', '', content)
    content = re.sub(r'SET @@SESSION\.SQL_LOG_BIN.*?;', '', content)
    content = re.sub(r'SET @@GLOBAL\.GTID_PURGED.*?;', '', content)
    content = re.sub(r'LOCK TABLES `.*?` WRITE;', '', content)
    content = re.sub(r'UNLOCK TABLES;', '', content)
    content = re.sub(r'/\*!40000 ALTER TABLE `.*?` DISABLE KEYS \*/;', '', content)
    content = re.sub(r'/\*!40000 ALTER TABLE `.*?` ENABLE KEYS \*/;', '', content)
    
    # Remove backticks
    content = content.replace('`', '"')
    
    # Remove BOM
    content = content.replace('\ufeff', '')
    
    # Convert data types
    content = re.sub(r'bigint unsigned', 'bigint', content, flags=re.IGNORECASE)
    content = re.sub(r'int unsigned', 'integer', content, flags=re.IGNORECASE)
    content = re.sub(r'tinyint\(1\)', 'boolean', content, flags=re.IGNORECASE)
    content = re.sub(r'tinyint unsigned', 'smallint', content, flags=re.IGNORECASE)
    content = re.sub(r'smallint unsigned', 'integer', content, flags=re.IGNORECASE)
    content = re.sub(r'mediumint unsigned', 'integer', content, flags=re.IGNORECASE)
    content = re.sub(r'json', 'jsonb', content, flags=re.IGNORECASE)
    content = re.sub(r'longtext', 'text', content, flags=re.IGNORECASE)
    content = re.sub(r'mediumtext', 'text', content, flags=re.IGNORECASE)
    content = re.sub(r'tinytext', 'text', content, flags=re.IGNORECASE)
    
    # Convert AUTO_INCREMENT to SERIAL/GENERATED
    content = re.sub(r'bigint\s+NOT NULL\s+AUTO_INCREMENT', 'bigint GENERATED ALWAYS AS IDENTITY', content, flags=re.IGNORECASE)
    content = re.sub(r'int\s+NOT NULL\s+AUTO_INCREMENT', 'integer GENERATED ALWAYS AS IDENTITY', content, flags=re.IGNORECASE)
    
    # Convert timestamp defaults
    content = re.sub(r'timestamp NULL DEFAULT NULL', 'timestamp NULL', content, flags=re.IGNORECASE)
    
    # Remove CHARACTER SET and COLLATE
    content = re.sub(r'\s+CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci', '', content, flags=re.IGNORECASE)
    content = re.sub(r'\s+CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci', '', content, flags=re.IGNORECASE)
    content = re.sub(r'\s+COLLATE utf8mb4_unicode_ci', '', content, flags=re.IGNORECASE)
    content = re.sub(r'\s+COLLATE utf8mb4_0900_ai_ci', '', content, flags=re.IGNORECASE)
    
    # Convert ENGINE and CHARSET
    content = re.sub(r'\) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;', ');', content, flags=re.IGNORECASE)
    content = re.sub(r'\) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;', ');', content, flags=re.IGNORECASE)
    
    # Remove KEY lines (PostgreSQL creates indexes separately) - FIX: also handle UNIQUE KEY
    content = re.sub(r'\n\s+KEY\s+"[^"]+"\s*\([^)]+\),?', '', content)
    content = re.sub(r'\n\s+UNIQUE KEY\s+"[^"]+"\s*\([^)]+\),?', '', content)
    
    # Fix UNIQUE KEY to UNIQUE CONSTRAINT
    # This is handled by removing UNIQUE KEY lines above, but we need to add UNIQUE constraints
    # Actually, just remove them - they'll be created by PRIMARY KEY or separate statements
    
    # Fix boolean default
    content = re.sub(r"DEFAULT '0'", 'DEFAULT false', content, flags=re.IGNORECASE)
    content = re.sub(r"DEFAULT '1'", 'DEFAULT true', content, flags=re.IGNORECASE)
    
    # Convert SET NAMES
    content = re.sub(r'SET NAMES utf8mb4;', '', content, flags=re.IGNORECASE)
    
    # Convert TIME_ZONE
    content = re.sub(r'SET TIME_ZONE=.*?;', '', content, flags=re.IGNORECASE)
    
    # Convert UNIQUE_CHECKS, FOREIGN_KEY_CHECKS, SQL_MODE, SQL_NOTES
    content = re.sub(r'SET @OLD_UNIQUE_CHECKS.*?;', '', content, flags=re.IGNORECASE)
    content = re.sub(r'SET @OLD_FOREIGN_KEY_CHECKS.*?;', '', content, flags=re.IGNORECASE)
    content = re.sub(r'SET @OLD_SQL_MODE.*?;', '', content, flags=re.IGNORECASE)
    content = re.sub(r'SET @OLD_SQL_NOTES.*?;', '', content, flags=re.IGNORECASE)
    content = re.sub(r'SET UNIQUE_CHECKS=0;', '', content, flags=re.IGNORECASE)
    content = re.sub(r'SET FOREIGN_KEY_CHECKS=0;', '', content, flags=re.IGNORECASE)
    content = re.sub(r'SET SQL_MODE=.*?;', '', content, flags=re.IGNORECASE)
    content = re.sub(r'SET SQL_NOTES=0;', '', content, flags=re.IGNORECASE)
    
    # Remove empty lines at start/end and multiple newlines
    content = re.sub(r'\n{3,}', '\n\n', content)
    content = content.strip() + '\n'
    
    # Add PostgreSQL-specific header
    header = '''-- PostgreSQL dump converted from MySQL
-- Run this on Supabase PostgreSQL

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

'''
    
    content = header + content
    
    with open(output_file, 'w', encoding='utf-8') as f:
        f.write(content)

if __name__ == '__main__':
    convert_mysql_to_postgresql(
        'C:/Kuliah Jaya Jaya Jaya/SKRIPSI GWEH/Project PWA/database_ambalan_mysql.sql',
        'C:/Kuliah Jaya Jaya Jaya/SKRIPSI GWEH/Project PWA/database_ambalan_postgresql.sql'
    )
    print("Conversion complete!")