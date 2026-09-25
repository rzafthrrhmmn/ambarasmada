<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Sertifikat - {{ $certificate->nomor_sertifikat }}</title>
    <style>
        @page {
            margin: 2cm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 0;
        }
        .certificate {
            border: 3px solid #6F9435;
            padding: 40px;
            text-align: center;
            position: relative;
            min-height: 250mm;
        }
        .header {
            margin-bottom: 30px;
        }
        .logo {
            width: 120px;
            height: 120px;
            margin: 0 auto 20px;
            background-color: #f0f0f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .title {
            font-size: 28px;
            font-weight: bold;
            color: #6F9435;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .subtitle {
            font-size: 18px;
            color: #333;
            margin-bottom: 30px;
        }
        .certificate-number {
            font-size: 14px;
            color: #666;
            margin-bottom: 40px;
        }
        .recipient {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin: 30px 0;
            text-transform: uppercase;
        }
        .description {
            font-size: 16px;
            color: #555;
            line-height: 1.8;
            margin: 30px 0;
        }
        .footer {
            position: absolute;
            bottom: 40px;
            left: 40px;
            right: 40px;
            display: flex;
            justify-content: space-between;
        }
        .signature-block {
            text-align: center;
            width: 200px;
        }
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 60px;
            padding-top: 10px;
            font-weight: bold;
        }
        .signature-title {
            font-size: 12px;
            color: #666;
        }
        .date-location {
            font-size: 14px;
            color: #666;
        }
        .seal {
            position: absolute;
            bottom: 60px;
            right: 60px;
            width: 100px;
            height: 100px;
            border: 2px solid #6F9435;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #6F9435;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="certificate">
        <div class="header">
            <div class="logo">
                <svg width="80" height="80" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="50" cy="50" r="45" stroke="#6F9435" stroke-width="3"/>
                    <text x="50" y="55" font-family="DejaVu Sans" font-size="12" font-weight="bold" fill="#6F9435" text-anchor="middle">AMBALAN</text>
                    <text x="50" y="70" font-family="DejaVu Sans" font-size="10" fill="#6F9435" text-anchor="middle">SMAN 2 MAROS</text>
                </svg>
            </div>
            <div class="title">Sertifikat</div>
            <div class="subtitle">{{ $certificate->judul }}</div>
            <div class="certificate-number">Nomor: {{ $certificate->nomor_sertifikat }}</div>
        </div>

        <div class="recipient">{{ $certificate->member->nama_lengkap }}</div>

        <div class="description">
            {{ $certificate->deskripsi ?? 'Telah menyelesaikan kegiatan dengan baik dan mendapat pengakuan atas prestasi yang dicapai.' }}
        </div>

        <div class="description">
            Sertifikat ini diberikan pada tanggal {{ \Carbon\Carbon::parse($certificate->tanggal_diterbitkan)->translatedFormat('d F Y') }} di {{ $certificate->ambalan->alamat ?? 'Maros, Sulawesi Selatan' }}.
        </div>

        <div class="footer">
            <div class="signature-block">
                <div class="signature-line">{{ $certificate->issuedBy->name ?? 'Pembina' }}</div>
                <div class="signature-title">Pembina</div>
            </div>
            <div class="date-location">
                {{ $certificate->ambalan->nama ?? 'Ambalan UPT SMAN 2 Maros' }}, {{ \Carbon\Carbon::parse($certificate->tanggal_diterbitkan)->translatedFormat('d F Y') }}
            </div>
            <div class="signature-block">
                <div class="signature-line">{{ $certificate->member->nama_lengkap }}</div>
                <div class="signature-title">Penerima</div>
            </div>
        </div>

        <div class="seal">
            SEGEL<br>AMBALAN
        </div>
    </div>
</body>
</html>