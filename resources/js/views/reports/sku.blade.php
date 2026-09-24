<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Rekap SKU</title></head>
<body style="font-family: sans-serif; padding: 20px; color: #333;">
<h1 style="text-align:center; border-bottom: 3px solid #6F9435; padding-bottom: 10px;">Rekap SKU Anggota</h1>
<table style="width:100%; border-collapse: collapse; margin-top: 20px;">
<thead><tr style="background:#335233; color: #f0ead8;"><th style="padding:8px; border:1px solid #6F9435;">No</th><th style="padding:8px; border:1px solid #6F9435;">Nama</th><th style="padding:8px; border:1px solid #6F9435;">Tingkatan</th><th style="padding:8px; border:1px solid #6F9435;">SKU Disetujui</th><th style="padding:8px; border:1px solid #6F9435;">SKU Pending</th><th style="padding:8px; border:1px solid #6F9435;">SKU Ditolak</th></tr></thead>
<tbody>
@foreach($members as $idx => $m)
<tr>
<td style="padding:6px; border:1px solid #ddd;">{{ $idx+1 }}</td>
<td style="padding:6px; border:1px solid #ddd;">{{ $m->nama_lengkap }}</td>
<td style="padding:6px; border:1px solid #ddd;">{{ $m->tingkatan }}</td>
<td style="padding:6px; border:1px solid #ddd;">{{ $m->skuSubmissions->where('status', 'Approved')->count() }}</td>
<td style="padding:6px; border:1px solid #ddd;">{{ $m->skuSubmissions->where('status', 'Pending')->count() }}</td>
<td style="padding:6px; border:1px solid #ddd;">{{ $m->skuSubmissions->where('status', 'Rejected')->count() }}</td>
</tr>
@endforeach
</tbody>
</table>
</body>
</html>
