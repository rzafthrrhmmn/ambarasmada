<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Unduhan peta offline dulu selalu berakhir dengan "0 tile berhasil diunduh".
 *
 * PMTiles v3 mengurutkan tile dengan kurva Hilbert, sedangkan zxyToTileId()
 * di service worker menyisipkan bit x dan y berselang-seling. Nomor tile yang
 * dihasilkan tidak pernah ada di direktori arsip, jadi findTile() mengembalikan
 * null untuk setiap tile, pengecualian ditelan di dalam loop, dan halaman
 * menampilkan "Selesai! 0 tile" tanpa satu pun tanda gagal.
 *
 * Test di bawah mengunci penyebabnya, bukan hanya gejalanya: satu-satunya
 * pembaca arsip di service worker, jadi regresi di sini langsung berarti
 * unduhan offline kembali mengembalikan nol tile.
 */
class OfflineTileLookupTest extends TestCase
{
    private function sw(): string
    {
        return file_get_contents(base_path('public/sw.js'));
    }

    /** @return array<string, string> */
    private function petaPages(): array
    {
        $pages = [
            'Peta/Index' => 'resources/js/Pages/Peta/Index.vue',
            'Peta/MapDenganPencarian' => 'resources/js/Pages/Peta/MapDenganPencarian.vue',
        ];

        $sources = [];

        foreach ($pages as $name => $path) {
            $source = file_get_contents(base_path($path));
            $this->assertIsString($source, "Tidak bisa membaca {$path}.");
            $sources[$name] = $source;
        }

        return $sources;
    }

    public function test_tile_id_uses_the_hilbert_curve_not_a_plain_bit_interleave(): void
    {
        $sw = $this->sw();

        $this->assertStringContainsString(
            'function zxyToTileId(',
            $sw,
            'PMTiles v3 mengindeks tile lewat kurva Hilbert.'
        );

        // Bentuk kurva Hilbert: acc += ((3 * rx) ^ ry) * (1 << a) di setiap
        // tingkat zoom, diikuti rotasi. Bit-interleave yang salah hanya punya
        // id |= 1 << (2 * bit).
        $this->assertStringContainsString(
            'acc += ((3 * rx) ^ ry) * (1 << a);',
            $sw,
            'zxyToTileId() harus menghitung kurva Hilbert, bukan bit-interleave.'
        );

        $this->assertStringContainsString(
            'rotateHilbert(',
            $sw,
            'Kurva Hilbert butuh rotasi di setiap tingkat zoom.'
        );

        $this->assertStringNotContainsString(
            'id |= 1 << (2 * bit)',
            $sw,
            'Bit-interleave x/y menghasilkan nomor tile yang tidak ada di arsip PMTiles sehingga unduhan selalu 0 tile.'
        );

        $this->assertStringNotContainsString(
            'id |= 1 << (2 * bit + 1)',
            $sw,
            'Bit-interleave x/y menghasilkan nomor tile yang tidak ada di arsip PMTiles sehingga unduhan selalu 0 tile.'
        );
    }

    public function test_zoom_level_offset_starts_at_the_zoom_base(): void
    {
        // ((1 << z) * (1 << z) - 1) / 3 adalah offset zoom PMTiles; tanpa itu
        // nomor tile meleset dan tidak pernah ditemukan di direktori.
        $this->assertStringContainsString(
            '((1 << z) * (1 << z) - 1) / 3',
            $this->sw()
        );
    }

    public function test_zoom_range_is_clamped_to_the_archive(): void
    {
        $sw = $this->sw();

        // Arsip produksi hanya memuat z8-z12. Tanpa pemotongan, slider z14
        // meminta ratusan tile yang memang tidak ada di arsip.
        $this->assertStringContainsString(
            'const effMin = Math.max(zoomMin, reader.minZoom);',
            $sw
        );
        $this->assertStringContainsString(
            'const effMax = Math.min(zoomMax, reader.maxZoom);',
            $sw
        );

        $this->assertStringContainsString(
            "type: 'DOWNLOAD_ERROR'",
            $sw,
            'Rentang zoom yang tidak berpotongan harus dilaporkan, bukan diabaikan.'
        );
    }

