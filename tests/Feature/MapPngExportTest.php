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

    public function test_modul_ekspor_berkas_png(): void
    {
        $module = $this->module();

        $this->assertStringContainsString('export async function buildMapPng(', $module);
        $this->assertStringContainsString("'image/png'", $module, 'Kanvas harus dikonversi ke PNG.');
        $this->assertStringContainsString('export function triggerPngDownload(', $module);
    }
    public function test_kompas_dan_bar_skala_ada_di_png(): void
    {
        $module = $this->module();

        // Tanpa penanda utara, peta hasil cetak tidak bisa dibaca arahnya.
        $this->assertStringContainsString('function drawCompass(', $module);

        foreach (['U', 'T', 'S', 'B'] as $arah) {
            $this->assertStringContainsString(
                "label: '{$arah}'",
                $module,
                "Arah mata angin {$arah} tidak digambar."
            );
        }

        // Huruf U/T/S/B sudah menyebut arahnya. Label "UTARA" tambahan di bawah
        // kompas hanya mengulang huruf U dan sempat jatuh di luar area peta.
        $this->assertStringNotContainsString(
            "ctx.fillText('UTARA', cx, cy + radius + 16);",
            $module,
            'Label UTARA tambahan pernah digambar di luar kotak peta dan mengulang huruf U.'
        );

        $this->assertStringContainsString('function drawScaleBar(', $module);
    }

    /**
     * Empat panel di atas peta harus menempati sudut yang berbeda.
     *
     * Versi lama menaruh kompas di pojok kanan atas, tempat histogram juga
     * diletakkan, sehingga histogram tertutup sebagian. Legenda juga memakai
     * lebar tetap 176 piksel sehingga label terpanjang keluar kotak.
     */
    public function test_panel_peta_tidak_saling_menimpa(): void
    {
        $module = $this->module();

        // Setiap sudut punya satu pemilik supaya tidak saling menimpa.
        $this->assertStringContainsString(
            'topLeft: picked.legend,',
            $module,
            'Legenda memiliki pojok kiri atas.'
        );
        $this->assertStringContainsString(
            'topRight: picked.histogram,',
            $module,
            'Histogram memiliki pojok kanan atas.'
        );
        $this->assertStringContainsString(
            'bottomLeft: picked.scaleBar,',
            $module,
            'Bilah skala memiliki pojok kiri bawah.'
        );
        $this->assertStringContainsString(
            'bottomRight: picked.northArrow,',
            $module,
            'Kompas harus pindah ke pojok kanan bawah supaya tidak menimpa histogram.'
        );

        // Kompas dihitung dari pojok peta, bukan dari lebar kanvas.
        $this->assertStringNotContainsString(
            'drawCompass(ctx, width - MARGIN - 56,',
            $module,
            'Posisi kompas dihitung dari lebar kanvas, sehingga tidak mengikuti kotak peta.'
        );
        $this->assertStringContainsString(
            'areaRect.x + areaRect.w - PANEL.inset - PANEL.compassRadius,',
            $module,
            'Kompas harus dihitung dari pojok kanan bawah gambar peta.'
        );

        // Lebar legenda mengikuti label terpanjang.
        $this->assertStringNotContainsString(
            'const width = 176;',
            $module,
            'Lebar legenda tetap membuat label terpanjang keluar kotak.'
        );
        $this->assertStringContainsString(
            'Math.max(',
            $module,
            'Lebar legenda harus diukur dari label terpanjang.'
        );
    }

    /**
     * Judul panjang harus dipecah, bukan dibiarkan keluar kanvas.
     *
     * fillText tidak membungkus teks. Nama wilayah yang panjang keluar dari
     * kanvas dan hilang begitu PNG disimpan, dan menimpa subjudul berisi
     * koordinat, zoom, serta skala.
     */
    public function test_judul_panjang_dipotong_sesuai_lebar_kanvas(): void
    {
        $module = $this->module();

        $this->assertStringNotContainsString(
            'ctx.fillText(title, MARGIN, cursorY);',
            $module,
            'Judul digambar satu baris tanpa dipecah, sehingga judul panjang keluar kanvas.'
        );

        $this->assertStringContainsString(
            'const titleLines = wrapText(ctx, title, mapWidth);',
            $module,
            'Judul harus dipecah menurut lebar konten.'
        );

        // Subjudul memuat koordinat, zoom, dan skala sekaligus, jadi bisa panjang.
        $this->assertStringContainsString(
            'const subtitleLines = wrapText(ctx, subtitleText, mapWidth);',
            $module,
            'Subjudul harus dipecah menurut lebar konten.'
        );

        // Kursor setelah subjudul harus ikut bertambah sesuai jumlah baris.
        // Kalau hanya menambah tinggi satu baris, subjudul dua baris menimpa
        // kotak peta.
        $this->assertStringContainsString(
            'cursorY += subtitleLines.length * SUBTITLE_LINE_HEIGHT + SUBTITLE_GAP;',
            $module,
            'Kursor harus menyesuaikan tinggi subjudul yang sebenarnya.'
        );

        $this->assertStringNotContainsString(
            'cursorY += 22 + 20;',
            $module,
            'Penambahan tetap mengasumsikan subjudul hanya satu baris.'
        );

        // Tinggi area peta harus dihitung dari jumlah baris yang sebenarnya,
        // bukan diasumsikan satu baris. Kalau tidak, judul panjang membuat
        // subjudul menimpa peta.
        $this->assertStringContainsString(
            'const titleLines = wrapText(probe, title, mapWidth).length;',
            $module,
            'Tinggi area peta harus memakai jumlah baris judul yang sebenarnya.'
        );
        $this->assertStringContainsString(
            'const subtitleLines = wrapText(probe, subtitleText, mapWidth).length;',
            $module,
            'Tinggi area peta harus memakai jumlah baris subjudul yang sebenarnya.'
        );

        // Tinggi peta dan tinggi kanvas harus berasal dari perhitungan yang sama.
        // Kalau tidak, yang diukur dan yang digambar berbeda dan isi bagian
        // bawah terpotong.
        $this->assertStringContainsString(
            'const available = height - MARGIN * 2 - headerHeight - FOOTER_GAP - FOOTER_HEIGHT;',
            $module,
            'Tinggi area peta harus dihitung dari sisa lembar, bukan dari isi teks.'
        );
        $this->assertStringContainsString(
            'const mapHeight = Math.round(Math.min(Math.max(available, MAP_MIN_HEIGHT), MAP_MAX_HEIGHT));',
            $module,
            'Tinggi area peta harus dibatasi supaya judul dan kaki halaman tetap muat.'
        );
    }

    /**
     * Snapshot peta tidak boleh dipotong saat dicetak.
     *
     * Versi lama memakai "cover": snapshot diperbesar sampai menutupi kotak
     * cetak 1128x560 dan bagian yang lebih dipotong di tengah. Karena rasio
     * jendela yang sedang dipakai hampir selalu lebih tinggi dari 2:1, hampir
     * setiap unduhan kehilangan bagian atas dan bawah wilayah yang sedang
     * dilihat, lalu sisanya diperbesar. Akibatnya peta hasil unduhan terlihat
     * ter-zoom dan wilayah seperti Kabupaten Maros tidak tampil utuh.
     *
     * Sekarang tinggi kotak mengikuti rasio snapshot dan gambar diletakkan
     * dengan "contain", jadi tidak ada satu pun bagian yang terpotong.
     */
    public function test_peta_tidak_dipotong_saat_dicetak(): void
    {
        $module = $this->module();

        $this->assertStringContainsString(
            'function fitContain(',
            $module,
            'Penempatan snapshot harus memakai fitContain yang tidak memotong.'
        );

        // Rumus "cover" yang memotong bagian tengah tidak boleh kembali.
        foreach (['drawWidth', 'drawHeight', 'offsetX', 'offsetY'] as $variabel) {
            $this->assertStringNotContainsString(
                "const {$variabel}",
                $module,
                "Variabel pemotong '{$variabel}' masih ada, sehingga snapshot dipotong di tengah lagi."
            );
        }

        $this->assertStringContainsString(
            'const placement = fitContain(mapCanvas.width, mapCanvas.height, mapX, mapY, mapWidth, mapHeight);',
            $module,
            'Snapshot harus ditempatkan memakai fitContain().'
        );

        // Batas yang digambar tetap batas viewport, karena tidak ada lagi yang
        // terbuang keluar. Menghitung ulang batas dari potongan hanya akan
        // menyusun ulang pemotongan yang baru saja dihapus.
        $this->assertStringContainsString(
            'const visibleBounds = bounds;',
            $module,
            'Grid koordinat harus memakai batas viewport apa adanya.'
        );
        $this->assertStringNotContainsString(
            'visibleBounds = {',
            $module,
            'Batas masih disempitkan mengikuti pemotongan yang sudah tidak ada.'
        );

        // Grid dan panel digambar di atas gambar peta, bukan di atas kotak cetak,
        // supaya tidak berdiri di atas pita abu-abu saat snapshot tertahan
        // oleh batas MIN/MAX.
        $this->assertStringContainsString(
            'drawGraticule(ctx, areaRect, visibleBounds, reserved)',
            $module,
            'Grid koordinat harus memakai area gambar peta.'
        );
        $this->assertStringContainsString(
            'areaRect = placement;',
            $module,
            'Area gambar peta harus memakai hasil penempatan snapshot.'
        );
    }

    /**
     * Lembar cetak harus landscape dan tidak boleh ikut memanjang.
     *
     * Tinggi lembar dulu diukur dari isi, termasuk daftar uraian komponen peta
     * kontur. Akibatnya keluarannya seperti kolom web: peta tinggal jadi pita
     * tipis di bagian atas, wilayah yang dipilih terlihat kecil, dan printer
     * harus memutar kertas. Sekarang ukuran lembar adalah pilihan format, bukan
     * hasil pengukuran, sehingga setiap wilayah keluar dengan format sama.
     */
    public function test_lembar_cetak_landscape_dan_tidak_ikut_memanjang(): void
    {
        $module = $this->module();

        // Lebar lebih besar dari tinggi: itu definisi landscape yang dipakai
        // di sini, dan tidak boleh berubah diam-diam.
        $this->assertMatchesRegularExpression(
            '/const SHEET_WIDTH = (\d+);/',
            $module,
            'Lebar lembar harus berupa konstanta yang bisa diperiksa.'
        );
        $this->assertMatchesRegularExpression(
            '/const SHEET_HEIGHT = (\d+);/',
            $module,
            'Tinggi lembar harus berupa konstanta yang bisa diperiksa.'
        );

        preg_match('/const SHEET_WIDTH = (\d+);/', $module, $lebar);
        preg_match('/const SHEET_HEIGHT = (\d+);/', $module, $tinggi);
        $this->assertGreaterThan(
            (int) $tinggi[1],
            (int) $lebar[1],
            'Lembar harus landscape: lebar lebih besar dari tinggi.'
        );

        // Tinggi kanvas harus diambil dari konstanta lembar, bukan dihitung
        // dari isi. Kalau dihitung dari isi, format keluar tidak seragam.
        $this->assertStringContainsString(
            'const height = SHEET_HEIGHT;',
            $module,
            'Tinggi kanvas harus memakai tinggi lembar yang dipatok.'
        );

        // Kode lama yang mengukur isi tidak boleh kembali.
        $this->assertStringNotContainsString(
            'function measureHeight(',
            $module,
            'Pengukur tinggi berbasis isi akan membuat format keluar berubah-ubah.'
        );
    }

    /**
     * PNG hanya berisi peta, bukan daftar uraian komponen peta kontur.
     *
     * Uraian itu cocok untuk lembar handout, tapi di sini ia membuat halaman
     * panjang dan peta mengecil sampai wilayah yang dipilih tidak terbaca.
     */
    public function test_png_tidak_mencetak_uraian_komponen_peta_kontur(): void
    {
        $module = $this->module();

        foreach (['Komponen Utama Peta Kontur', 'Kelengkapan Umum Peta'] as $judul) {
            $this->assertStringNotContainsString(
                $judul,
                $module,
                "Bagian '{$judul}' tidak lagi dicetak pada PNG."
            );
        }

        // Kode gambar bagian itu harus hilang, bukan hanya disobatkan.
        foreach (['drawSection(', 'teachingSections(', 'CONTOUR_COMPONENTS', 'MAP_COMPLETENESS'] as $simbol) {
            $this->assertStringNotContainsString(
                $simbol,
                $module,
                "Fungsi atau data '{$simbol}' masih ada padahal tidak lagi dipakai."
            );
        }

        // Yang tetap dicetak: peta dan keterangan sumbernya.
        $this->assertStringContainsString(
            'Garis kontur (garis tebal = kontur indeks)',
            $module,
            'Keterangan sumber peta harus tetap ada.'
        );
    }

    /**
     * Label grid tidak boleh jatuh di atas panel atau bilah skala.
     *
     * Label bujur secara bawaan ditulis di tepi bawah, tempat bilah skala berada, dan
     * label lintang di tepi kiri, tempat legenda berada. Keduanya jadi tidak
     * terbaca.
     */
    public function test_label_grid_menghindari_panel_dan_bilah_skala(): void
    {
        $module = $this->module();

        $this->assertStringContainsString(
            '@param {{topLeft:boolean,topRight:boolean,bottomLeft:boolean,bottomRight:boolean}} reserved',
            $module,
            'drawGraticule harus tahu sudut mana yang tertutup panel.'
        );

        $this->assertStringContainsString(
            "const lonSide = reserved.bottomLeft || reserved.bottomRight ? 'top' : 'bottom';",
            $module,
            'Label bujur harus pindah ke tepi atas saat bilah skala memakai tepi bawah.'
        );

        $this->assertStringContainsString(
            'const latSide = !reserved.topLeft',
            $module,
            'Label lintang harus pindah dari tepi kiri saat legenda menutupinya.'
        );
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
            'const { data } = ctx.getImageData(0, 0, size, size);',
            $module,
            'Kanvas peta yang di-taint CORS hanya terdeteksi lewat getImageData pada kanvas 2D hasil drawImage.'
        );
    }

    /**
     * Peta kosong tidak boleh lolos diam-diam.
     *
     * Kanvas peta bisa ada dan berukuran benar tetapi isinya sudah dibuang
     * browser. Salinan seperti itu menghasilkan kotak peta abu-abu polos
     * sementara PNG tetap tersimpan dan halaman tetap melaporkan "PNG
     * tersimpan", sehingga pengguna mengira peta ikut tercetak padahal yang ada
     * hanya bahan ajar.
     */
    public function test_kanvas_peta_kosong_ditolak_dengan_alasan(): void
    {
        $module = $this->module();

        // Isi kanvas harus dihitung, bukan diasumsikan ada.
        $this->assertStringContainsString(
            'function mapContentRatio(',
            $module,
            'Isi kanvas peta harus diukur supaya kanvas kosong terdeteksi.'
        );

        $this->assertStringContainsString(
            'ratio = mapContentRatio(copy);',
            $module,
            'Salinan kanvas peta harus diperiksa isinya.'
        );

        $this->assertStringContainsString(
            'if (ratio > 1.5) {',
            $module,
            'Kanvas yang terbaca kosong harus ditolak, bukan diteruskan.'
        );

        // Penyalinan dilakukan di dalam frame 'render' supaya buffer WebGL
        // belum kosong ketika dibaca.
        $this->assertStringContainsString(
            "map.once('render', () => {",
            $module,
            'Salinan harus dilakukan di dalam frame render, bukan di luar frame.'
        );

        // Alasan harus naik ke pemanggil supaya halaman bisa menampilkan
        // alasannya.
        $this->assertStringContainsString(
            'mapProblem: reason,',
            $module,
            'Alasan peta kosong harus diteruskan ke buildMapPng.'
        );

        $this->assertStringContainsString(
            'mapMissing: !canvas,',
            $module,
            'Pemanggil harus tahu bahwa peta tidak ikut tercetak.'
        );

        foreach ($this->petaPages() as $name => $source) {
            $this->assertStringContainsString(
                'result.mapMissing',
                $source,
                "Halaman {$name} harus memberi tahu pengguna ketika peta tidak ikut tercetak."
            );
        }
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

        // Penyesuaian kamera tidak lagi dilakukan halaman ini. Dulu halaman
        // menggeser peta sendiri dengan jumpTo dan menunggu whenMapIdle, dan
        // itulah yang membuat PNG berisi potongan viewport, bukan wilayah yang
        // dipilih. Sekarang halaman cukup meneruskan batas wilayah.
        $this->assertStringNotContainsString(
            'whenMapIdle',
            $pencarian,
            'Penunggu peta idle sudah pindah ke useMapPngExport yang juga memulihkan kamera.'
        );

        $this->assertMatchesRegularExpression(
            "/const png = await printMapPng\(\{[\s\S]{0,300}?region:[\s\S]{0,200}?components,[\s\S]{0,80}?\}\);/",
            $pencarian,
            'printMapPng harus menerima wilayah yang dipilih dan pilihan komponen sekaligus.'
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

    public function test_batas_peta_dibaca_dengan_bentuk_yang_benar(): void
    {
        $module = $this->module();

        // LngLatBounds.toArray() mengembalikan [[west, south], [east, north]]:
        // dua pasang angka, bukan empat angka berurutan. Memecahnya seperti
        // angka berurutan membuat west berisi pasangan dan east berisi
        // undefined, sehingga selisihnya NaN dan grid koordinat yang dicentang
        // pengguna diam-diam tidak pernah muncul di PNG.
        $this->assertStringNotContainsString(
            'raw.toArray()',
            $module,
            'toArray() LngLatBounds tidak boleh dipakai untuk destructuring empat angka.'
        );

        foreach (['getWest()', 'getSouth()', 'getEast()', 'getNorth()'] as $accessor) {
            $this->assertStringContainsString(
                $accessor,
                $module,
                "Batas peta harus dibaca lewat {$accessor}."
            );
        }
    }

    public function test_kanvas_peta_yang_kolaps_tidak_menggagalkan_ekspor(): void
    {
        $module = $this->module();

        // Kanvas 0x0 memberi rasio 0/0 = NaN, dan drawImage dengan ukuran NaN
        // melempar TypeError yang menggagalkan seluruh pembuatan PNG.
        $this->assertStringContainsString(
            'if (!source || !source.width || !source.height) {',
            $module,
            'Kanvas peta berukuran nol harus dikenali sebelum dipakai.'
        );
    }

    public function test_alasan_gagal_png_diteruskan_ke_pemanggil(): void
    {
        $module = $this->module();

        // "PNG peta gagal dibuat" tanpa sebabnya tidak bisa ditelusuri: yang
        // terlihat hanya dua UI yang diam. Alasan asli harus ikut naik.
        $this->assertStringContainsString(
            'return { ok: false, blocked: false, error: exportError.value };',
            $module,
            'Kegagalan ekspor harus membawa pesan galatnya.'
        );

        $this->assertStringContainsString(
            "return { ok: false, error: 'Ada proses cetak PNG lain yang sedang berjalan.' };",
            $module,
            'Jalur keluar lebih awal juga harus menjelaskan kenapa gagal.'
        );

        $pencarian = file_get_contents(base_path('resources/js/Pages/Peta/MapDenganPencarian.vue'));

        $this->assertStringContainsString(
            'PNG peta gagal: ${png.error',
            $pencarian,
            'Status unduhan harus menyebut alasan gagalnya, bukan hanya "gagal dibuat".'
        );

        $this->assertStringNotContainsString(
            "'PNG peta gagal dibuat.'",
            $pencarian,
            'Pesan tanpa alasan membuat galat tidak bisa ditelusuri.'
        );
    }

    /**
     * Bilah skala harus memilih jarak terbesar yang muat, bukan yang terkecil.
     *
     * Kandidat jarak dibaca dari daftar menaik. Versi lama memakai find() pada
     * daftar itu, jadi yang mengembalikan kandidat PERTAMA yang muat, yaitu
     * selalu 1 meter: bilahnya setebal 0,03 piksel dan ujungnya menulis "1 m"
     * padahal peta mencakup ratusan kilometer. Peta kontur tanpa skala yang
     * berarti tidak layak dipakai sebagai bahan ajar.
     */
    public function test_bilah_skala_memilih_jarak_terbesar_yang_muat(): void
    {
        $module = $this->module();

        $this->assertStringNotContainsString(
            'for (const meters of candidates) {',
            $module,
            'find() pada daftar menaik selalu mengembalikan jarak terkecil yang muat, yaitu 1 meter.'
        );

        // Kandidat harus dipindai dari belakang.
        $this->assertMatchesRegularExpression(
            '/for \(let i = candidates\.length - 1; i >= 0; i--\)/',
            $module,
            'Kandidat jarak bilah skala harus dipindai dari yang terbesar.'
        );

        // Verifikasi aritmetikanya dengan fungsi yang disalin dari modul.
        $niceDistance = static function (float $maxMeters, float $maxWidthPx, float $metersPerPx): int {
            $candidates = [1, 2, 5, 10, 20, 50, 100, 200, 500, 1000, 2000, 5000, 10000, 20000, 50000, 100000, 200000, 500000, 1000000, 2000000];

            for ($i = count($candidates) - 1; $i >= 0; $i--) {
                if ($maxWidthPx >= $candidates[$i] / $metersPerPx) {
                    return $candidates[$i];
                }
            }

            return (int) $maxMeters;
        };

        // z12 di lintang -5: sekitar 38 meter per piksel, kotak bilah 220 piksel.
        $metersPerPx = 156543.03392 * cos(-5.15 * M_PI / 180) / 2 ** 12;
        $meters = $niceDistance(INF, 220, $metersPerPx);

        $this->assertSame(5000, $meters, 'Jarak bilah skala harus 5 km pada z12 lintang -5.');
        $this->assertGreaterThan(
            100,
            $meters / $metersPerPx,
            'Bilah skala harus lebih dari 100 piksel panjangnya.'
        );

        // Bilah skala pada peta offline punya aturan yang sama.
        $sw = file_get_contents(base_path('public/sw.js'));
        $this->assertIsString($sw);
        // Kode lama memakai find() pada daftar menaik dan karena itu selalu
        // memilih 1 meter. Catatan wrongs-nya masih ada di komentar, jadi yang
        // diperiksa adalah pemanggilan find() itu sendiri sudah hilang.
        $this->assertStringNotContainsString(
            'SCALE_STEPS.find(',
            $sw,
            'Peta offline memakai kriteria yang sama salahnya: jarak terkecil yang lebih besar dari 1 meter.'
        );
        $this->assertStringContainsString(
            'for (let i = SCALE_STEPS.length - 1; i >= 0; i--) {',
            $sw,
            'Bilah skala peta offline harus memilih jarak terbesar yang masih muat.'
        );
    }

    /**
     * Angka skala di subjudul harus benar-benar muncul di PNG.
     *
     * exportMapPng menghitung "Skala 1:N" memakai scaleDenominator(), lalu
     * menaruh seluruh opsi halaman di dalam spread ...pngOptions. Karena itu
     * subtitle yang dikirim halaman menimpa subjudul tadi: perhitungannya jalan
     * tanpa galat tapi hasilnya tidak pernah tampil.
     */
    public function test_subjudul_skala_tidak_ditimpa_halaman(): void
    {
        $module = $this->module();

        $this->assertStringContainsString(
            'const { subtitle: subtitleOverride, ...restOptions } = pngOptions;',
            $module,
            'Subjudul dari halaman harus dipisah dari opsi lain, bukan ikut di-spread.'
        );

        $this->assertStringContainsString(
            '`${subtitleOverride} — ${detail}`',
            $module,
            'Subjudul halaman harus digabung dengan koordinat, zoom, dan skala.'
        );

        $this->assertStringNotContainsString(
            "...pngOptions,\n      });",
            $module,
            'Spread opsi mentah akan menimpa subjudul yang sudah menghitung skala.'
        );
    }

    /**
     * Cetak dari tombol 🖨️ harus menghasilkan HTML yang benar-benar jalan.
     *
     * Semua tag script dan link ditulis sebagai &lt;script&gt; di dalam template
     * literal, jadi document.write menulis teks "&lt;script..." ke jendela cetak:
     * MapLibre tidak pernah dimuat dan peta cetak selalu kosong. Tag penutup
     * script inline juga tidak pernah ada, sehingga sisa HTML ikut tertelan.
     */
    public function test_template_cetak_menghasilkan_html_yang_jalan(): void
    {
        $pencarian = file_get_contents(base_path('resources/js/Pages/Peta/MapDenganPencarian.vue'));
        $this->assertIsString($pencarian);

        $this->assertStringNotContainsString(
            '&lt;script',
            $pencarian,
            'Tag script yang di-escape menjadi teks tidak pernah dieksekusi, sehingga peta cetak kosong.'
        );

        $this->assertStringNotContainsString(
            '&lt;link',
            $pencarian,
            'Tag link yang di-escape menjadi teks tidak pernah memuat CSS MapLibre.'
        );

        // Penutup script inline harus ditulis sebagai <\/script> supaya compiler
        // SFC tidak menganggap blok script halaman sudah selesai di sana.
        $this->assertStringContainsString(
            '<\/script>',
            $pencarian,
            'Tag penutup script inline harus di-escape agar tidak menutup blok script SFC.'
        );

        // ${url} dipakai sebagai sumber batas, jadi variabelnya harus ada.
        $this->assertStringContainsString(
            'const url = geojsonUrl.value;',
            $pencarian,
            'Template cetak memakai ${url} untuk sumber batas; variabelnya harus dideklarasikan.'
        );
    }

    public function test_preview_wilayah_punya_basemap(): void
    {
        $pencarian = file_get_contents(base_path('resources/js/Pages/Peta/MapDenganPencarian.vue'));

        // Preview yang hanya berisi kontur dan batas wilayah di atas latar
        // kosong tidak memberi informasi: pengguna tidak bisa memastikan
        // wilayah yang dipilih memang yang akan diunduh.
        $this->assertStringContainsString(
            "'mini-basemap': {",
            $pencarian,
            'Preview wilayah harus punya sumber basemap.'
        );

        $this->assertStringContainsString(
            "{ id: 'mini-basemap-layer', type: 'raster', source: 'mini-basemap' },",
            $pencarian,
            'Layer basemap harus digambar lebih dulu supaya kontur menimpanya.'
        );

        $this->assertLessThan(
            strpos($pencarian, "'mini-kontur'"),
            strpos($pencarian, "'mini-basemap-layer'"),
            'Basemap harus berada di bawah layer kontur, bukan menutupinya.'
        );
    }

    /**
     * PNG harus berisi wilayah yang dipilih, bukan potongan yang sedang terlihat.
     *
     * Dua-duanya sudah pernah benar atau salahnya secara terpisah, sehingga
     * preview di dialog terlihat meyakinkan sementara berkasnya tidak sesuai.
     *
     * Penyebabnya: snapshot diambil dari kanvas peta yang sedang tampil, jadi
     * wilayah yang ikut tercetak adalah viewport pengguna. Memusatkan kamera saja
     * tidak menolong, karena zoomnya juga harus muat seluruh wilayah. Versi lama
     * memakai jumpTo dengan zoom paket offline yang tetap, sehingga untuk satu
     * kabupaten PNG hanya berisi potongan kecil di tengah wilayah itu.
     */
    public function test_png_mengikuti_wilayah_yang_dipilih_bukan_viewport(): void
    {
        $module = $this->module();
        $pencarian = file_get_contents(base_path('resources/js/Pages/Peta/MapDenganPencarian.vue'));

        // Penyesuaian kamera harus pakai fitBounds, satu-satunya cara MapLibre
        // yang memperhitungkan ukuran wadah dan rasio aspek sekaligus.
        $this->assertStringContainsString(
            'map.fitBounds(',
            $module,
            'Ekspor harus memakai fitBounds supaya seluruh wilayah muat dalam satu frame.'
        );

        $this->assertStringContainsString(
            'async function frameRegion(',
            $module,
            'Penyesuaian kamera harus terpusat supaya kedua halaman peta memakai cara yang sama.'
        );

        // Kamera lama harus dikembalikan, kalau tidak satu unduhan membuat
        // tampilan peta pengguna tersesat ke wilayah lain.
        $this->assertStringContainsString(
            'restoreCamera?.();',
            $module,
            'Kamera peta harus dipulihkan setelah snapshot diambil.'
        );

        $this->assertStringContainsString(
            'map.jumpTo(previous);',
            $module,
            'Pemulihan kamera harus memakai jumpTo supaya tidak memicu animasi.'
        );

        // Angka pada PNG harus dibaca setelah kamera diarahkan, kalau tidak
        // subjudul dan bilah skala menyebut wilayah yang tidak ada di gambar.
        $this->assertLessThan(
            strpos($module, 'const center = map?.getCenter?.()'),
            strpos($module, 'restoreCamera = await frameRegion('),
            'Kamera harus diarahkan ke wilayah sebelum pusat dan zoom dibaca.'
        );

        // Halaman tidak boleh lagi menggeser peta sendiri dengan zoom tetap.
        $this->assertStringNotContainsString(
            'zoom: offlineZoomMax.value,',
            $pencarian,
            'Zoom paket offline tidak bisa dipakai untuk membingkai wilayah; inilah bug preview benar tapi PNG salah.'
        );

        $this->assertStringContainsString(
            'region: { ...bbox',
            $pencarian,
            'Unduhan per-daerah harus meneruskan batas wilayah ke ekspor PNG.'
        );

        $this->assertStringContainsString(
            'regionMaxZoom:',
            $pencarian,
            'Batas zoom paket harus diteruskan sebagai pengaman fitBounds.'
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
