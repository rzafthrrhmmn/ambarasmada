<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Halaman peta gagal total (layar putih) bila <script setup> melempar
 * ReferenceError: Vue menangkap galat setup(), mengganti render dengan NOOP,
 * lalu komponen tidak menghasilkan apa pun.
 *
 * Test ini menjaga agar setiap binding yang dipakai template dan kode
 * setup benar-benar dideklarasikan.
 */
class PetaPageBindingsTest extends TestCase
{
    private const PAGES = [
        'Peta/MapDenganPencarian.vue',
        'Peta/Index.vue',
    ];

    public static function pageProvider(): array
    {
        return array_map(fn (string $page) => [$page], self::PAGES);
    }

    /** @dataProvider pageProvider */
    public function test_v_model_bindings_are_declared(string $page): void
    {
        [$template, $declared] = $this->parse($page);

        preg_match_all('/\bv-model(?:\.[\w-]+)?="([A-Za-z_$][\w$]*)"/', $template, $matches);

        $this->assertNotEmpty($matches[1], "Tidak ada v-model yang diperiksa di {$page}.");

        foreach (array_unique($matches[1]) as $name) {
            $this->assertContains(
                $name,
                $declared,
                "{$page}: v-model=\"{$name}\" dipakai di template tapi tidak dideklarasikan di <script setup>, sehingga halaman gagal render."
            );
        }
    }

    /** @dataProvider pageProvider */
    public function test_watched_sources_are_declared(string $page): void
    {
        [, $declared] = $this->parse($page);

        $source = file_get_contents($this->path($page));

        preg_match_all('/\bwatch\(\s*([A-Za-z_$][\w$]*)\s*,/', $source, $matches);

        foreach (array_unique($matches[1]) as $name) {
            $this->assertContains(
                $name,
                $declared,
                "{$page}: watch({$name}, ...) memanggil {$name} sebagai identifier di badan setup, padahal tidak ada deklarasinya. Ini ReferenceError saat setup dan membuat halaman putih kosong."
            );
        }
    }

    /** @dataProvider pageProvider */
    public function test_reactive_values_read_in_script_are_declared(string $page): void
    {
        [, $declared] = $this->parse($page);

        $script = $this->scriptBlock(file_get_contents($this->path($page)));

        preg_match_all('/(?<![\w$.])([A-Za-z_$][\w$]*)\.value\b/', $script, $matches);

        $known = array_merge($declared, ['props', 'form', 'sessionForm', 'addForm', 'bulkForm', 'recordForm', 'page', 'filters']);

        foreach (array_unique($matches[1]) as $name) {
            $this->assertContains(
                $name,
                $known,
                "{$page}: \"{$name}.value\" dibaca di <script setup> tapi binding-nya tidak dideklarasikan. Ini ReferenceError dan membuat halaman gagal render."
            );
        }
    }

    public function test_maplibre_stylesheet_is_imported(): void
    {
        $entry = file_get_contents(base_path('resources/js/maplibre.js'));

        $this->assertStringContainsString(
            "import 'maplibre-gl/dist/maplibre-gl.css'",
            $entry,
            'resources/js/maplibre.js harus mengimpor stylesheet MapLibre. Tanpa itu kontainer peta dan seluruh kontroles tidak bergaya sehingga tampak sebagai kotak kosong.'
        );
    }

    public function test_csp_allows_every_configured_tile_host(): void
    {
        $csp = file_get_contents(base_path('app/Http/Middleware/SecurityHeaders.php'));

        // Host tile yang dipakai <select> basemap dan layer hillshade.
        foreach ([
            'tile.openstreetmap.org',
            'server.arcgisonline.com',
            'tile.opentopomap.org',
            'fonts.openmaptiles.org',
            's3.amazonaws.com',
            'tiles.stadiamaps.com',
            'nominatim.openstreetmap.org',
        ] as $host) {
            $this->assertStringContainsString(
                $host,
                $csp,
                "Host tile {$host} tidak ada di CSP, sehingga tile-nya diblokir browser dan peta tampil kosong."
            );
        }
    }

    public function test_csp_does_not_list_the_broken_opentopomap_host(): void
    {
        $csp = file_get_contents(base_path('app/Http/Middleware/SecurityHeaders.php'));

        $this->assertStringNotContainsString(
            'https://tiles.opentopomap.org',
            $csp,
            'Host jamak tiles.opentopomap.org menyajikan sertifikat TLS yang tidak cocok dengan nama hostnya sehingga browser menolak koneksi. Host tunggal tile.opentopomap.org-lah yang sah.'
        );
    }

