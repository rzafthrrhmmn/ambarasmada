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
}
