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
        return $this->sumber('resources/js/Composables/useMapPngExport.js');
    }

    private function petaIndex(): string
    {
        return $this->sumber('resources/js/Pages/Peta/Index.vue');
    }

    /**
     * Baca berkas sumber dengan akhir baris dinormalkan ke LF.
     *
     * Halaman peta dan modul ekspor PNG dibaca apa adanya supaya pemeriksaan
     * kontrak bisa menempel pada kode yang benar-benar dijalankan. Pemeriksaan
     * itu juga mencocokkan potongan beberapa baris, jadi CRLF di working copy
     * Windows akan membuatnya gagal atau, untuk negated assertion, lolos tanpa
     * benar-benar memeriksa apa pun. Git sudah menormalkan LF saat commit
     * (.gitattributes eol=lf), jadi menormalkan di sini hanya membuang
     * perbedaan yang memang tidak pernah masuk ke repositori.
     */
    private function sumber(string $relatif): string
    {
        $source = file_get_contents(base_path($relatif));
        $this->assertIsString($source, "Tidak bisa membaca {$relatif}.");

        return str_replace("\r\n", "\n", $source);
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
            'Peta/Index' => $this->sumber('resources/js/Pages/Peta/Index.vue'),
            'Peta/MapDenganPencarian' => $this->sumber('resources/js/Pages/Peta/MapDenganPencarian.vue'),
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
            'const titleLines = wrapText(ctx, title, textWidth);',
            $module,
            'Judul harus dipecah menurut lebar konten yang tersisa.'
        );

        // Lebar kontennya boleh lebih kecil dari lebar lembar karena logo
        // memakan sebagian header. Kalau judulnya dipecah menurut lebar penuh,
        // baris kedua dan seterusnya mulai tepat di atas logo.
        $this->assertStringContainsString(
            'const reserved = logo ? logo.width + LOGO_GAP : 0;',
            $module,
            'Kolom logo harus dipotong dari lebar isi header, bukan tetap sebesar lembar.'
        );

        $this->assertStringContainsString(
            'const titleLines = wrapText(probe, title, textWidth).length;',
            $module,
            'Pengukuran tinggi header harus memecah judul dengan lebar yang sama seperti menggambar.'
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
            'cursorY = subtitleTop + subtitleLines.length * SUBTITLE_LINE_HEIGHT + SUBTITLE_GAP;',
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
            'const subtitleLines = wrapText(probe, subtitle, mapWidth).length;',
            $module,
            'Tinggi area peta harus memakai jumlah baris subjudul yang sebenarnya.'
        );

        // Ruang logo juga harus ikut terukur, kalau tidak tinggi header yang
        // digambar lebih besar dari yang dihitung dan isinya menimpa peta.
        $this->assertStringContainsString(
            'const headerHeight = stackTop + subtitleLines * SUBTITLE_LINE_HEIGHT + SUBTITLE_GAP;',
            $module,
            'Tinggi header harus memperhitungkan ruang logo dan jumlah baris sebenarnya.'
        );

        // Perhitungan itu dipindah ke measureSheet() supaya jalur ekspor bisa
        // mengetahui rasio sisi kotak peta lebih dulu, tanpa menghitungnya
        // ulang dengan rumus yang bisa meleset.
        $this->assertStringContainsString(
            'const sheet = measureSheet({ title, subtitle: subtitleText, orientation, logo: logoSize });',
            $module,
            'buildMapPng harus memakai tinggi peta dari measureSheet, bukan menghitungnya sendiri.'
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
            'const mapHeight = Math.round(Math.min(Math.max(available, MAP_MIN_HEIGHT), maxMapHeight));',
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
            'Lembar bawaan harus landscape: lebar lebih besar dari tinggi.'
        );

        // Bawaannya harus menunjuk format yang benar-benar ada, kalau tidak
        // pemakai yang tidak memilih format akan mendapat pengukuran diam-diam.
        $this->assertStringContainsString(
            "const DEFAULT_ORIENTATION = 'landscape';",
            $module,
            'Format bawaan harus landscape dan harus ada di daftar format.'
        );
        $this->assertStringContainsString(
            "landscape: { label: 'Landscape 16:9', width: SHEET_WIDTH, height: SHEET_HEIGHT },",
            $module,
            'Format landscape bawaan harus memakai konstanta lembar yang dipatok.'
        );

        // Kode lama yang mengukur isi tidak boleh kembali.
        $this->assertStringNotContainsString(
            'function measureHeight(',
            $module,
            'Pengukur tinggi berbasis isi akan membuat format keluar berubah-ubah.'
        );
    }

    /**
     * fitBounds harus menerima bujur lebih dulu.
     *
     * MapLibre membaca setiap titik sebagai [lng, lat]. Versi lama menuliskannya
     * [south, west], sehingga bujur Sulawesi 119 terbaca sebagai lintang 119 dan
     * fitBounds melempar "Invalid LngLat latitude value". Kegagalan itu tertelan
     * catch yang mengembalikan fungsi kosong, jadi peta tidak pernah bergerak dan
     * PNG berisi viewport peta utama, persis seperti keluhuan pengguna.
     *
     * Urutan ini harus ada di test: harness berbasis stub bisa saja memakai
     * konvensi yang sama salahnya sehingga lolos tanpa menangkap apa pun.
     */
    public function test_fit_bounds_menerima_bujur_lalu_lintang(): void
    {
        $module = $this->module();

        $this->assertStringContainsString(
            '[region.west, region.south],',
            $module,
            'Sudut kiri bawah wilayah harus ditulis [bujur, lintang].'
        );

        $this->assertStringContainsString(
            '[region.east, region.north],',
            $module,
            'Sudut kanan atas wilayah harus ditulis [bujur, lintang].'
        );

        foreach (['[region.south, region.west]', '[region.north, region.east]'] as $terbalik) {
            $this->assertStringNotContainsString(
                $terbalik,
                $module,
                "Urutan {$terbalik} menukar bujur dan lintang sehingga fitBounds melempar."
            );
        }

        // Kegagalan fitBounds tidak boleh ditelan. Kalau ditelan, pengguna
        // menerima berkas berisi viewport tanpa ada petunjuk apa yang salah.
        $this->assertStringContainsString(
            'framed: false,',
            $module,
            'Kegagalan penyesuaian kamera harus ditandai, bukan diam-diam diabaikan.'
        );

        $this->assertStringContainsString(
            'framingProblem,',
            $module,
            'Alasan kegagalan penyesuaian kamera harus dikembalikan ke pemanggil.'
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

        $pencarian = $this->sumber('resources/js/Pages/Peta/MapDenganPencarian.vue');

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
        $sw = $this->sumber('public/sw.js');
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

        $pencarian = $this->sumber('resources/js/Pages/Peta/MapDenganPencarian.vue');

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
        $sw = $this->sumber('public/sw.js');
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
     * Angka skala harus 1:N, bukan meter per piksel.
     *
     * Halaman cetak pernah menulis "Skala ~1:76" di zoom 11. Angka 76 itu
     * meter per piksel, bukan pembilang rasio: pada lintang -5 dan zoom 11 satu
     * piksel mewakili sekitar 76 meter, sedangkan skala yang benar di titik itu
     * 1:287.752. Jadi angka yang tertulis bukan peta dengan skala 1:76, melainkan
     * peta dengan satu piksel sepanjang 76 meter. Pembilang rasio dan meter per
     * piksel memang berbeda sekitar 3.780 kali, jadi salah satulah yang tertulis
     * di setiap tempat yang salah.
     *
     * Ketiga tempat yang menampilkan skala harus memanggil helper yang sama,
     * supaya tidak ada yang lagi memakai rumus sendiri.
     */
    public function test_skala_1_n_bukan_meter_per_piksel(): void
    {
        $module = $this->module();

        $this->assertStringContainsString(
            'export function scaleLabel(latitude, zoom)',
            $module,
            'Bentuk siap tulis dari skala harus ada di satu helper.'
        );

        $this->assertStringContainsString(
            'return `1:${denominator.toLocaleString(\'id-ID\')}`;',
            $module,
            'Label skala harus ditulis sebagai 1:N dengan format Locale Indonesia.'
        );

        $this->assertStringContainsString(
            "return '1:-';",
            $module,
            'Pembilang yang tidak masuk akal di dekat kutub tidak boleh ditulis sebagai 1:0.'
        );

        // Subjudul PNG memakai helper, bukan merangkai sendiri.
        $this->assertStringContainsString(
            '| Skala ${scaleLabel(center.lat, zoom)}`;',
            $module,
            'Subjudul PNG harus memakai scaleLabel() supaya formatnya sama dengan yang lain.'
        );

        foreach (['MapDenganPencarian', 'Index'] as $halaman) {
            $sumber = $this->sumber("resources/js/Pages/Peta/{$halaman}.vue");

            $this->assertStringNotContainsString(
                '156543.03392',
                $sumber,
                "{$halaman}: rumus meter per piksel dipakai sebagai pembilang rasio, sehingga angka skala yang tertulis salah."
            );

            $this->assertStringContainsString(
                'scaleLabel(center.lat, zoom)',
                $sumber,
                "{$halaman}: halaman cetak harus memakai helper skala yang sama dengan PNG."
            );
        }
    }

    /**
     * Skala 1:N harus terlihat di halaman, bukan hanya di berkas PNG.
     *
     * ScaleControl bawaan MapLibre hanya menggambar batang tanpa angkanya, jadi
     * pembaca tidak tahu batangnya mewakili berapa. Angka 1:N ikut berubah
     * begitu zoom atau lintang pusat peta berubah, jadi harus dihitung ulang
     * ketika kamera bergerak, bukan hanya sekali saat peta dimuat.
     */
    public function test_halaman_menampilkan_angka_skala(): void
    {
        $pencarian = $this->sumber('resources/js/Pages/Peta/MapDenganPencarian.vue');

        $this->assertStringContainsString(
            'Skala 1:{{ mapScale }}',
            $pencarian,
            'Halaman peta harus menuliskan skala 1:N, bukan hanya bilah skala.'
        );

        $this->assertStringContainsString(
            'const mapScale = ref(\'\');',
            $pencarian,
            'Angka skala harus punya state sendiri supaya bisa diikat ke template.'
        );

        // Kamera yang bergerak harus memperbarui angkanya.
        $this->assertStringContainsString(
            "map.value.on('move', updateMapScale);",
            $pencarian,
            'Skala ikut berubah saat peta digeser, jadi harus ikut diperbarui.'
        );

        $this->assertStringContainsString(
            "map.value.on('zoom', updateMapScale);",
            $pencarian,
            'Skala wajib berubah saat zoom berubah.'
        );

        $this->assertStringContainsString(
            'const label = scaleLabel(center.lat, zoom);',
            $pencarian,
            'Angka skala di layar harus dihitung dengan helper yang sama seperti PNG.'
        );

        // Skala dan koordinat berbagi satu titik jangkar supaya tidak saling
        // menimpa, dan tidak menabrak bilah skala bawaan di pojok yang sama.
        $this->assertStringContainsString(
            'absolute bottom-12 left-3 flex flex-col items-start gap-1.5',
            $pencarian,
            'Skala dan koordinat harus ditumpuk dalam satu wadah, bukan dipatok terpisah.'
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
        $pencarian = $this->sumber('resources/js/Pages/Peta/MapDenganPencarian.vue');
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
        $pencarian = $this->sumber('resources/js/Pages/Peta/MapDenganPencarian.vue');

        // Preview yang hanya berisi kontur dan batas wilayah di atas latar
        // kosong tidak memberi informasi: pengguna tidak bisa memastikan
        // wilayah yang dipilih memang yang akan diunduh.
        //
        // Definisi basemap diambil dari modul bersama supaya teks atribusi
        // lisensinya tidak bisa berbeda dari peta utama, jadi yang diperiksa di
        // sini adalah sumbernya tetap ditunjuk, bukan bentuk definisinya.
        $this->assertStringContainsString(
            "'mini-basemap': BASEMAPS.osm,",
            $pencarian,
            'Preview wilayah harus punya sumber basemap dari definisi bersama.'
        );

        $this->assertStringContainsString(
            "{ id: 'mini-basemap-layer', type: 'raster', source: 'mini-basemap' },",
            $pencarian,
            'Layer basemap harus digambar lebih dulu supaya kontur menimpanya.'
        );

        // Pencarian dibatasi pada definisi layer ("id: ..."), bukan nama layer
        // belaka. Peta pantulan sekarang menyebut 'mini-kontur' di modul
        // pemirrornya, dan kemunculan pertama nama itu ada jauh di atas style,
        // sehingga pencarian longgar membandingkan posisi yang tidak berkaitan
        // sama sekali.
        $this->assertLessThan(
            strpos($pencarian, "id: 'mini-kontur'"),
            strpos($pencarian, "id: 'mini-basemap-layer'"),
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
        $pencarian = $this->sumber('resources/js/Pages/Peta/MapDenganPencarian.vue');

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
        $sw = $this->sumber('public/sw.js');
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

    /**
     * Lembar cetak harus dirender setajam mungkin, tapi tidak sampai melebihi
     * luas kanvas yang bisa dibuat peramban seluler.
     *
     * Skala 2 menghasilkan berkas 3200 x 2000. Semua kelas teks, garis,
     * dan simbol digambar ulang di kanvas lembar, jadi menaikkan skalanya
     * memperbaiki semua isi lembar, bukan cuma peta.
     */
    public function test_lembar_cetak_dirender_pada_resolusi_tinggi(): void
    {
        $module = $this->module();

        $this->assertStringContainsString(
            'const SHEET_SCALE = 3;',
            $module,
            'Skala lembar harus 3 supaya isi lembar lebih rapat.'
        );

        $this->assertStringContainsString(
            'const MAX_SHEET_PIXELS = 16_000_000;',
            $module,
            'Luas kanvas lembar harus dijaga di bawah batas peramban seluler.'
        );

        $this->assertStringContainsString(
            'const scale = sheetScale(sheet);',
            $module,
            'Skala yang dipakai harus lewat sheetScale() supaya jaring pengaman berlaku.'
        );

        $this->assertStringContainsString(
            'Math.sqrt(MAX_SHEET_PIXELS / area)',
            $module,
            'Skala harus diturunkan otomatis kalau ukuran lembar someday bertambah.'
        );
    }

    /**
     * Peta yang diunduh dari ponsel harus landscape dan tajam, sama seperti
     * hasil desktop.
     *
     * Kanvas peta mengikuti ukuran layar, jadi di ponsel kanvasnya portrait.
     * Snapshot portrait di dalam kotak peta yang landscape tidak dipotong oleh
     * fitContain, melainkan diberi pita abu-abu di kiri dan kanan yang memakai
     * lebih dari separuh lebar berkas.
     */
    public function test_ekspor_dari_ponsel_dibuat_landscape_seperti_desktop(): void
    {
        $module = $this->module();

        $this->assertStringContainsString(
            'function exportSurfaceFor(map, targetAspect)',
            $module,
            'Permukaan ekspor harus dihitung dari rasio sisi kotak peta.'
        );

        // Kanvas dibetulkan tanpa syarat. Kalau ukurannya dibiarkan apa adanya,
        // kanvas ponsel yang cuma beberapa ratus piksel menghasilkan bitmap
        // sekitar 1440 x 2000 untuk kotak cetak yang butuh 2712 x 3279, jadi
        // petanya buram dan sebagian lebar berkas terbuang jadi pita abu-abu.
        $this->assertStringContainsString(
            'const width = EXPORT_SURFACE_WIDTH;',
            $module,
            'Kanvas peta harus selalu memakai lebar permukaan ekspor.'
        );

        $this->assertStringContainsString(
            'const height = Math.round(EXPORT_SURFACE_WIDTH / targetAspect);',
            $module,
            'Tinggi kanvas harus mengikuti rasio kotak peta, bukan rasio layar.'
        );

        // Rasio piksel harus memakai plafon yang memperhitungkan sisi dan luas
        // kanvas. Dua kesalahan lama tidak boleh kembali:
        // min(max(...)) selalu memilih nilai terkecil sehingga ketajaman tidak
        // pernah bertambah, dan max(ratioSaatIni, plafon) membiarkan perangkat
        // beresolusi tinggi melewati plafon dan kehabisan memori GPU.
        $this->assertStringContainsString(
            'const ratio = Math.max(1, ceiling);',
            $module,
            'Rasio piksel ekspor harus memakai plafon yang sudah memperhitungkan batas perangkat.'
        );

        $this->assertStringNotContainsString(
            'Math.min(ceiling, Math.max(currentRatio',
            $module,
            'Urutan min(max(...)) membuat rasio ekspor selalu jatuh ke nilai terkecil.'
        );

        $this->assertStringNotContainsString(
            'Math.max(currentRatio, ceiling)',
            $module,
            'Rasio perangkat tidak boleh melewati plafon; di potrait dpr 3 hasilnya 21 juta piksel.'
        );

        // Batas luas inilah yang menjaga format potrait tetap muat di GPU.
        $this->assertStringContainsString(
            'const EXPORT_CANVAS_MAX_PIXELS = 12_000_000;',
            $module,
            'Kanvas WebGL perlu batas luas, bukan cuma batas sisi terpanjang.'
        );

        $this->assertStringContainsString(
            'Math.sqrt(EXPORT_CANVAS_MAX_PIXELS / Math.max(width * height, 1))',
            $module,
            'Plafon rasio piksel harus ikut memperhitungkan luas kanvas.'
        );
    }

    /**
     * Ukuran kanvas dan rasio piksel harus dikembalikan setelah ekspor selesai.
     *
     * Kalau tidak, satu unduhan dari ponsel membuat peta utama terkunci pada
     * ukuran ekspor yang lebar di dalam wadah sempit, sehingga peta di layar
     * jadi tidak bisa dipakai sampai halaman dimuat ulang.
     */
    public function test_ukuran_peta_dikembalikan_setelah_ekspor(): void
    {
        $module = $this->module();

        $this->assertStringContainsString(
            'async function withExportSurface(map, surface, run)',
            $module,
            'Ekspor harus berjalan di dalam pembungkus yang memulihkan ukuran kanvas.'
        );

        $this->assertStringContainsString(
            'container.style.width = previousWidth;',
            $module,
            'Lebar wadah peta harus dikembalikan setelah ekspor.'
        );

        $this->assertStringContainsString(
            'container.style.height = previousHeight;',
            $module,
            'Tinggi wadah peta harus dikembalikan setelah ekspor.'
        );

        $this->assertStringContainsString(
            'map.setPixelRatio?.(previousRatio);',
            $module,
            'Rasio piksel peta harus dikembalikan, kalau tidak peta berikutnya jadi buram.'
        );

        // Pemulihan harus ada di finally supaya tetap jalan walau ekspornya gagal.
        $this->assertMatchesRegularExpression(
            '/finally\s*\{[^}]*container\.style\.width = previousWidth;/s',
            $module,
            'Pemulihan ukuran kanvas harus berada di blok finally.'
        );
    }

    /**
     * Peta utama harus menyebut batas kanvas WebGL secara eksplisit.
     *
     * Saat ekspor, rasio piksel kanvas sengaja dinaikkan supaya petanya tajam.
     * Kalau maxCanvasSize tidak ditulis, MapLibre bisa menurunkannya lagi secara
     * diam-diam dan berkasnya tetap buram tanpa ada tanda apa pun.
     */
    public function test_peta_utama_menulis_batas_kanvas_webgl(): void
    {
        $pencarian = $this->sumber('resources/js/Pages/Peta/MapDenganPencarian.vue');

        $this->assertStringContainsString(
            'maxCanvasSize: [4096, 4096],',
            $pencarian,
            'Batas kanvas peta utama harus ditulis eksplisit, bukan mengandalkan bawaan.'
        );
    }

    /**
     * Tombol Cetak harus menulis PNG isi kanvas utama, persis seperti yang tampil.
     *
     * Sebelumnya tombol ini membuka jendela cetak terpisah dengan peta MapLibre
     * kedua, yang isinya viewport saat jendela dibuka, bukan yang ada di layar.
     */
    public function test_tombol_cetak_mencetak_png_dari_kanvas_utama(): void
    {
        $pencarian = $this->sumber('resources/js/Pages/Peta/MapDenganPencarian.vue');
        $module = $this->module();

        $this->assertStringContainsString(
            '@click="printMapPng({ matchViewport: true })"',
            $pencarian,
            'Tombol Cetak harus mencetak PNG kanvas utama, bukan membuka jendela cetak.'
        );

        $this->assertStringNotContainsString(
            '@click="printMap"',
            $pencarian,
            'Tombol Cetak tidak boleh lagi membuka jendela cetak terpisah.'
        );

        // Batas yang sedang terlihat harus dibaca sebelum ukuran kanvas diubah
        // untuk resolusi ekspor. Kalau dibaca belakangan, luas yang dirujuk
        // menunjuk ke wilayah yang tidak sedang terlihat.
        $this->assertStringContainsString(
            'const viewportRegion = matchViewport ? visibleRegion(map) : null;',
            $module,
            'Luas yang sedang terlihat harus dibaca dari peta, bukan dihitung dari kamera.'
        );

        $this->assertStringContainsString(
            'const targetRegion = region ?? viewportRegion;',
            $module,
            'Wilayah yang dibingkai harus memakai pilihan pengguna, atau tampilan layar bila tidak ada.'
        );
    }

    /**
     * Ekspor tampilan layar tidak boleh memakai margin wilayah.
     *
     * Margin itu gunanya supaya panel legenda, histogram, kompas, dan skala tidak
     * menutupi sudut wilayah. Untuk tampilan yang sedang terlihat, margin membuat
     * berkasnya berbeda dari layar, padahal itu yang diminta tombol Cetak.
     */
    public function test_ekspor_tampilan_layar_tanpa_margin_wilayah(): void
    {
        $module = $this->module();

        $this->assertStringContainsString(
            'function regionPadding(map, enabled = true)',
            $module,
            'Margin wilayah harus bisa dimatikan.'
        );

        $this->assertStringContainsString(
            '{ pad: !matchViewport },',
            $module,
            'Ekspor tampilan layar harus membingkai tanpa margin.'
        );

        $this->assertStringContainsString(
            'padding: regionPadding(map, options.pad !== false),',
            $module,
            'frameRegion harus menghormati pilihan margin dari pemanggil.'
        );

        // Batas zoom paket offline hanya untuk wilayah yang dipilih. Tampilan
        // yang sedang terlihat harus memakai zoom yang sedang dipakai pengguna.
        $this->assertStringContainsString(
            'region ? regionMaxZoom : null,',
            $module,
            'Batas zoom arsip tidak boleh ikut membatasi ekspor tampilan layar.'
        );
    }

    /**
     * PDF hasil "Simpan sebagai PDF" dari halaman cetak tidak boleh kehilangan peta.
     *
     * Dua hal membuat gejalanya sama persis, jadi keduanya harus dijaga:
     *
     * 1. window.print() yang dipanggil pada event 'load'. Event itu hanya
     *    berarti style terurai dan frame pertama selesai digambar; tile-nya
     *    biasanya masih turun, jadi dialog cetak terbuka saat kanvas kosong.
     * 2. Peta cetakan tanpa preserveDrawingBuffer. Browser boleh membuang
     *    buffer WebGL sebelum halaman dicetak, dan yang tampil adalah kotak
     *    kosong padahal petanya sudah termuat.
     */
    public function test_halaman_cetak_tidak_mencetak_pdf_tanpa_peta(): void
    {
        $pencarian = $this->sumber('resources/js/Pages/Peta/MapDenganPencarian.vue');

        $this->assertStringContainsString(
            "map.once('idle', cetakSekarang);",
            $pencarian,
            'Dialog cetak harus menunggu idle, yaitu semua tile selesai, bukan hanya load.'
        );

        $this->assertStringNotContainsString(
            "map.once('load', () => { window.print(); });",
            $pencarian,
            'Menunggu load membuat dialog cetak terbuka sebelum tile selesai.'
        );

        $this->assertStringContainsString(
            'preserveDrawingBuffer: true,',
            $pencarian,
            'Peta cetakan harus memakai preserveDrawingBuffer supaya buffer WebGL tidak dibuang.'
        );

        // 'idle' bisa tidak pernah datang kalau satu sumber menggantung, jadi
        // dialog cetaknya tetap harus muncul.
        $this->assertStringContainsString(
            'setTimeout(cetakSekarang, 15000);',
            $pencarian,
            'Cetak perlu batas waktu supaya dialog tetap muncul kalau idle tidak datang.'
        );

        $this->assertStringContainsString(
            'if (printed) return;',
            $pencarian,
            'Cetak hanya boleh berjalan sekali walau idle dan batas waktunya sama-sama datang.'
        );
    }

    /**
     * Format lembar harus bisa dipilih pengguna, dan pratinjaunya harus jujur.
     *
     * Tanpa pratinjau, "potrait" dan "landscape" hanya istilah yang artinya
     * berbeda antara pemakai dan sistem, dan hasilnya disappoint karena keduanya
     * tidak pernah dibandingkan.
     */
    public function test_pemakai_bisa_memilih_format_lembar_dan_melihat_pratinjaunya(): void
    {
        $module = $this->module();
        $pencarian = $this->sumber('resources/js/Pages/Peta/MapDenganPencarian.vue');

        $this->assertStringContainsString(
            "portrait: { label: 'Potrait 3:4', width: 1000, height: 1333 },",
            $module,
            'Format potrait 3:4 harus tersedia untuk dipilih.'
        );

        $this->assertStringContainsString(
            'export const SHEET_ORIENTATION_LIST',
            $module,
            'Daftar format harus bisa dibaca halaman untuk mengisi pemilihnya.'
        );

        // Pratinjau harus memakai fungsi yang sama dengan penggambar lembar.
        $this->assertStringContainsString(
            'export function describeSheet(orientation',
            $module,
            'Pratinjau harus dihitung dari ukuran lembar yang sama.'
        );

        $this->assertStringContainsString(
            'const printFormatPreview = computed(() => describeSheet(printOrientation.value));',
            $pencarian,
            'Halaman harus memakai hasil describeSheet untuk pratinjau format.'
        );

        $this->assertStringContainsString(
            'v-model="printOrientation"',
            $pencarian,
            'Pemilih format harus terikat ke state yang dipakai ekspor.'
        );

        // Rasio kotak pratinjau harus mengikuti rasio lembar, kalau tidak
        // potrait akan tampil mendatar dan preview-nya menipu.
        $this->assertStringContainsString(
            'aspectRatio: `${printFormatPreview.width} / ${printFormatPreview.height}`',
            $pencarian,
            'Kotak pratinjau harus memakai rasio sisi lembar yang dipilih.'
        );

        // Ukuran berkas harus ikut ditampilkan, karena "potrait" saja tidak
        // memberitahu berapa resolusi yang sebenarnya diterima pengguna.
        $this->assertStringContainsString(
            '{{ printFormatPreview.outputWidth }} &times; {{ printFormatPreview.outputHeight }} piksel',
            $pencarian,
            'Pratinjau harus menyebut ukuran berkas PNG hasilnya.'
        );

        // Format yang sama harus dipakai tombol Cetak dan unduhan offline.
        $this->assertStringContainsString(
            'orientation: printOrientation.value,',
            $pencarian,
            'Ekspor PNG harus mengirim format yang dipilih pengguna.'
        );
    }

/**
     * Header lembar memakai lambang urutan organisasi Kepramukaan, dan
     * kegagalan memuatnya tidak boleh menggagalkan PNG.
     *
     * Dua hal yang dijaga di sini. Pertama, sumber logonya bukan lagi logo
     * ambalan yang bisa diunggah: lembar ini dibagikan sebagai bahan ajar, dan
     * header yang ikut berubah setiap kali admin mengganti logo membuat dua
     * unduhan dari wilayah yang sama terlihat seperti dari dua sumber berbeda.
     *
     * Kedua, lambang itu dimuat dengan mode CORS. Gambar lintas origin hanya
     * boleh digambar ke kanvas kalau server-nya mengirim header CORS. Kalau
     * tidak, kanvas ikut tercemar dan toBlob gagal dengan SecurityError, jadi
     * seluruh PNG hilang gara-gara satu logo.
     */
    public function test_lambang_kepramukaan_masuk_header_tanpa_merusak_png(): void
    {
        $module = $this->module();
        $pencarian = $this->sumber('resources/js/Pages/Peta/MapDenganPencarian.vue');

        $this->assertStringContainsString(
            "export const SHEET_LOGO_URL = '/images/Logo_Urutan_Organiasasi_Kepramukaan.png';",
            $module,
            'Lambang resmi urutan organisasi Kepramukaan harus jadi sumber logo header lembar.'
        );

        // Berkas lambang harus benar-benar dilayani, kalau tidak header cetakan
        // selalu kosong tanpa jejaknya karena pemuatannya gagal diam-diam.
        $this->assertFileExists(
            public_path('images/Logo_Urutan_Organiasasi_Kepramukaan.png'),
            'Berkas lambang tidak ada di public/images, jadi tidak bisa dilayani.'
        );

        // Halaman peta harus memakai lambang yang sama, bukan kembali ke logo
        // ambalan milik setiap penyewa.
        $this->assertStringContainsString(
            'const logoUrl = computed(() => SHEET_LOGO_URL);',
            $pencarian,
            'Halaman peta harus memakai lambang lembar yang sama dengan bawaan modul ekspor.'
        );

        $this->assertStringNotContainsString(
            'ambalan?.logo_url',
            $pencarian,
            'Logo ambalan yang diunggah tidak boleh lagi menjadi logo header lembar PNG.'
        );

        // Bawaan di kedua lapisan: halaman yang lupa mengirim logo pun tetap
        // mencetak lambang, jadi hasilnya tidak bergantung pada pemanggil.
        $this->assertSame(
            2,
            preg_match_all('/logo = SHEET_LOGO_URL,/', $module),
            'Bawaan logo harus dipasang di buildMapPng dan exportMapPng.'
        );

        $this->assertStringContainsString(
            'image.crossOrigin =',
            $module,
            'Logo harus dimuat dengan mode CORS supaya tidak mencederai kanvas.'
        );

        $this->assertStringContainsString(
            'function loadLogo(url)',
            $module,
            'Pemuatan logo harus dibungkus supaya kegagalan bisa dilewati.'
        );

        $this->assertStringContainsString(
            'ctx.drawImage(logoImage, MARGIN, MARGIN, logoSize.width, logoSize.height);',
            $module,
            'Logo harus digambar di header lembar.'
        );

        // Lebar teks header harus menyusut, bukan menimpa logo.
        $this->assertStringContainsString(
            'const { textLeft, textWidth, subtitleTop } = sheet;',
            $module,
            'Judul harus digeser agar tidak menimpa logo, memakai hasil pengukuran yang sama.'
        );

        // Logo bisa jauh lebih tinggi daripada satu baris judul, jadi subjudul
        // tidak boleh langsung mengikuti judul: baris pertamanya akan berada di
        // dalam kotak logo dan teksnya tertutup gambarnya.
        $this->assertStringContainsString(
            'const stackTop = Math.max(titleHeight, logo ? logo.height + LOGO_GAP : 0);',
            $module,
            'Subjudul harus mulai di bawah logo, bukan di sampingnya.'
        );

        $this->assertStringContainsString(
            'ctx.fillText(line, MARGIN, subtitleTop + index * SUBTITLE_LINE_HEIGHT);',
            $module,
            'Baris subjudul harus digambar pada tinggi yang sudah diukur.'
        );

        // Jalur ekspor menyiapkan kanvas peta memakai rasio dari measureSheet,
        // jadi logo juga harus ikut diukur di sana. Kalau tidak, rasio yang
        // dipakai menyiapkan kanvas peta berbeda dari rasio yang digambar dan tepi
        // PNG berisi pita abu-abu.
        $this->assertStringContainsString(
            'const logoSize = logoBox(await loadLogo(restOptions.logo));',
            $module,
            'Jalur ekspor harus mengukur ruang logo sebelum menyiapkan kanvas peta.'
        );

        $this->assertStringContainsString(
            'logo: logoSize,',
            $module,
            'measureSheet harus menerima ruang logo supaya tinggi header ikut terhitung.'
        );

        // Tanpa cache, logo dimuat dua kali: sekali untuk mengukur, sekali
        // untuk menggambar.
        $this->assertStringContainsString(
            'if (!logoCache.has(url)) {',
            $module,
            'Logo yang sudah dimuat harus dipakai ulang, bukan diunduh ulang.'
        );
    }

    /**
     * Lambang resmi itu memanjang, jadi kotak logo tidak boleh tetap persegi.
     *
     * Logo lama 640 x 640 muat rapi di kotak 64 x 64. Lambang urutan organisasi
     * Kepramukaan berbanding sekitar 4:1, dan kalau kotak logonya tetap
     * persegi, gambar selebar 64 hanya jadi setinggi 15: lambang yang sudah
     * kecil makin tidak terbaca karena diperkecil, bukan karena ruangnya kurang.
     */
    public function test_kotak_logo_menyesuaikan_gambar_yang_memanjang(): void
    {
        $module = $this->module();

        $this->assertStringContainsString(
            'const LOGO_MAX_WIDTH = 224;',
            $module,
            'Kotak logo harus memuat lambang yang memanjang.'
        );

        $this->assertStringContainsString(
            'const LOGO_MAX_HEIGHT = 56;',
            $module,
            'Tinggi kotak logo harus dibatasi supaya judul punya ruang di sebelahnya.'
        );

        // Rasio gambar harus tetap terjaga di dalam kotak, kalau tidak lambang
        // teregang dan tidak lagi sama dengan berkas aslinya.
        $this->assertStringContainsString(
            'const ratio = Math.min(LOGO_MAX_WIDTH / image.width, LOGO_MAX_HEIGHT / image.height);',
            $module,
            'Ukuran logo harus mengikuti rasio gambar, bukan dipaksa jadi kotak.'
        );

        // Pratinjau format di dialog unduhan tidak memuat logo sungguhan, jadi
        // ruang yang dipesannya harus memakai kotak yang sama dengan yang dipakai
        // menggambar. Kalau tidak, pratinjau menjanjikan area peta yang lebih
        // besar dari yang benar-benar ada.
        $this->assertStringContainsString(
            'logo: { width: LOGO_MAX_WIDTH, height: LOGO_MAX_HEIGHT },',
            $module,
            'Pratinjau format harus memesan ruang logo sebesar kotak yang sebenarnya.'
        );

        $this->assertStringNotContainsString(
            'LOGO_BOX',
            $module,
            'Kotak logo persegi yang lama tidak boleh tersisa, karena menyelesaikan gambar yang memanjang.'
        );
    }

    /**
     * Header dan kaki halaman harus terbaca sebagai dua bagian yang rapi.
     *
     * Tanpa garis pemisah, subjudul terakhir, bingkai peta, keterangan sumber,
     * dan waktu cetak menyatu jadi satu blok teks kecil yang tidak jelas di mana
     * judul berhenti dan peta dimulai. Susunan dua baris di kaki halaman juga
     * membuat isinya seimbang: keterangan isi peta dan kreditnya di atas, waktu
     * cetak di kiri bawah dan nama sistem di kanan bawah.
     */
    public function test_header_dan_kaki_halaman_punya_garis_pemisah_dan_susun_berimbang(): void
    {
        $module = $this->module();

        // Garis pemisah header digambar di dalam jarak subjudul ke peta, bukan
        // di luar, supaya ruangnya sudah dipesan saat mengukur tinggi area peta.
        $this->assertStringContainsString(
            'const headerRuleY = subtitleTop + subtitleLines.length * SUBTITLE_LINE_HEIGHT + SUBTITLE_GAP / 2;',
            $module,
            'Garis pemisah header harus digambar pada jarak yang sudah dipesan measureSheet.'
        );

        $this->assertStringContainsString(
            'const SUBTITLE_GAP = 26;',
            $module,
            'Jarak subjudul ke peta harus cukup untuk garis pemisah, bukan hanya ruang kosong.'
        );

        $this->assertStringContainsString(
            'const footerRuleY = cursorY + FOOTER_ROW + FOOTER_RULE_GAP / 2;',
            $module,
            'Kaki halaman harus dipisahkan dari baris keterangan sumber oleh garis.'
        );

        // Waktu cetak dan nama sistem harus jadi dua blok yang berderajat,
        // bukan satu baris panjang yang menempel di tepi kertas.
        $this->assertStringContainsString(
            'ctx.fillText(`Dicetak pada ${new Date().toLocaleString(\'id-ID\')}`, MARGIN, footerBottomY);',
            $module,
            'Waktu cetak harus berada di kaki halaman kiri pada tinggi yang sudah dipesan.'
        );

        $this->assertStringContainsString(
            "ctx.fillText('AMBARA - Sistem Digital Ambalan UPT SMAN 2 Maros', width - MARGIN, footerBottomY);",
            $module,
            'Nama sistem harus berada di kaki halaman kanan, diseimbangkan dengan waktu cetak.'
        );

        // Kaki halaman yang bertambah baris harus ikut mengurangi tinggi area
        // peta, kalau tidak isinya menimpa tepi kertas.
        $this->assertStringContainsString(
            'const FOOTER_HEIGHT = FOOTER_ROW + FOOTER_RULE_GAP + FOOTER_ROW_BOTTOM;',
            $module,
            'Tinggi kaki halaman harus dijumlahkan dari tinggi tiap barisnya.'
        );

        $this->assertStringNotContainsString(
            'height - MARGIN * 0.5,',
            $module,
            'Kaki halaman lama ditumpangkan ke tepi kertas, sehingga posisinya bergeser antarwilayah.'
        );
    }

    /**
     * Preview di dialog harus bisa menyembunyikan kontur dan mengikuti kanvas utama.
     *
     * Preview memakai peta MapLibre kedua. Kalau tombol hanya mengubah peta
     * utama, preview tetap menampilkan kontur yang sudah disembunyikan dan PNG
     * yang diunduh berbeda dari yang dilihat pengguna.
     */
    public function test_preview_dialog_bisa_mematikan_kontur_dan_mengikuti_peta_utama(): void
    {
        $pencarian = $this->sumber('resources/js/Pages/Peta/MapDenganPencarian.vue');

        $this->assertStringContainsString(
            'const MINI_LAYER_MIRROR = {',
            $pencarian,
            'Layer preview harus punya pasangan dengan layer peta utama.'
        );

        $this->assertStringContainsString(
            "'garis-kontur': 'mini-kontur',",
            $pencarian,
            'Layer kontur peta utama harus punya kembarannya di preview.'
        );

        // Mematikan kontur harus mengubah kedua peta.
        $this->assertStringContainsString(
            'function setLayerVisibility(layerId, visible)',
            $pencarian,
            'Visibilitas harus lewat satu fungsi yang menyentuh kedua peta.'
        );

        $this->assertStringContainsString(
            'const miniDone = miniLayerId ? apply(miniMap.value, miniLayerId) : true;',
            $pencarian,
            'Fungsi visibilitas harus ikut mengubah peta preview lewat helper yang sama.'
        );

        $this->assertStringContainsString(
            "target.setLayoutProperty(id, 'visibility', visibility);",
            $pencarian,
            'Helper visibilitas harus menuliskan properti ke peta mana pun yang diterimanya.'
        );

        // Layer bisa belum ada saat tombol ditekan, jadi Visibility ditunda
        // sampai peta selesai dimuat. Tanpa itu toggle yang ditekan terlalu cepat
        // hilang begitu style selesai diurai.
        $this->assertStringContainsString(
            'if (!miniDone && miniMap.value && !miniMap.value.loaded()) {',
            $pencarian,
            'Preview yang belum selesai dimuat harus menunda penerapan visibilitas.'
        );

        // Dialog harus punya kendali yang memakai toggel yang sama.
        $this->assertStringContainsString(
            '@change="toggleLayer"',
            $pencarian,
            'Dialog harus bisa menyembunyikan garis kontur lewat toggle yang sama.'
        );

        $this->assertStringContainsString(
            ':checked="showContour"',
            $pencarian,
            'Checkbox dialog harus menampilkan keadaan kontur yang sedang aktif.'
        );

        // Preview butuh layer yang sama supaya tidak bohong soal tampilan.
        $this->assertStringContainsString(
            "id: 'mini-hillshade'",
            $pencarian,
            'Preview harus punya layer hillshade seperti peta utama.'
        );

        $this->assertStringContainsString(
            "id: 'mini-kabupaten-labels'",
            $pencarian,
            'Preview harus punya layer label kabupaten seperti peta utama.'
        );

        // Layer symbol butuh glyphs, tanpa itu shader teks gagal dan preview
        // berhenti digambar.
        $this->assertStringContainsString(
            'glyphs: GLYPHS_URL,',
            $pencarian,
            'Style preview wajib punya glyphs karena sekarang memakai layer symbol.'
        );
    }

    /**
     * Tombol Cetak dan unduhan offline harus memakai pilihan komponen yang sama.
     *
     * Tombol Cetak sebelumnya tidak mengirim apa pun, jadi buildMapPng memakai
     * DEFAULT_COMPONENTS yang histogram dan grid-nya mati. PNG "tampilan saat
     * ini" jadi berbeda dari berkas unduhan offline.
     */
    public function test_kedua_ekspor_memakai_pilihan_komponen_yang_sama(): void
    {
        $pencarian = $this->sumber('resources/js/Pages/Peta/MapDenganPencarian.vue');

        $this->assertStringContainsString(
            'const printComponents = computed(() => ({',
            $pencarian,
            'Pilihan komponen harus punya satu sumber yang dipakai kedua ekspor.'
        );

        $this->assertStringContainsString(
            'components = printComponents.value',
            $pencarian,
            'printMapPng harus memakai pilihan komponen bersama sebagai bawaan.'
        );

        $this->assertStringContainsString(
            'const components = printComponents.value;',
            $pencarian,
            'Unduhan offline juga harus memakai pilihan komponen bersama.'
        );

        // Bersamaan dengan komponen, format juga harus satu sumber.
        $this->assertStringContainsString(
            'orientation: printOrientation.value,',
            $pencarian,
            'Kedua ekspor harus mengirim format yang sama.'
        );
    }
}
