<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        /* Hanya font bawaan dompdf (DejaVu) supaya pratinjau dan PDF
           menghasilkan tata letak yang sama persis. */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #1f2b1f;
            background: #fff;
            padding: 32px 40px;
        }
        p { margin: 0 0 2px; }
        p.kosong { margin: 0 0 10px; }
        .tab { display: inline-block; width: 2.2em; }
        table { width: 100%; border-collapse: collapse; margin: 0 0 14px; table-layout: fixed; }
        td { vertical-align: top; padding: 2px 6px 2px 0; }
        table.tabel-bergaris td { border: 1px solid #9aa08c; padding: 4px 6px; }
        table.tabel-polos td { border: none; }
        td.label { width: 190px; color: #4c6b2a; }
        hr.garis { border: none; border-top: 1.5px solid #6f9435; margin: 10px 0 18px; }
        .kop { text-align: center; margin-bottom: 18px; }
        .kop .lembaga { font-size: 12pt; font-weight: bold; letter-spacing: 0.5px; }
        .kop .sub { font-size: 10pt; color: #4c6b2a; }
        .isi { margin-top: 12px; text-align: justify; }
        .isi p { margin: 0 0 8px; }
        .ttd { margin-top: 34px; text-align: right; }
        .ttd .nama { margin-top: 58px; font-weight: bold; text-decoration: underline; }
        .ttd .jabatan { font-size: 10pt; }
        .catatan {
            margin-bottom: 14px;
            padding: 8px 10px;
            border: 1px solid #c0392b;
            color: #c0392b;
            font-size: 9.5pt;
        }
    </style>
</head>
<body>
@if (! empty($unfilled))
    <p class="catatan">Penanda template belum diisi: {{ implode(', ', $unfilled) }}</p>
@endif
{!! $body !!}
</body>
</html>