    public function test_leaf_directories_are_followed(): void
    {
        $sw = $this->sw();

        $this->assertStringContainsString(
            'if (entry.runLength > 0) return entry;',
            $sw,
            'Entri dengan runLength 0 adalah pointer direktori leaf, bukan tile.'
        );

        $this->assertStringContainsString(
            'leafOffset + entry.offset',
            $sw,
            'Arsip dengan root directory > 16 KB menyimpan sebagian tile di direktori leaf.'
        );
    }

    public function test_zero_result_is_reported_as_an_error(): void
    {
        $sw = $this->sw();

        $this->assertStringNotContainsString(
            '} catch {
            // Skip failed tile',
            $sw,
            'Menelan setiap kegagalan membuat unduhan 0 tile terlihat seperti sukses.'
        );

        $this->assertStringContainsString(
            'if (downloadedTiles === 0) {',
            $sw,
            'Unduhan tanpa satu pun tile harus gagal eksplisit.'
        );

        $this->assertStringContainsString(
            'type: \'DOWNLOAD_ERROR\'',
            $sw,
            'Kegagalan di dalam service worker harus dikirim ke halaman, kalau tidak tombol unduh menggantung selamanya.'
        );
    }

    public function test_offline_tiles_are_not_labelled_as_still_compressed(): void
    {
        $sw = $this->sw();

        $this->assertStringNotContainsString(
            "'Content-Encoding': 'gzip'",
            $sw,
            'Tile disimpan setelah didekompresi; header Content-Encoding: gzip membuat MapLibre gagal mendecode MVT sehingga layer tidak tergambar.'
        );

        $this->assertStringContainsString(
            'application/vnd.mapbox-vector-tile',
            $sw
        );
    }

    public function test_generated_offline_map_page_survives_service_worker_updates(): void
    {
        $sw = $this->sw();

        $this->assertStringContainsString(
            "const OFFLINE_HTML_CACHE = 'jaya-jaya-jaya-offline-html';",
            $sw
        );

        $this->assertStringContainsString(
            'OFFLINE_HTML_CACHE,',
            $sw,
            'Cache halaman peta offline harus ikut dipertahankan saat activate, kalau tidak setiap pembaruan service worker menghapus peta yang baru diunduh.'
        );

        $this->assertStringContainsString(
            'if (new URL(request.url).pathname === OFFLINE_MAP_HTML) {',
            $sw,
            '/offline-map.html hanya ada di Cache Storage dan tidak pernah dilayani origin, jadi harus dicek sebelum jaringan.'
        );
    }

    public function test_both_peta_pages_handle_download_errors(): void
    {
        foreach ($this->petaPages() as $name => $source) {
            $this->assertStringContainsString(
                "if (data.type === 'DOWNLOAD_ERROR') {",
                $source,
                "Halaman {$name} harus menampilkan galat unduhan dan melepas status mengunduh."
            );

            $this->assertStringContainsString(
                'const pmtilesUrl = props.mapConfig.pmtilesUrl;',
                $source,
                "Halaman {$name} harus mengirim URL absolut arsip; service worker membacanya lewat HTTP Range."
            );
        }
    }

    public function test_peta_pages_read_the_archive_zoom_range(): void
    {
        foreach ($this->petaPages() as $name => $source) {
            $this->assertStringContainsString(
                'loadArchiveZoomRange',
                $source,
                "Halaman {$name} harus membaca rentang zoom arsip agar estimasi tile tidak menjanjikan tile yang tidak ada."
            );

            $this->assertStringContainsString(
                ':min="zoomBounds.min"',
                $source,
                "Slider di halaman {$name} harus dibatasi rentang zoom arsip."
            );
        }
    }

