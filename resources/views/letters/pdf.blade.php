<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat - {{ $letter->perihal }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=DejaVu+Sans:wght@400;600;700&display=swap');
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12pt;
            color: #263D26;
            background: #fff;
            padding: 40px 60px;
            line-height: 1.6;
        }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { font-size: 18pt; color: #263D26; margin-bottom: 4px; }
        .header p { font-size: 11pt; color: #6F9435; font-style: italic; }
        .header .divider { width: 100px; height: 3px; background: #A7B92A; margin: 10px auto; border-radius: 2px; }
        .details { margin-bottom: 25px; }
        .details p { margin-bottom: 6px; font-size: 11pt; }
        .details strong { color: #6F9435; }
        .content { margin-bottom: 30px; }
        .content h2 { font-size: 13pt; color: #263D26; margin-bottom: 10px; }
        .content p { text-align: justify; font-size: 11pt; }
        .footer { margin-top: 50px; text-align: right; font-size: 11pt; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $ambalan->nama ?? 'Ambalan UPT SMAN 2 Maros' }}</h1>
        <p>Persuratan Digital</p>
        <div class="divider"></div>
    </div>

    <div class="details">
        <p><strong>Nomor Surat:</strong> {{ $letter->nomor_surat ?? '-' }}</p>
        <p><strong>Jenis:</strong> {{ $letter->jenis_surat }}</p>
        <p><strong>Perihal:</strong> {{ $letter->perihal }}</p>
        <p><strong>Tanggal:</strong> {{ $letter->tgl_surat ? \Carbon\Carbon::parse($letter->tgl_surat)->translatedFormat('d F Y') : '-' }}</p>
        @if($letter->waktu_kegiatan)
            <p><strong>Waktu Kegiatan:</strong> {{ $letter->waktu_kegiatan }}</p>
        @endif
        @if($letter->lokasi_kegiatan)
            <p><strong>Lokasi Kegiatan:</strong> {{ $letter->lokasi_kegiatan }}</p>
        @endif
        <p><strong>Tujuan/Pengirim:</strong> {{ $letter->tujuan_pengirim ?? '-' }}</p>
        <p><strong>Pradana Putra:</strong> {{ $nama_pradana_putra ?? '-' }}</p>
        <p><strong>NIS Pradana Putra:</strong> {{ $nis_pradana_putra ?? '-' }}</p>
        <p><strong>Pradana Putri:</strong> {{ $nama_pradana_putri ?? '-' }}</p>
        <p><strong>NIS Pradana Putri:</strong> {{ $nis_pradana_putri ?? '-' }}</p>
    </div>

    <div class="content">
        <h2>Isi Surat</h2>
        <p>{{ $letter->isi_surat }}</p>
    </div>

    <div class="footer">
        <p>Dikeluarkan pada: {{ now()->translatedFormat('d F Y') }}</p>
    </div>
</body>
</html>