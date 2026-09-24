<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Laporan Keuangan</title></head>
<body style="font-family: sans-serif; padding: 20px; color: #333;">
<h1 style="text-align:center; border-bottom: 3px solid #6F9435; padding-bottom: 10px;">Laporan Keuangan Ambalan</h1>
<p style="text-align:center; color: #8fa06a;">Periode: {{ now()->translatedFormat('F Y') }}</p>
<div style="display: flex; justify-content: space-around; margin: 20px 0;">
<div style="text-align:center;"><h3 style="color: #A7B92B;">Saldo Kas</h3><p style="font-size: 24px; font-weight: bold;">Rp {{ number_format($balance, 0, ',', '.') }}</p></div>
<div style="text-align:center;"><h3 style="color: #A7B92B;">Pemasukan</h3><p style="font-size: 24px; font-weight: bold;">Rp {{ number_format($income, 0, ',', '.') }}</p></div>
<div style="text-align:center;"><h3 style="color: #ef4419;">Pengeluaran</h3><p style="font-size: 24px; font-weight: bold;">Rp {{ number_format($expense, 0, ',', '.') }}</p></div>
</div>
<table style="width:100%; border-collapse: collapse; margin-top: 20px;">
<thead><tr style="background:#335233; color: #f0ead8;"><th style="padding:8px; border:1px solid #6F9435;">Tanggal</th><th style="padding:8px; border:1px solid #6F9435;">Keterangan</th><th style="padding:8px; border:1px solid #6F9435;">Jenis</th><th style="padding:8px; border:1px solid #6F9435;">Nominal</th><th style="padding:8px; border:1px solid #6F9435;">Anggota</th></tr></thead>
<tbody>
@foreach($finances as $f)
<tr><td style="padding:6px; border:1px solid #ddd;">{{ $f->tgl_transaksi }}</td><td style="padding:6px; border:1px solid #ddd;">{{ $f->keterangan }}</td><td style="padding:6px; border:1px solid #ddd;">{{ $f->jenis_transaksi }}</td><td style="padding:6px; border:1px solid #ddd; text-align:right;">Rp {{ number_format($f->nominal, 0, ',', '.') }}</td><td style="padding:6px; border:1px solid #ddd;">{{ $f->member?->nama_lengkap }}</td></tr>
@endforeach
</tbody>
</table>
</body>
</html>