    public function test_service_worker_and_pages_agree_on_the_message_protocol(): void
    {
        $sw = $this->sw();

        $this->assertStringContainsString(
            'const SW_PROTOCOL = ',
            $sw,
            'Service worker harus menandai format pesannya dengan nomor protokol.'
        );

        // Nomor yang sama harus ditulis di kedua sisi. Kalau tidak, halaman
        // akan menolak balasan worker yang sebenarnya sudah benar.
        $this->assertSame(
            1,
            preg_match('/const SW_PROTOCOL = (\d+);/', $sw, $swMatch),
            'Nomor protokol service worker harus terbaca.'
        );

        $module = file_get_contents(base_path('resources/js/ServiceWorker.js'));
        $this->assertIsString($module, 'Tidak bisa membaca resources/js/ServiceWorker.js.');
        $this->assertSame(
            1,
            preg_match('/export const SW_PROTOCOL = (\d+);/', $module, $moduleMatch),
            'Nomor protokol harus diekspor dari satu modul supaya halaman tidak menyalin sendiri.'
        );

        $this->assertSame(
            $swMatch[1],
            $moduleMatch[1],
            'Nomor protokol service worker dan halaman harus sama, kalau tidak setiap unduhan ditolak sebagai versi lama.'
        );

        foreach ($this->petaPages() as $name => $source) {
            $this->assertStringContainsString(
                'if (data.protocol !== SW_PROTOCOL) {',
                $source,
                "Halaman {$name} harus menolak balasan service worker versi lama."
            );

            $this->assertStringContainsString(
                'protocol: SW_PROTOCOL,',
                $source,
                "Halaman {$name} harus menyertakan nomor protokol pada permintaan."
            );
        }
    }

    public function test_a_stale_service_worker_reports_a_reload_instead_of_zero_tiles(): void
    {
        $sw = $this->sw();

        // Gejala yang pernah dilihat pengguna: "Selesai! 0 tile berhasil diunduh
        // (zoom undefined-undefined)". Worker lama mengirim DOWNLOAD_COMPLETE
        // tanpa zoomMin/zoomMax dan menelan galatnya sendiri, sehingga halaman
        // menampilkan angka nol seolah-olah unduhan berhasil.
        $this->assertStringContainsString(
            'function send(message) {',
            $sw,
            'Semua pesan dari service worker harus melewati satu helper yang menempelkan nomor protokol.'
        );

        $this->assertStringContainsString(
            'port.postMessage({ protocol: SW_PROTOCOL, ...message });',
            $sw
        );

        $this->assertStringContainsString(
            'Muat ulang halaman lalu ulangi unduhan.',
            $sw,
            'Protokol yang berbeda harus dijelaskan sebagai kebutuhan muat ulang, bukan sebagai kegagalan unduhan biasa.'
        );
    }

    public function test_service_worker_update_is_checked_before_downloading(): void
    {
        $module = file_get_contents(base_path('resources/js/ServiceWorker.js'));
        $this->assertIsString($module);

        $this->assertStringContainsString(
            'await registration.update();',
            $module,
            'Tombol unduh harus memicu pemeriksaan sw.js terbaru; kalau tidak, worker lama melayani permintaan setelah deploy.'
        );

        $app = file_get_contents(base_path('resources/js/app.js'));
        $this->assertIsString($app);

        $this->assertStringContainsString(
            "updateViaCache: 'none'",
            $app,
            'sw.js tidak boleh dilayani dari HTTP cache, kalau tidak salinan lama tidak pernah tergantikan.'
        );
    }

    /**
     * Alat ukur dan bookmark harus bisa dipakai, bukan hanya ada di kode.
     *
     * startMeasurement() dan goToBookmark() tidak pernah dipanggil dari template,
     * jadi panel pengukuran tidak pernah muncul dan tampilan yang disimpan lewat
     * tombol 🔖 tidak pernah bisa dimuat lagi. Kode pengukuran juga hanya
     * menambahkan sumber GeoJSON tanpa layer, sehingga tidak ada yang tergambar.
     */
    public function test_alat_ukur_dan_bookmark_bisa_dipakai(): void
    {
        $pencarian = $this->pencarian();

        foreach (["startMeasurement('distance')", "startMeasurement('area')"] as $call) {
            $this->assertStringContainsString(
                $call,
                $pencarian,
                "Tombol alat ukur harus memanggil {$call}; tanpa itu panel pengukuran tidak pernah muncul."
            );
        }

        $this->assertStringContainsString(
            '@click="goToBookmark(bookmark)"',
            $pencarian,
            'Bookmark yang disimpan harus punya tombol untuk kembali ke tampilan itu.'
        );

        $this->assertStringContainsString(
            'v-for="(bookmark, index) in bookmarks"',
            $pencarian,
            'Bookmark perlu daftar di UI; sebelumnya hanya bisa disimpan, tidak pernah dibuka lagi.'
        );

        foreach (["const MEASURE_LINE_LAYER = 'measurement-line';", "const MEASURE_FILL_LAYER = 'measurement-fill';"] as $declaration) {
            $this->assertStringContainsString(
                $declaration,
                $pencarian,
                'Pengukuran harus punya layer; sumber GeoJSON kosong tidak akan terlihat.'
            );
        }

        $this->assertStringContainsString(
            "addLayer({\n      id: MEASURE_LINE_LAYER,",
            $pencarian,
            'Layer garis harus benar-benar ditambahkan ke peta.'
        );

        $this->assertStringContainsString(
            "addLayer({\n      id: MEASURE_FILL_LAYER,",
            $pencarian,
            'Layer isi dibutuhkan supaya mode luas punya bentuk, bukan hanya garis.'
        );

        $this->assertStringContainsString(
            "geometry: { type: 'Polygon', coordinates: [[...coords, coords[0]]] }",
            $pencarian,
            'Mode luas harus mengirim Polygon, bukan hanya LineString.'
        );
    }

