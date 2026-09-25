<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Keuangan</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        h1 { font-size: 16px; margin-bottom: 5px; }
        h2 { font-size: 14px; margin-top: 15px; margin-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 4px 6px; text-align: left; }
        th { background-color: #f0f0f0; }
        .summary { margin: 10px 0; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h1>LAPORAN KEUANGAN</h1>
    <p>Dicetak pada: {{ now()->format('d/m/Y H:i') }}</p>

    <div class="summary">
        <p><strong>Total Pemasukan:</strong> Rp {{ number_format($income, 0, ',', '.') }}</p>
        <p><strong>Total Pengeluaran:</strong> Rp {{ number_format($expense, 0, ',', '.') }}</p>
        <p><strong>Saldo Akhir:</strong> Rp {{ number_format($balance, 0, ',', '.') }}</p>
    </div>

    <h2>Transaksi</h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Tanggal</th>
                <th>Jenis</th>
                <th>Anggota</th>
                <th>Kategori</th>
                <th>Keterangan</th>
                <th class="text-right">Nominal</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($finances as $finance)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ \Carbon\Carbon::parse($finance->tgl_transaksi)->format('d/m/Y') }}</td>
                    <td>{{ $finance->jenis_transaksi }}</td>
                    <td>{{ $finance->member?->nama_lengkap ?? '-' }}</td>
                    <td>{{ $finance->category?->nama ?? '-' }}</td>
                    <td>{{ $finance->keterangan }}</td>
                    <td class="text-right">Rp {{ number_format($finance->nominal, 0, ',', '.') }}</td>
                    <td>{{ $finance->status }}</td>
                </tr>
            @empty
                <tr><td colspan="8">Tidak ada data transaksi.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
