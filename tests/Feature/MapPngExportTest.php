<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Cetak peta PNG dan bahan ajar peta kontur.
 *
 * Modul Composables/useMapPngExport.js menggambar peta, kompas, skala, dan
 * uraian komponen peta kontur ke dalam satu kanvas, lalu mengunduhnya sebagai
 * PNG. Isi berkasnya adalah bahan ajar, jadi tidak boleh hilang diam-diam: satu
 * salah ketik pada judul komponen akan sampai ke berkas yang sudah dicetak dan
 * dibagikan ke siswa tanpa ada yang menyadarinya.
 *
 * Proyek tidak punya test runner JavaScript, jadi kontrak di sini diperiksa
 * pada sumber modulnya. Yang dijaga adalah keberadaan dan isi, bukan tata
 * letak gambar.
 */
class MapPngExportTest extends TestCase
{
    private function module(): string
    {
        return file_get_contents(base_path('resources/js/Composables/useMapPngExport.js'));
    }

    private function petaIndex(): string
    {
        return file_get_contents(base_path('resources/js/Pages/Peta/Index.vue'));
    }

    /**
     * Kedua halaman peta harus bisa mencetak PNG.
     *
     * Peta dengan Pencarian adalah halaman yang punya tombol Unduh Peta Offline
     * dengan mode per-daerah, jadi unduhan tile selalu dimulai dari sana. Dulu
     * hanya Peta/Index yang punya ekspor PNG, sehingga satu klik "Unduh Peta
     * Offline" dari halaman yang paling banyak dipakai menghasilkan paket tile
     * tanpa PNG sama sekali.
     *
     * @return array<string, string>
     */
    private function petaPages(): array
    {
        return [
            'Peta/Index' => file_get_contents(base_path('resources/js/Pages/Peta/Index.vue')),
            'Peta/MapDenganPencarian' => file_get_contents(base_path('resources/js/Pages/Peta/MapDenganPencarian.vue')),
        ];
    }

