<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap SKU</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        h1 { font-size: 16px; margin-bottom: 5px; }
        h2 { font-size: 14px; margin-top: 15px; margin-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 4px 6px; text-align: left; }
        th { background-color: #f0f0f0; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <h1>REKAP SISTEM KEHADIRAN UJIAN (SKU)</h1>
    <p>Dicetak pada: {{ now()->format('d/m/Y H:i') }}</p>

    <h2>Anggota</h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Nama Lengkap</th>
                <th>NTA</th>
                <th>Poin SKU</th>
                <th>Jumlah Kehadiran</th>
                <th>Jumlah Tidak Hadir</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($members as $member)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $member->nama_lengkap ?? '-' }}</td>
                    <td>{{ $member->nta ?? '-' }}</td>
                    <td class="text-center">{{ $member->skuSubmissions->sum(fn($s) => $s->sku_point->sum('point')) }}</td>
                    <td class="text-center">{{ $member->skuSubmissions->where('status', 'Hadir')->count() }}</td>
                    <td class="text-center">{{ $member->skuSubmissions->where('status', '!=', 'Hadir')->count() }}</td>
                </tr>
            @empty
                <tr><td colspan="6">Tidak ada data anggota.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