    /**
     * Style yang punya layer symbol dengan text-field wajib mendeklarasikan
     * glyphs. Tanpa itu MapLibre gagal menyusun shader teks; error-nya uncaught
     * dan render loop berhenti, sehingga kanvas tetap abu-abu walaupun peta,
     * kontur, dan batas kabupaten sudah termuat.
     */
    public function test_style_declares_glyphs_when_symbol_layers_present(): void
    {
        $checked = 0;

        foreach (self::PAGES as $page) {
            $source = file_get_contents($this->path($page));

            if (! preg_match("/type:\s*'symbol'/", $source)) {
                continue;
            }

            $checked++;

            $this->assertStringContainsString(
                "'text-field'",
                $source,
                "{$page}: layer symbol ada, jadi test ini tidak bermakna tanpa text-field."
            );

            $this->assertStringContainsString(
                'glyphs: GLYPHS_URL',
                $source,
                "{$page}: ada layer symbol dengan text-field, jadi style wajib mendeklarasikan glyphs. Tanpa itu shader teks gagal disusun dan kanvas tetap abu-abu."
            );
        }

        $this->assertGreaterThan(
            0,
            $checked,
            'Tidak ada halaman peta yang punya layer symbol, jadi test ini tidak menguji apa pun.'
        );
    }

    public function test_dem_source_uses_a_host_with_a_valid_certificate(): void
    {
        $checked = 0;

        foreach (self::PAGES as $page) {
            $source = file_get_contents($this->path($page));

            // Hanya array tiles: yang diperiksa, bukan komentar.
            preg_match_all('/tiles:\s*\[([^\]]*)\]/', $source, $matches);

            foreach ($matches[1] as $urlList) {
                $checked++;

                $this->assertStringNotContainsString(
                    'tiles.opentopomap.org',
                    $urlList,
                    "{$page}: sumber memakai tiles.opentopomap.org yang sertifikat TLS-nya tidak cocok dengan nama host, sehingga browser menolak koneksi dan hillshade tidak pernah punya data."
                );
            }

            $this->assertStringContainsString(
                "'terrarium'",
                $source,
                "{$page}: DEM Terrarium wajib disertai deklarasi encoding agar MapLibre tahu cara membacanya."
            );
        }

        $this->assertGreaterThan(0, $checked, 'Tidak ada sumber tiles: yang diperiksa, jadi test ini tidak menguji apa pun.');
    }

    public function test_map_is_not_gated_entirely_on_pmtiles(): void
    {
        $source = file_get_contents($this->path('Peta/MapDenganPencarian.vue'));

        $this->assertStringNotContainsString(
            'if (!mapContainer.value || !hasPmtiles.value) return;',
            $source,
            'initMap() tidak boleh berhenti hanya karena PMTiles hilang. Basemap dan batas kabupaten harus tetap bisa digambar.'
        );
    }

    /**
     * @return array{0: string, 1: list<string>} template dan daftar binding yang dideklarasikan
     */
    private function parse(string $page): array
    {
        $source = file_get_contents($this->path($page));

        $templateEnd = strrpos($source, "\n</template>");
        $this->assertNotFalse($templateEnd, "Blok </template> tidak ditemukan di {$page}.");

        $template = substr($source, 0, $templateEnd);
        $script = $this->scriptBlock($source);

        $declared = [];

        preg_match_all('/(?:^|\n)\s*(?:const|let|var)\s+([A-Za-z_$][\w$]*)/', $script, $names);
        $declared = array_merge($declared, $names[1]);

        preg_match_all('/(?:const|let|var)\s*\{([^}]*)\}\s*=/', $script, $destructured);
        foreach ($destructured[1] as $group) {
            foreach (explode(',', $group) as $piece) {
                $name = trim(explode('=', trim(explode(':', $piece)[1] ?? $piece))[0]);
                if (preg_match('/^[A-Za-z_$][\w$]*$/', $name)) {
                    $declared[] = $name;
                }
            }
        }

        preg_match_all('/import\s+(?:([A-Za-z_$][\w$]*)\s*,?\s*)?(?:\{([^}]*)\})?\s*from/', $script, $imports);
        foreach ($imports[1] as $default) {
            if ($default !== '') {
                $declared[] = $default;
            }
        }
        foreach ($imports[2] ?? [] as $group) {
            foreach (explode(',', $group) as $piece) {
                $name = trim(explode(' as ', trim($piece))[1] ?? trim($piece));
                if (preg_match('/^[A-Za-z_$][\w$]*$/', $name)) {
                    $declared[] = $name;
                }
            }
        }

        $declared[] = 'props';

        return [$template, array_values(array_unique($declared))];
    }

    private function scriptBlock(string $source): string
    {
        $start = strpos($source, '<script setup>');

        return $start === false ? '' : substr($source, $start);
    }

    private function path(string $page): string
    {
        return base_path('resources/js/Pages/'.$page);
    }
}
