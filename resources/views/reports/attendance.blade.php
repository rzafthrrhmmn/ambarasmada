<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Absensi</title>
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
    <h1>REKAP PRESENSI KEHADIRAN</h1>
    <p>Dicetak pada: {{ now()->format('d/m/Y H:i') }}</p>

    <h2>Ringkasan Sesi</h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Tanggal</th>
                <th>Kegiatan</th>
                <th>Total Hadir</th>
                <th>Total Tidak Hadir</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($sessions as $session)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ \Carbon\Carbon::parse($session->tanggal)->format('d/m/Y') }}</td>
                    <td>{{ $session->nama_kegiatan ?? '-' }}</td>
                    <td class="text-center">{{ $session->attendances->where('status', 'Hadir')->count() }}</td>
                    <td class="text-center">{{ $session->attendances->where('status', '!=', 'Hadir')->count() }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Tidak ada data sesi kehadiran.</td></tr>
            @endforelse
        </tbody>
    </table>

    @foreach ($sessions as $session)
        @if ($session->attendances->isNotEmpty())
            <h2>Detail: {{ $session->nama_kegiatan ?? 'Sesi ' . $session->id }} ({{ \Carbon\Carbon::parse($session->tanggal)->format('d/m/Y') }})</h2>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nama Lengkap</th>
                        <th>NTA</th>
                        <th>Status Kehadiran</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($session->attendances as $attendance)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $attendance->member?->nama_lengkap ?? '-' }}</td>
                            <td>{{ $attendance->member?->nta ?? '-' }}</td>
                            <td>{{ $attendance->status }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endforeach
</body>
</html>
