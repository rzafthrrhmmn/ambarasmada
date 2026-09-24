<?php

namespace Database\Seeders;

use App\Models\Ambalan;
use App\Models\Angkatan;
use App\Models\Announcement;
use App\Models\AttendanceSession;
use App\Models\FinanceCategory;
use App\Models\FinancePeriod;
use App\Models\Inventory;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (range(2009, 2026) as $year) {
            $nomor = str_pad((string) ($year - 2008), 3, '0', STR_PAD_LEFT);
            Angkatan::updateOrCreate(
                ['angkatan' => (string) $year],
                [
                    'nomor' => $nomor,
                    'nama' => "Angkatan {$year}",
                    'is_active' => $year === 2026,
                    'is_current' => $year === 2026,
                ]
            );
        }

        $this->call(DemoUsersSeeder::class);
        $this->call(LearningMaterialSeeder::class);
        $this->call(PengurusPositionSeeder::class);
        $this->call(SkuPenegakPointSeeder::class);
        $this->call(TkkWajibPenegakSeeder::class);

        $ambalan = Ambalan::updateOrCreate(
            ['kode' => 'SMAN2MAROS'],
            ['nama' => 'Ambalan UPT SMAN 2 Maros', 'alamat' => 'Maros, Sulawesi Selatan', 'status' => 'Aktif']
        );

        FinanceCategory::updateOrCreate(['nama' => 'Iuran Wajib'], [
            'ambalan_id' => $ambalan->id,
            'jenis' => 'Masuk',
            'is_active' => true,
        ]);
        FinanceCategory::updateOrCreate(['nama' => 'Operasional Latihan'], [
            'ambalan_id' => $ambalan->id,
            'jenis' => 'Keluar',
            'is_active' => true,
        ]);
        FinancePeriod::updateOrCreate(['ambalan_id' => $ambalan->id, 'nama' => 'Tahun 2026'], [
            'starts_at' => '2026-01-01',
            'ends_at' => '2026-12-31',
            'is_closed' => false,
        ]);

        AttendanceSession::firstOrCreate(['qr_token' => 'LATIHAN001'], [
            'ambalan_id' => $ambalan->id,
            'nama' => 'Latihan Rutin Perdana',
            'tanggal' => now()->toDateString(),
            'lokasi' => 'Halaman SMAN 2 Maros',
            'created_by' => 1,
        ]);

        Inventory::updateOrCreate(['kode_barang' => 'TENDA-001'], [
            'ambalan_id' => $ambalan->id,
            'nama_barang' => 'Tenda Regu 4x4',
            'jenis' => 'Aset',
            'satuan' => 'Unit',
            'jumlah' => 4,
            'kondisi' => 'Baik',
            'status_pinjam' => 'Tersedia',
        ]);

        Announcement::updateOrCreate(['judul' => 'Selamat datang di Ekosistem Digital Ambalan'], [
            'ambalan_id' => $ambalan->id,
            'isi' => 'Sistem ini digunakan untuk mengelola anggota, SKU, presensi, keuangan, inventaris, dan kegiatan alumni.',
            'published_at' => now(),
            'created_by' => 1,
        ]);
    }
}
