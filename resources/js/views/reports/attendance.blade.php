<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Rekap Kehadiran</title></head>
<body style="font-family: sans-serif; padding: 20px; color: #333;">
<h1 style="text-align:center; border-bottom: 3px solid #6F9435; padding-bottom: 10px;">Rekap Kehadiran Ambalan</h1>
<p style="text-align:center; color: #8fa06a;">Periode: {{ now()->translatedFormat('F Y') }}</p>
@foreach($sessions as $session)
<div style="margin-bottom: 20px; padding: 15px; border: 2px solid #6F9435; border-radius: 8px;">
<h3 style="color: #f0ead8; background: #335233; padding: 8px; border-radius: 4px;">{{ $session->nama }}</h3>
<p>Tanggal: {{ $session->tanggal }} | Lokasi: {{ $session->lokasi ?? '-' }} | Presensi: {{ $session->attendances_count }}</p>
@if($session->attendances->count())
<table style="width:100%; border-collapse: collapse; margin-top: 8px;">
<thead><tr style="background:#263D26; color: #f0ead8;"><th style="padding:4px; border:1px solid #6F9435;">No</th><th style="padding:4px; border:1px solid #6F9435;">Nama</th><th style="padding:4px; border:1px solid #6F9435;">Status</th></tr></thead>
<tbody>
@foreach($session->attendances as $idx => $a)
<tr><td style="padding:4px; border:1px solid #ddd;">{{ $idx+1 }}</td><td style="padding:4px; border:1px solid #ddd;">{{ $a->member?->nama_lengkap }}</td><td style="padding:4px; border:1px solid #ddd;">{{ $a->keterangan }}</td></tr>
@endforeach
</tbody>
</table>
@endif
</div>
@endforeach
</body>
</html>
