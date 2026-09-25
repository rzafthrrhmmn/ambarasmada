import re

with open('C:/Users/Advan/database_ambalan.sql', 'r', encoding='utf-8') as f:
    content = f.read()

tables = re.findall(r'CREATE TABLE `(\w+)`', content)
print('Tables in SQL dump:', len(tables))
for t in tables:
    print('  ' + t)