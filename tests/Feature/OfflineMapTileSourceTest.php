<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Peta yang diunduh untuk dipakai offline tidak pernah menampilkan tile.
 *
 * Peta offline dibangun sebagai HTML mandiri di dalam service worker. Semula
 * sumber tile-nya memakai skema kustom pmtiles:// plus pustaka pmtiles dari CDN.
 * Kombinasi itu tidak bisa bekerja karena dua hal:
 *
 * 1. Pustaka pmtiles yang disematkan (3.1.4) tidak ada di unpkg, sehingga
 *    global PMTiles undefined dan seluruh script inline berhenti di baris
 *    `new PMTiles.Protocol()`. Gejalanya persis seperti error CORS yang pernah
 *    dilaporkan: browser melaporkan 404 lintas origin tanpa header
 *    Access-Control-Allow-Origin sebagai kegagalan CORS.
 *
 * 2. Sumber vector dengan `url:` membuat MapLibre meminta TileJSON ke URL
 *    tersebut, sedangkan penangan di service worker hanya melayani pola
 *    /{z}/{x}/{y}. Dan Protocol pmtiles membaca arsip lewat HTTP Range ke URL
 *    arsip, bukan dari IndexedDB tempat tile offline disimpan.
 *
 * Tile kini dilayani lewat path HTTP same-origin yang dijawab service worker
 * dari IndexedDB, dan sumber tile memakai `tiles:` supaya TileJSON tidak
 * diperlukan.
 */
class OfflineMapTileSourceTest extends TestCase
{
    private function sw(): string
    {
        return file_get_contents(base_path('public/sw.js'));
    }

    /** @return list<string> */
    private function literals(string $source): array
    {
        preg_match_all("/'([^'\n]*)'/", $source, $matches);

        return $matches[1];
    }

    public function test_offline_map_does_not_load_pmtiles_from_cdn(): void
    {
        $this->assertStringNotContainsString(
            'pmtiles/dist/pmtiles.js',
            $this->sw(),
            'Semua build pmtiles berversi di unpkg menjawab 404 sehingga hanya menghasilkan error CORS. Peta offline tidak butuh pustaka itu karena tile diambil dari IndexedDB.'
        );
    }

    public function test_offline_map_does_not_depend_on_pmtiles_global(): void
    {
        $sw = $this->sw();

        $this->assertStringNotContainsString(
            'new PMTiles.Protocol()',
            $sw,
            'PMTiles tidak lagi dimuat, jadi pemanggilan ini akan menghentikan seluruh script inline peta offline.'
        );

        $this->assertStringNotContainsString(
            "addProtocol('pmtiles'",
            $sw,
            'Skema pmtiles: dihapus karena tile offline tidak dibaca dari arsip PMTiles.'
        );
    }

    public function test_offline_tile_source_uses_template_instead_of_tilejson(): void
    {
        $this->assertStringContainsString(
            "'/offline-tiles/{z}/{x}/{y}.pbf'",
            $this->sw(),
            'Sumber tile offline harus memakai template tiles: agar MapLibre tidak perlu TileJSON.'
        );
    }

    public function test_offline_tile_pattern_is_shared_by_route_and_handler(): void
    {
        $sw = $this->sw();

        $this->assertSame(
            1,
            preg_match(
                '/const OFFLINE_TILE_PATTERN = (\/.*\/);/',
                $sw,
                $matches
            ),
            'Konstanta OFFLINE_TILE_PATTERN tidak ditemukan di public/sw.js.'
        );

        $pattern = $matches[1];

        // Rute di event fetch dan penangan tile harus memakai pola yang sama,
        // kalau tidak tile yang tidak cocok diam-diam jatuh ke cache dan 502.
        $this->assertSame(
            1,
            preg_match(
                '/OFFLINE_TILE_PATTERN\.test\(url\.pathname\)/',
                $sw
            ),
            'Rute tile offline harus diuji dengan OFFLINE_TILE_PATTERN.'
        );

        $this->assertSame(
            1,
            preg_match(
                '/match\(OFFLINE_TILE_PATTERN\)/',
                $sw
            ),
            'handleOfflineTileRequest harus memakai OFFLINE_TILE_PATTERN.'
        );

        // Pola harus menangkap tepat tiga grup: z, x, y.
        $regex = '/^\/offline-tiles\/(\d+)\/(\d+)\/(\d+)\.pbf$/';
        $this->assertSame(
            1,
            preg_match($pattern, '/offline-tiles/12/2048/1362.pbf', $groups),
            'Pola harus cocok dengan path tile yang diminta MapLibre.'
        );
        $this->assertSame(['12', '2048', '1362'], array_slice($groups, 1));

        $this->assertSame(0, preg_match($pattern, '/offline-tiles/abc/1/1.pbf'));
        $this->assertSame(0, preg_match($pattern, '/offline-tiles/1/1/1.json'));
    }

    public function test_offline_tile_route_only_serves_same_origin_get(): void
    {
        $this->assertStringContainsString(
            'if (OFFLINE_TILE_PATTERN.test(url.pathname)) {',
            $this->sw()
        );

        // Rute tile harus berada setelah penjaga same-origin, supaya request
        // lintas origin tidak ikut dilayani dari IndexedDB.
        $originGuard = strpos($this->sw(), 'url.origin !== location.origin');
        $tileRoute = strpos($this->sw(), 'OFFLINE_TILE_PATTERN.test');

        $this->assertIsInt($originGuard);
        $this->assertIsInt($tileRoute);
        $this->assertGreaterThan(
            $originGuard,
            $tileRoute,
            'Rute tile offline harus berada setelah penjaga same-origin.'
        );
    }

