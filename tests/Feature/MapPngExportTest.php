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

    public function test_cetak_png_tergabung_di_tombol_unduh_peta_offline(): void
    {
        foreach ($this->petaPages() as $name => $source) {
            $this->assertStringNotContainsString(
                '>Cetak Peta PNG<',
                $source,
                "Halaman {$name} tidak lagi punya tombol cetak PNG terpisah; sudah menyatu dengan Unduh Peta Offline."
            );

            $this->assertStringContainsString(
                'useMapPngExport',
                $source,
                "Halaman {$name} harus memakai modul ekspor PNG yang sama."
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

    public function test_overlay_memuat_peta_tidak_menggantung_dan_tidak_jadi_terlalu_awal(): void
    {
        foreach ($this->petaPages() as $name => $source) {
            // Melepas overlay dari `styledata` keliru: event itu fire paling awal,
            // hanya setelah JSON style terurai. Overlay hilang seketika lalu garis
            // kontur terlihat macet selama tile-nya masih turun.
            $this->assertStringNotContainsString(
                "map.value.on('styledata', clearLoading);",
                $source,
                "Halaman {$name} tidak boleh melepas penanda loading dari styledata; overlay lalu hilang sebelum kontur masuk."
            );

            $this->assertStringContainsString(
                "map.value.on('idle', clearLoading);",
                $source,
                "Halaman {$name} harus melepas penanda loading setelah peta selesai menggambar."
            );

            $this->assertMatchesRegularExpression(
                '/setTimeout\(\(\) => \{\s*if \(loading\.value\)/',
                $source,
                "Halaman {$name} perlu batas waktu supaya penanda loading tidak menggantung selamanya."
            );
        }
    }

    public function test_cetak_png_gabung_dengan_unduhan_peta_offline(): void
    {
        foreach ($this->petaPages() as $name => $source) {
            // Tidak ada lagi tombol cetak terpisah: cetak PNG adalah hasil akhir
            // dari unduhan peta offline, bukan fitur lain yang berdiri sendiri.
            $this->assertStringNotContainsString(
                '>Cetak Peta PNG<',
                $source,
                "Halaman {$name} tidak perlu tombol cetak PNG terpisah; sudah menyatu dengan Unduh Peta Offline."
            );

            $this->assertStringContainsString(
                'useMapPngExport',
                $source,
                "Halaman {$name} harus memakai modul ekspor PNG yang sama."
            );
        }

        $pencarian = file_get_contents(base_path('resources/js/Pages/Peta/MapDenganPencarian.vue'));

        // Urutan menentukan isi berkas: snapshot PNG diambil dari kanvas peta
        // yang sedang tampil, jadi cetaknya harus sebelum paket tile diminta ke
        // service worker. Kalau tidak, berkas berisi viewport terakhir.
        $printAt = strpos($pencarian, 'const png = await printMapPng({');
        $requestAt = strpos($pencarian, "type: 'DOWNLOAD_OFFLINE_TILES'");

        $this->assertIsInt($printAt, 'Halaman pencarian harus mencetak PNG dari downloadOffline.');
        $this->assertIsInt($requestAt, 'Halaman pencarian harus meminta paket tile ke service worker.');
        $this->assertLessThan(
            $requestAt,
            $printAt,
            'PNG harus dicetak sebelum paket tile diminta; kalau dibalik, berkas berisi viewport terakhir.'
        );

        $this->assertMatchesRegularExpression(
            "/jumpTo\(\{[\s\S]{0,400}?whenMapIdle\(\)[\s\S]{0,400}?printMapPng\(\{/",
            $pencarian,
            'Peta harus digeser ke wilayah yang dipilih sebelum PNG dicetak.'
        );

        $this->assertStringContainsString(
            'components,',
            $pencarian,
            'Pilihan "Komponen Peta Offline" harus diteruskan ke PNG supaya kedua berkas sama.'
        );
    }

    public function test_komponen_peta_yang_dicentang_benar_benar_digambar(): void
    {
        $module = $this->module();

        foreach (
            [
                'picked.grid' => 'drawGraticule(ctx,',
                'picked.legend' => 'drawLegend(ctx,',
                'picked.histogram' => 'drawHistogram(ctx,',
                'picked.northArrow' => 'drawCompass(ctx,',
                'picked.scaleBar' => 'drawScaleBar(ctx,',
            ] as $gate => $call
        ) {
            $this->assertStringContainsString(
                $gate,
                $module,
                "Komponen {$gate} tidak lagi dikendalikan pilihan pengguna."
            );
            $this->assertStringContainsString(
                $call,
                $module,
                "Fungsi gambar untuk {$gate} tidak ada di modul."
            );
        }

        $this->assertStringContainsString(
            'export const DEFAULT_COMPONENTS = {',
            $module,
            'Harus ada default komponen supaya halaman tanpa dialog tetap bisa mencetak PNG.'
        );

        // Histogram harus menolak menggambar angka karangan.
        $this->assertStringNotContainsString(
            'Math.random()',
            $module,
            'Modul tidak boleh membuat angka elevasi acak: angka karangan tercetak sebagai data elevasi sungguhan.'
        );
    }

    public function test_peta_offline_lama_dibuang_karena_isinya_sudah_usang(): void
    {
        $sw = file_get_contents(base_path('public/sw.js'));
        $this->assertIsString($sw);

        // Cache OFFLINE_HTML_CACHE sengaja tidak ikut CACHE_VERSION supaya peta
        // offline milik pengguna tidak hilang tiap pembaruan. Kalau tidak ada
        // pengecualian, peta yang sudah diunduh akan tetap menampilkan angka
        // elevasi karangan selamanya, karena tidak ada yang pernah memperbaruinya.
        $this->assertStringContainsString(
            'dropStaleOfflineMap()',
            $sw,
            'Activate harus memeriksa peta offline hasil unduhan versi lama.'
        );

        $this->assertStringContainsString(
            '<meta name="offline-map-generator" content="${OFFLINE_MAP_GENERATOR}">',
            $sw,
            'Halaman peta offline harus mencatat versi generatornya.'
        );

        $this->assertStringContainsString(
            'await cache.delete(OFFLINE_MAP_HTML);',
            $sw,
            'Peta yang dibuat generator lama harus dihapus dari cache.'
        );

        // Tile di IndexedDB tidak ikut terhapus: peta bisa dibuat ulang tanpa
        // mengunduh ulang data.
        $this->assertStringNotContainsString(
            'caches.delete(TILES_CACHE)',
            $sw,
            'Membuang peta lama tidak boleh ikut membuang tile yang sudah diunduh.'
        );
    }

    public function test_service_worker_tidak_membuat_angka_elevasi_palsu(): void
    {
        $sw = file_get_contents(base_path('public/sw.js'));
        $this->assertIsString($sw);

        $this->assertStringNotContainsString(
            'function sampleElevations()',
            $sw,
            'Service worker tidak boleh mensintesis nilai elevasi; panel histogram akan menampilkan angka yang tidak berasal dari data.'
        );

        $this->assertStringContainsString(
            'resolve(null);',
            $sw,
            'Tanpa data elevasi yang bisa dibaca, statistik harus null supaya panel menampilkan N/A.'
        );

        $this->assertStringContainsString(
            '!layoutOptions.histogram ?',
            $sw,
            'Centang histogram harus tetap menghasilkan panel berisi keterangan, bukan panel yang hilang diam-diam.'
        );
    }
}