    /**
     * Peta tidak boleh menampilkan kontrol ganda.
     *
     * Template pernah menyediakan wadah untuk bilah skala dan kompas sendiri
     * dengan ref yang tidak pernah diisi, sementara initMap() juga menambahkan
     * ScaleControl dan NavigationControl. Hasilnya dua bilah skala, dan yang
     * di pojok kiri bawah selalu kosong. Kontrol lokasi punya masalah serupa.
     */
    public function test_kontrol_peta_tidak_ganda(): void
    {
        $pencarian = $this->pencarian();

        $this->assertStringNotContainsString(
            'ref="scaleBarContainer"',
            $pencarian,
            'Wadah skala kosong membuat peta menampilkan dua bilah skala.'
        );

        $this->assertStringNotContainsString(
            'ref="northArrowContainer"',
            $pencarian,
            'Wadah kompas kosong membuat peta menampilkan kompas dua kali.'
        );

        $this->assertStringContainsString(
            'new ScaleControl({ maxWidth: 200, unit: \'metric\' })',
            $pencarian,
            'Skala harus memakai ScaleControl bawaan MapLibre.'
        );

        $this->assertStringContainsString(
            'new NavigationControl({ showCompass: true, showZoom: false })',
            $pencarian,
            'Kompas harus memakai NavigationControl bawaan MapLibre.'
        );

        $this->assertStringNotContainsString(
            'GeolocateControl(',
            $pencarian,
            'Tombol lokasi di template sudah memanggil locateUser(); kontrol bawaan akan membuat dua tombol lokasi.'
        );
    }

    /**
     * Legenda harusnya milik Vue, bukan DOM yang disuntik dari script.
     *
     * initElevationLegend() menulis innerHTML ke dalam elemen yang dimiliki Vue,
     * sehingga re-render berikutnya dapat menghapus isinya, dan legenda tidak
     * pernah bisa disembunyikan karena tidak ada tombolnya.
     */
    public function test_legenda_elevasi_jadi_markup_vue(): void
    {
        $pencarian = $this->pencarian();

        $this->assertStringNotContainsString(
            'function initElevationLegend(',
            $pencarian,
            'Legenda harus ditulis sebagai markup supaya Vue yang memilikinya.'
        );

        $this->assertStringNotContainsString(
            'ref="legendContainer"',
            $pencarian,
            'Ref yang diisi manual bisa terhapus oleh re-render Vue.'
        );

        $this->assertStringContainsString(
            '@click="showElevationLegend = !showElevationLegend"',
            $pencarian,
            'Legenda harus bisa disembunyikan dan ditampilkan lagi.'
        );
    }