    public function test_offline_map_uses_the_precached_maplibre_version(): void
    {
        $sw = $this->sw();

        // EXTERNAL_LIBS yang di-precache harus versi yang sama dengan yang
        // dimuat template HTML offline, kalau tidak peta gagal saat offline.
        preg_match_all(
            "/'(https:\/\/unpkg\.com\/maplibre-gl@[^']+)'/",
            $sw,
            $matches
        );

        $versions = array_map(
            static fn (string $url): string => (string) preg_replace(
                '/^.*maplibre-gl@/',
                '',
                preg_replace('/\/(dist\/.*|\.js|\.css)$/', '', $url)
            ),
            $matches[1]
        );

        $versions = array_values(array_unique($versions));

        $this->assertCount(
            1,
            $versions,
            'Template peta offline dan EXTERNAL_LIBS harus memakai versi maplibre-gl yang sama, sekarang ada: '
                .implode(', ', $versions)
        );
    }

    public function test_offline_map_text_has_no_mojibake(): void
    {
        $raw = file_get_contents(base_path('public/sw.js'));

        // Emoji dan tanda baca non-ASCII pernah tersimpan sebagai UTF-8 yang
        // dibaca Latin-1, sehingga tampil rusak di peta offline.
        $mojibake = [
            'â€“', // en-dash
            'â€',  // smart quote
            'Ã©',
            'Â°',
        ];

        foreach ($mojibake as $sequence) {
            $this->assertStringNotContainsString(
                $sequence,
                $raw,
                "public/sw.js masih berisi urutan byte salah decode: {$sequence}"
            );
        }

        $this->assertSame(
            0,
            preg_match('/[\x{00C2}-\x{00C3}][\x{0080}-\x{00BF}]/u', $raw),
            'public/sw.js masih punya karakter hasil salah decode.'
        );
    }

    public function test_offline_source_declares_zoom_range(): void
    {
        $sw = $this->sw();

        // Tanpa minzoom/maxzoom, MapLibre tidak tahu rentang tile yang boleh
        // diminta dari IndexedDB dan sumber gagal dimuat.
        $this->assertStringContainsString('minzoom: ${zoomMin}', $sw);
        $this->assertStringContainsString('maxzoom: ${zoomMax}', $sw);
    }

    /**
     * Template HTML untuk cetak dan ekspor berdiri sendiri di luar bundle
     * aplikasi, jadi MapLibre dan pmtiles diambil dari CDN.
     */
    public function test_print_export_templates_load_an_existing_pmtiles_build(): void
    {
        foreach ($this->printTemplates() as $page) {
            foreach ($this->cdnUrls($page) as $url) {
                $this->assertDoesNotMatchRegularExpression(
                    '#pmtiles@[\d.]+#',
                    $url,
                    $page.': pmtiles tidak pernah dipublikasikan di unpkg. Build berversi selalu 404 sehingga PMTiles undefined, seluruh script inline berhenti, dan peta cetak kosong.'
                );
            }

            $this->assertContains(
                'https://unpkg.com/pmtiles/dist/pmtiles.js',
                $this->cdnUrls($page),
                $page.': harus memakai build pmtiles tanpa versi yang memang ada di unpkg.'
            );
        }
    }

    public function test_print_export_templates_use_the_configured_archive(): void
    {
        foreach ($this->printTemplates() as $page) {
            $this->assertStringNotContainsString(
                'pmtiles:///storage/maps/',
                $page,
                $page.': nama arsip lama di-hardcode. Arsip produksi berada di Supabase Storage, jadi peta cetak selalu gagal memuat kontur.'
            );

            $this->assertStringContainsString(
                'url: \'${pmtilesSourceUrl.value}\'',
                $page,
                $page.': sumber kontur harus memakai URL arsip dari mapConfig.'
            );

            $this->assertStringContainsString(
                'data: \'${url}\'',
                $page,
                $page.': sumber batas harus memakai URL geojson dari mapConfig.'
            );
        }
    }

    /**
     * URL CDN yang benar-benar dimuat template.
     *
     * Hanya atribut src dan href, supaya keterangan versi di komentar tidak
     * ikut terperiksa.
     *
     * @return list<string>
     */
    private function cdnUrls(string $source): array
    {
        preg_match_all(
            '/(?:src|href)="(https:\/\/unpkg\.com\/[^"]+)"/',
            $source,
            $matches
        );

        return array_values(array_unique($matches[1]));
    }

    /** @return array<string, string> */
    private function printTemplates(): array
    {
        $pages = [
            'resources/js/Pages/Peta/Index.vue',
            'resources/js/Pages/Peta/MapDenganPencarian.vue',
        ];

        $templates = [];

        foreach ($pages as $page) {
            $source = file_get_contents(base_path($page));
            $this->assertIsString($source, "Tidak bisa membaca {$page}.");

            if (str_contains($source, "container: 'print-map'")) {
                $templates[$page] = $source;
            }
        }

        $this->assertNotEmpty(
            $templates,
            'Tidak ditemukan template peta cetak di komponen mana pun.'
        );

        return $templates;
    }
}
