with open('database/seeders/DatabaseSeeder.php', 'rb') as f:
    for i, line in enumerate(f.read().split(b'\n'), 1):
        if b'xAClass' in line or b' barang' in line or b' tanggal' in line:
            print(i, line.hex())