    /**
     * Overlay peta tidak boleh menumpuk di koordinat yang sama.
     *
     * Pencarian, panel pengukuran, dan penanda offline dulu ditumpuk di
     * top-4 left-4 sehingga saling menutupi. Dua tombol juga memakai
     * right-52 yang tidak ada di skala spasi Tailwind, jadi tombolnya tidak
     * dapat offset sama sekali dan menimpa pemilih basemap.
     *
     * Aturannya sekarang lebih ketat: seluruh overlay atas tidak lagi dipatok
     * ke pojoknya sendiri, melainkan berbagi satu wadah flex yang boleh
     * membungkus baris. Jadi tidak boleh ada lagi kelas absolute per-pojok
     * di lapisan atas, dan wadah itu harus menyisakan ruang untuk
     * NavigationControl bawaan MapLibre di pojok kanan atas.
     */
    public function test_overlay_peta_tidak_menumpuk(): void
    {
        foreach (['MapDenganPencarian', 'Index'] as $halaman) {
            $sumber = $this->halamanPeta($halaman);

            $this->assertSame(
                1,
                substr_count($sumber, 'absolute inset-x-3 top-3 flex flex-wrap'),
                "{$halaman}: lapisan atas harus satu wadah flex yang boleh membungkus baris."
            );

            $this->assertSame(
                0,
                substr_count($sumber, 'absolute left-3 top-3') + substr_count($sumber, 'absolute left-4 top-4'),
                "{$halaman}: jangan patok panel kiri atas sendiri, ia harus anak dari wadah lapisan atas."
            );

            $this->assertStringContainsString(
                'pr-12',
                $sumber,
                "{$halaman}: wadah lapisan atas harus menyisakan ruang untuk NavigationControl pojok kanan atas."
            );

            $this->assertSame(
                0,
                substr_count($sumber, 'peta-panel-kontrol'),
                "{$halaman}: offset top terpisah membuat lapisan atas menumpuk lagi."
            );

            $this->assertSame(
                0,
                substr_count($sumber, 'right-52') + substr_count($sumber, 'right-96'),
                "{$halaman}: right-52 bukan kelas Tailwind yang ada dan right-96 menggeser tombol tanpa alasan."
            );

            $this->assertStringNotContainsString(
                'absolute bottom-4 left-4',
                $sumber,
                "{$halaman}: readout koordinat dan bilah skala MapLibre sama-sama di pojok kiri bawah."
            );
        }
    }

    /**
     * Listener window harus dilepas lagi, bukan dibuat inline.
     *
     * Empat listener dibuat dengan arrow function di dalam initMap(). Arrow
     * function tidak punya nama, jadi tidak ada rujukan untuk dilepas. Karena
     * halaman ini dibuka lewat Inertia tanpa muat ulang penuh, setiap kunjungan
     * berikutnya menambah satu pasang listener lagi, dan listener dari
     * kunjungan yang sudah lewat masih hidup. Akibatnya satu event
     * map-bookmark menyimpan bookmark beberapa kali sekaligus.
     *
     * Listener yang dibungkus named function boleh dilepas di onBeforeUnmount.
     */
    public function test_listener_window_dilepas_saat_halaman_ditutup(): void
    {
        $pencarian = $this->pencarian();

        $this->assertStringContainsString(
            "window.addEventListener('online', onWindowOnline);",
            $pencarian,
            'Status daring harus dipasang lewat named function supaya bisa dilepas.'
        );

        $this->assertStringContainsString(
            "window.addEventListener('offline', onWindowOffline);",
            $pencarian,
            'Status luar jaringan harus dipasang lewat named function supaya bisa dilepas.'
        );

        $this->assertStringContainsString(
            "window.removeEventListener('online', onWindowOnline);",
            $pencarian,
            'Listener status daring harus dilepas saat komponen ditutup.'
        );

        $this->assertStringContainsString(
            "window.removeEventListener('offline', onWindowOffline);",
            $pencarian,
            'Listener status luar jaringan harus dilepas saat komponen ditutup.'
        );

        // map.remove() hanya membersihkan listener milik peta itu sendiri.
        $this->assertStringContainsString(
            'window.removeEventListener(',
            $pencarian,
            'onBeforeUnmount harus melepas listener window; map.remove() tidak ikut melakukannya.'
        );

        // Batas waktu pesan "dimuat sebagian" juga punya hidup yang lebih panjang
        // daripada komponennya.
        $this->assertStringContainsString(
            'clearTimeout(partialLoadTimer);',
            $pencarian,
            'Timer "dimuat sebagian" harus dibatalkan saat halaman ditutup.'
        );
    }

