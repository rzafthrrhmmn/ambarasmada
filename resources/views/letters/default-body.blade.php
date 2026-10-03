{{-- Tata letak bawaan untuk surat yang tidak memakai templat .docx. --}}
@php
    $base = app(App\Support\Letters\LetterValues::class)->base($letter);
    $rows = array_filter([
        'Nomor Surat' => $base['nomor_surat'],
        'Jenis Surat' => $base['jenis_surat'],
        'Perihal' => $base['perihal'],
        'Tanggal Surat' => $base['tgl_surat'],
        'Waktu Kegiatan' => $base['waktu_kegiatan'],
        'Lokasi Kegiatan' => $base['lokasi_kegiatan'],
        'Tujuan / Pengirim' => $base['tujuan_pengirim'],
        'Ambalan' => $base['nama_ambalan'],
    ]);
@endphp

<div class="kop">
    <p class="lembaga">{{ $ambalan->nama ?? 'Ambalan UPT SMAN 2 Maros' }}</p>
    <p class="sub">Ambalan Pramuka &mdash; {{ $base['jenis_surat'] }}</p>
</div>
<hr class="garis">

<table>
    @foreach ($rows as $label => $value)
        <tr>
            <td class="label">{{ $label }}</td>
            <td>{{ $value }}</td>
        </tr>
    @endforeach
    @foreach ($extra as $key => $value)
        @if (is_string($value) && trim($value) !== '')
            <tr>
                <td class="label">{{ ucwords(str_replace(['_', '-'], ' ', $key)) }}</td>
                <td>{{ $value }}</td>
            </tr>
        @endif
    @endforeach
</table>

<div class="isi">
    @foreach (preg_split('/\r\n|\r|\n/', (string) $letter->isi_surat) as $paragraph)
        @if (trim($paragraph) !== '')
            <p>{{ $paragraph }}</p>
        @endif
    @endforeach
</div>

<div class="ttd">
    <p>{{ $base['lokasi_kegiatan'] !== '-' ? $base['lokasi_kegiatan'] : 'Maros' }}, {{ $base['tanggal'] }}</p>
    <p class="jabatan">{{ $extra['jabatan_pengirim'] ?? 'Pradana Putra' }}</p>
    <p class="nama">{{ $extra['nama_pengirim'] ?? ($extra['nama_pradana_putra'] ?? '-') }}</p>
    @if (! empty($extra['nip_pengirim']))
        <p class="jabatan">NIP. {{ $extra['nip_pengirim'] }}</p>
    @endif
</div>