    /**
     * Komponen utama peta kontur beserta penjelasannya.
     *
     * Dipisah dari kelengkapan umum karena keduanya punya daftar berbeda;
     * digabung dalam satu assertion membuat pesan kegagalan tidak jelas
     * butir mana yang hilang.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    private function expectedComponents(): array
    {
        return [
            ['Garis Kontur', 'Garis imajiner pada peta yang menghubungkan titik-titik dengan ketinggian atau elevasi yang sama dari permukaan laut.'],
            ['Nilai Kontur', 'Angka atau label numerik yang menunjukkan besarnya elevasi (biasanya dalam satuan meter) pada suatu garis kontur.'],
            ['Interval Kontur', 'Jarak vertikal yang konstan antara dua garis kontur yang berurutan.'],
            ['Garis Kontur Indeks', 'Garis kontur yang digambar lebih tebal setiap kelipatan interval tertentu dan biasanya dilengkapi dengan angka nilai ketinggian untuk memudahkan pembacaan.'],
            ['Indikator Relief & Kenampakan Medan', 'Pola kerapatan garis yang menggambarkan bentuk-bentuk lahan, seperti lereng landai (garis renggang), lereng curam (garis rapat), lembah (membentuk huruf V menunjuk ke hulu), atau bukit/gunung (melingkar).'],
        ];
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    private function expectedCompleteness(): array
    {
        return [
            ['Judul Peta', 'Menunjukkan identitas atau nama wilayah yang dipetakan.'],
            ['Skala Peta', 'Perbandingan jarak pada peta dengan jarak sebenarnya di lapangan, yang juga menentukan besar kecilnya interval kontur.'],
            ['Arah Utara / Orientasi', 'Tanda panah yang menunjukkan arah utara geografis.'],
            ['Legenda / Keterangan', 'Penjelasan mengenai simbol-simbol lain yang ada di dalam peta.'],
            ['Grid Koordinat', 'Sistem koordinat garis bujur dan lintang atau UTM untuk penentuan posisi.'],
        ];
    }

    public function test_modul_ekspor_berkas_png(): void
    {
        $module = $this->module();

        $this->assertStringContainsString('export async function buildMapPng(', $module);
        $this->assertStringContainsString("'image/png'", $module, 'Kanvas harus dikonversi ke PNG.');
        $this->assertStringContainsString('export function triggerPngDownload(', $module);
    }

    public function test_komponen_utama_peta_kontur_lengkap(): void
    {
        $module = $this->module();

        foreach ($this->expectedComponents() as [$title, $text]) {
            $this->assertStringContainsString(
                $title,
                $module,
                "Judul komponen \"{$title}\" hilang dari bahan ajar."
            );
            $this->assertStringContainsString(
                $text,
                $module,
                "Penjelasan komponen \"{$title}\" hilang dari bahan ajar."
            );
        }
    }

    public function test_kelengkapan_umum_peta_lengkap(): void
    {
        $module = $this->module();

        foreach ($this->expectedCompleteness() as [$title, $text]) {
            $this->assertStringContainsString(
                $title,
                $module,
                "Judul kelengkapan \"{$title}\" hilang dari bahan ajar."
            );
            $this->assertStringContainsString(
                $text,
                $module,
                "Penjelasan kelengkapan \"{$title}\" hilang dari bahan ajar."
            );
        }
    }

    public function test_kompas_dan_bar_skala_ada_di_png(): void
    {
        $module = $this->module();

        // Tanpa penanda utara, peta hasil cetak tidak bisa dibaca arahnya.
        $this->assertStringContainsString('function drawCompass(', $module);
        $this->assertStringContainsString("'UTARA'", $module, 'Mata angin harus berlabel jelas.');

        foreach (['U', 'T', 'S', 'B'] as $arah) {
            $this->assertStringContainsString(
                "label: '{$arah}'",
                $module,
                "Arah mata angin {$arah} tidak digambar."
            );
        }

        $this->assertStringContainsString('function drawScaleBar(', $module);
    }

    public function test_peta_memakai_preserve_drawing_buffer(): void
    {
        // Tanpa ini kanvas WebGL bisa dibaca kosong, jadi PNG peta selalu
        // berisi teks bahan ajar tanpa gambar peta.
        foreach ($this->petaPages() as $name => $source) {
            $this->assertStringContainsString(
                'preserveDrawingBuffer: true',
                $source,
                "Halaman {$name} harus memakai preserveDrawingBuffer agar kanvasnya bisa diekspor."
            );
        }
    }

    public function test_png_diunduh_otomatis_setelah_unduh_peta_offline(): void
    {
        foreach ($this->petaPages() as $name => $source) {
            $this->assertStringContainsString(
                'Cetak Peta PNG',
                $source,
                "Halaman {$name} harus punya tombol cetak PNG."
            );

            $this->assertStringContainsString(
                'useMapPngExport',
                $source,
                "Halaman {$name} harus memakai modul ekspor PNG yang sama."
            );

            // Pemicu otomatis harus menempel pada penyelesaian unduhan tile,
            // bukan pada pembuatan modal, supaya PNG benar-benar keluar saat
            // pengguna menekan "Unduh Peta Offline".
            $this->assertMatchesRegularExpression(
                "/DOWNLOAD_COMPLETE'[\s\S]{0,600}?printMapPng\(/",
                $source,
                "PNG harus dicetak otomatis setelah unduhan tile selesai di halaman {$name}."
            );
        }
    }

    public function test_uji_baca_peta_tidak_memakai_readpixels_yang_salah(): void
    {
        $module = $this->module();

        // readPixels dengan format 0 bukan gl.RGBA. Panggilan itu tidak melempar
        // apa pun, hanya menulis "WebGL: INVALID_ENUM: readPixels: invalid
        // format" ke console, jadi hasil ujinya selalu lulus padahal tidak ada
        // yang benar-benar dicek.
        $this->assertStringNotContainsString(
            'readPixels(',
            $module,
            'Uji baca kanvas harus memakai kanvas 2D, bukan readPixels WebGL dengan format yang salah.'
        );

        $this->assertStringContainsString(
            'probeCtx.drawImage(canvas, 0, 0, 1, 1);',
            $module,
            'Cara mendeteksi kanvas peta yang di-taint CORS: salin ke kanvas 2D lewat drawImage.'
        );

        $this->assertStringContainsString(
            'probeCtx.getImageData(0, 0, 1, 1);',
            $module,
            'getImageData pada kanvas 2D hasil drawImage melempar SecurityError bila kanvas peta di-taint.'
        );
    }

    public function test_overlay_memuat_peta_tidak_bisa_menggantung(): void
    {
        foreach ($this->petaPages() as $name => $source) {
            // Event load belum selalu sampai: glyph, DEM, atau tile luar yang
            // lambat menahannya, dan gejalanya kanvas tetap bertuliskan
            // "Memuat peta..." sampai jendela dikecilkan.
            $this->assertStringContainsString(
                "map.value.on('styledata', clearLoading);",
                $source,
                "Halaman {$name} harus melepas overlay saat style selesai dimuat."
            );

            $this->assertStringContainsString(
                "map.value.on('idle', clearLoading);",
                $source,
                "Halaman {$name} harus melepas overlay saat peta idle."
            );

            $this->assertMatchesRegularExpression(
                '/setTimeout\(\(\) => \{\s*if \(loading\.value\)/',
                $source,
                "Halaman {$name} perlu batas waktu supaya overlay tidak menggantung selamanya."
            );
        }
    }
}