    /**
     * Isi popup tidak boleh dirangkai sebagai HTML.
     *
     * Nama kabupaten, id, dan nama provinsi berasal dari berkas GeoJSON, dan
     * display_name dari pencarian berasal dari balasan server Nominatim. Kalau
     * dirangkai jadi string lalu masuk lewat setHTML, isinya bisa menyisipkan
     * tag sendiri dan skrip itu berjalan di origin aplikasi.
     *
     * Tombolnya juga tidak boleh memakai onclick berisi JSON.stringify: atribut
     * HTML diapit tanda kutip ganda, sedangkan JSON juga memakai tanda kutip
     * ganda, jadi satu nama dengan tanda kutip sudah menutup atribut lebih awal.
     */
    public function test_popup_dibangun_dari_node_bukan_html(): void
    {
        $pencarian = $this->pencarian();

        $this->assertStringNotContainsString(
            'onclick="window.dispatchEvent(',
            $pencarian,
            'onclick yang isinya JSON.stringify bisa keluar dari atributnya lewat tanda kutip pada nama wilayah.'
        );

        $this->assertStringContainsString(
            'function popupWilayah(',
            $pencarian,
            'Isi popup harus punya pembangun sendiri supaya bisa diuji.'
        );

        $this->assertStringContainsString(
            'judul.textContent = nama;',
            $pencarian,
            'Nama wilayah harus masuk lewat textContent, yang tidak pernah mengartikan tag.'
        );

        $this->assertStringContainsString(
            ".setDOMContent(popupWilayah(",
            $pencarian,
            'Popup harus dipasang dari node, bukan dari string HTML.'
        );

        // Nama hasil pencarian juga dari luar, lewat balasan server.
        $this->assertStringNotContainsString(
            'text-[#1f2937]">${result.display_name}',
            $pencarian,
            'display_name dari server tidak boleh disisipkan sebagai HTML.'
        );
    }

    /**
     * Warna di dalam kueri wajib diisi dari larik tetap.
     *
     * CSS calc() mewajibkan spasi di sekitar operator + dan -. Tanpa spasi,
     * browser membuang deklarasi itu sepenuhnya, jadi batas lebar yang
     * sengaja dibuat supaya tidak menabrak tumpukan pojok kanan bawah tidak
     * pernah berlaku.
     */
    public function test_calc_tidak_kehilangan_spasi(): void
    {
        $pencarian = $this->pencarian();

        // Tailwind menulis spasi di dalam arbitrary value sebagai garis bawah,
        // lalu mengubahnya jadi spasi saat CSS dibuat.
        $this->assertStringNotContainsString(
            'calc(100%-',
            $pencarian,
            'calc() tanpa spasi di sekitar operator minus dibuang browser, jadi lebarnya tidak pernah dibatasi.'
        );

        $this->assertStringNotContainsString(
            'calc(100vw-',
            $pencarian,
            'calc() tanpa spasi di sekitar operator minus dibuang browser.'
        );

        $this->assertStringContainsString(
            'calc(100%_-_13rem)',
            $pencarian,
            'Batas lebar readout koordinat harus memakai spasi yang ditulis sebagai garis bawah.'
        );
    }

    /**
     * Legenda harus menyebut warna dan tebal yang benar-benar ada di peta.
     *
     * Layer kontur hanya memakai satu warna, dan kontur indeks dibedakan oleh
     * tebal garisnya, bukan warnanya. Legenda lama men manufacture empat warna
     * yang tidak pernah muncul di peta mana pun, sehingga orang mencari
     * perbedaan yang memang tidak ada. Legenda cetak di halaman cetak sudah
     * benar sejak awal, jadi legenda di layar tidak boleh berbeda.
     */
    public function test_legenda_cocok_dengan_layer_peta(): void
    {
        $pencarian = $this->pencarian();

        foreach (['#a0522d', '#cd853f', '#8b4513'] as $warna) {
            $this->assertStringNotContainsString(
                $warna,
                $pencarian,
                "Warna {$warna} tidak dipakai layer mana pun, jadi warna di legenda mengarang sesuatu."
            );
        }

        $this->assertStringContainsString(
            'Kontur indeks (setiap 50 m)',
            $pencarian,
            'Legenda harus menjelaskan bahwa kontur indeks dibedakan oleh tebal, bukan warna.'
        );

        // Warna dan tebal swatch di layar harus lewat kelas, bukan atribut
        // style inline: proyek melarang inline CSS, dan warnanya tidak bisa
        // ikut berubah kalau tema berubah.
        $this->assertStringContainsString(
            'bg-[#8c510a]',
            $pencarian,
            'Swatch kontur pada legenda harus memakai kelas warna, bukan style inline.'
        );

        $this->assertStringContainsString(
            'border-dashed border-[#2563eb]',
            $pencarian,
            'Swatch batas kabupaten harus memakai kelas, bukan style inline.'
        );
    }

    /**
     * Tombol tidak boleh memakai ikon keyboard atau emoji.
     *
     * Ikon emoji dirender berbeda tiap sistem operasi dan sering tidak sepadan
     * dengan tinggi baris di sebelahnya, sehingga tampilan halaman berbeda
     * antar perangkat. Ikon yang dipakai bersama harus berasal dari satu
     * komponen SVG.
     */
    public function test_tombol_memakai_svg_bukan_ikon_keyboard(): void
    {
        foreach (['MapDenganPencarian', 'Index'] as $halaman) {
            $sumber = $this->halamanPeta($halaman);

            foreach (['📍', '🖨️', '🔖', '📏', '📐', '🗺️', '🛰️', '🌙', '📶', '📴', '⟳'] as $ikon) {
                $this->assertStringNotContainsString(
                    $ikon,
                    $sumber,
                    "{$halaman}: ikon {$ikon} tampil berbeda tiap perangkat. Pakai NavIcon."
                );
            }

            $this->assertStringNotContainsString(
                ">\n              ✕",
                $sumber,
                "{$halaman}: tombol hapus tidak boleh memakai tanda silang keyboard."
            );
        }

        $pencarian = $this->pencarian();

        $this->assertStringContainsString(
            "import NavIcon from '@/Components/NavIcon.vue';",
            $pencarian,
            'Ikon tombol harus datang dari komponen SVG yang sama dengan sidebar.'
        );

        // Opsi <select> hanya bisa memuat teks, jadi ikon di dalamnya hilang
        // tanpa jejak error.
        $this->assertStringContainsString(
            '<option value="osm">OpenStreetMap</option>',
            $pencarian,
            'Opsi basemap harus teks saja; SVG di dalam option tidak akan dirender.'
        );
    }

    /**
     * Ikon baru harus terdaftar di satu tempat.
     *
     * NavIcon memakai resolveIcon() yang diam-diam mengembalikan ikon lingkaran
     * kalau namanya tidak ditemukan. Ikon yang tidak terdaftar tidak pernah
     * gagal, hanya hilang.
     */
    public function test_nama_ikon_peta_terdaftar(): void
    {
        $pencarian = $this->pencarian();
        $icons = file_get_contents(base_path('resources/js/Navigation/icons.js'));
        $this->assertIsString($icons, 'Tidak bisa membaca resources/js/Navigation/icons.js.');

        preg_match_all('/<NavIcon\s+(?:name|:name)="([a-zA-Z]+)"/', $pencarian, $cocok);
        $dipakai = array_unique($cocok[1]);

        $this->assertNotEmpty($dipakai, 'Halaman peta harus memakai minimal satu ikon NavIcon.');

        foreach ($dipakai as $nama) {
            $this->assertMatchesRegularExpression(
                "/\b{$nama}:/",
                $icons,
                "Ikon \"{$nama}\" dipakai halaman peta tapi tidak terdaftar di icons.js, jadi diam-diam jadi lingkaran."
            );
        }
    }

    private function halamanPeta(string $halaman): string
    {
        $source = file_get_contents(base_path("resources/js/Pages/Peta/{$halaman}.vue"));
        $this->assertIsString($source, "Tidak bisa membaca resources/js/Pages/Peta/{$halaman}.vue.");

        // Sebagian pemeriksaan menempel pada potongan beberapa baris, jadi CRLF
        // di working copy Windows akan membuatnya gagal padahal kodenya benar.
        // Git menormalkan LF saat commit (.gitattributes eol=lf), jadi ini hanya
        // membuang perbedaan yang memang tidak pernah masuk ke repositori.
        return str_replace("\r\n", "\n", $source);
    }

    private function pencarian(): string
    {
        return $this->halamanPeta('MapDenganPencarian');
    }
}
