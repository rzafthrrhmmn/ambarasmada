<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Peta pernah kosong total karena MapLibre menolak seluruh style.
 *
 * Dua layer symbol menaruh properti paint di dalam blok layout, dan memakai
 * 'text-fill' yang tidak ada di spesifikasi MapLibre. MapLibre memvalidasi
 * style saat constructing map: satu properti tidak dikenal membuat style
 * ditolak seluruhnya, jadi tidak ada satu pun tile yang diminta dan kanvas
 * tetap kosong.
 *
 * Gejalanya sangat menyesatkan: WebGL hidup, kontrol peta berfungsi, marker
 * geolokasi muncul sebagai elemen DOM, console bersih karena handler error
 * hanya menulis ke ref, dan tidak ada request tile sama sekali.
 */
class MapStyleValidationTest extends TestCase
{
    /**
     * Properti paint yang paling sering diletakkan di layout.
     *
     *_MS_GLYPHS tidak termasuk: nama itu tidak pernah ada di spesifikasi.
     *
     * @var list<string>
     */
    private const PAINT_ONLY = [
        'text-color',
        'text-opacity',
        'text-halo-color',
        'text-halo-width',
        'text-halo-blur',
        'text-halo-opacity',
        'fill-color',
        'fill-opacity',
        'fill-outline-color',
        'fill-pattern',
        'line-color',
        'line-width',
        'line-opacity',
        'line-gap-width',
        'line-dasharray',
        'line-offset',
        'icon-color',
        'icon-opacity',
        'icon-halo-color',
        'icon-halo-width',
        'raster-opacity',
        'raster-saturation',
        'raster-contrast',
        'raster-brightness-min',
        'raster-brightness-max',
        'hillshade-illumination-direction',
        'hillshade-illumination-anchor',
        'hillshade-exaggeration',
        'hillshade-shadow-color',
        'hillshade-highlight-color',
        'hillshade-accent-color',
        'background-color',
        'background-pattern',
        'circle-color',
        'circle-radius',
        'fill-extrusion-color',
    ];

    /** @return list<string> */
    private function pages(): array
    {
        return [
            'resources/js/Pages/Peta/MapDenganPencarian.vue',
            'resources/js/Pages/Peta/Index.vue',
        ];
    }

    private function source(string $page): string
    {
        $path = base_path($page);
        $this->assertFileExists($path, "File {$page} tidak ditemukan.");

        // Blok layout dibandingkan sebagai potongan beberapa baris, jadi CRLF di
        // working copy Windows akan membuatnya gagal padahal bloknya benar. Git
        // menormalkan LF saat commit (.gitattributes eol=lf), jadi menormalkan di
        // sini hanya membuang perbedaan yang memang tidak masuk ke repositori.
        return str_replace("\r\n", "\n", (string) file_get_contents($path));
    }

    /**
     * Ekstrak isi setiap blok `layout: { ... }` dengan pencocokan kurung.
     *
     * @return list<string>
     */
    private function layoutBlocks(string $source): array
    {
        $blocks = [];
        $offset = 0;

        while (preg_match('/\blayout\s*:\s*\{/', $source, $m, PREG_OFFSET_CAPTURE, $offset)) {
            $start = $m[0][1] + strlen($m[0][0]) - 1;
            $depth = 0;
            $len = strlen($source);

            for ($i = $start; $i < $len; $i++) {
                if ($source[$i] === '{') {
                    $depth++;
                } elseif ($source[$i] === '}') {
                    $depth--;

                    if ($depth === 0) {
                        $blocks[] = substr($source, $start + 1, $i - $start - 1);
                        $offset = $i;

                        break;
                    }
                }
            }

            if (! isset($source[$start])) {
                break;
            }
        }

        return $blocks;
    }

    public function test_layout_blocks_contain_no_paint_properties(): void
    {
        foreach ($this->pages() as $page) {
            $blocks = $this->layoutBlocks($this->source($page));

            $this->assertNotEmpty($blocks, "{$page}: tidak ditemukan blok layout untuk diperiksa.");

            foreach ($blocks as $block) {
                foreach (self::PAINT_ONLY as $property) {
                    $this->assertStringNotContainsString(
                        $property,
                        $block,
                        "{$page}: '{$property}' adalah properti paint, bukan layout. "
                            .'MapLibre menolak seluruh style kalau properti ini ada di blok layout.'
                    );
                }
            }
        }
    }

    public function test_style_never_uses_text_fill(): void
    {
        foreach ($this->pages() as $page) {
            $this->assertStringNotContainsString(
                "'text-fill'",
                $this->source($page),
                "{$page}: tidak ada properti text-fill di spesifikasi MapLibre. Yang benar text-color."
            );
        }
    }

    public function test_symbol_layers_declare_glyphs(): void
    {
        foreach ($this->pages() as $page) {
            $source = $this->source($page);

            // Halaman yang punya layer symbol wajib punya glyphs, karena MapLibre
            // menyusun shader teks dari fontstack.
            if (! str_contains($source, "type: 'symbol'")) {
                continue;
            }

            $this->assertStringContainsString(
                'glyphs:',
                $source,
                "{$page}: ada layer symbol tanpa properti glyphs."
            );
        }
    }

    public function test_map_errors_reach_the_console(): void
    {
        foreach ($this->pages() as $page) {
            $handlers = $this->errorHandlers($this->source($page));

            foreach ($handlers as $index => $handler) {
                // Dicek per handler. Kalau hanya dicek per halaman, satu handler
                // yang punya console.error cukup membuat halaman
                // ini lolos padahal handler lainnya menelan error.
                $this->assertStringContainsString(
                    'console.error(',
                    $handler,
                    "{$page}: handler error peta ke-".($index + 1).' menulis ke ref tanpa '
                        .'menulis ke console. Style yang ditolak MapLibre tidak punya jejak.'
                );
            }
        }
    }

    /**
     * Ekstrak isi setiap handler `.on('error', ...)`.
     *
     * @return list<string>
     */
    private function errorHandlers(string $source): array
    {
        $handlers = [];
        $offset = 0;

        while (preg_match("/\.on\(\s*'error'/", $source, $m, PREG_OFFSET_CAPTURE, $offset)) {
            $start = $m[0][1];
            $depth = 0;
            $len = strlen($source);
            $started = false;

            for ($i = $start; $i < $len; $i++) {
                if ($source[$i] === '(') {
                    $depth++;
                    $started = true;
                } elseif ($source[$i] === ')') {
                    $depth--;

                    if ($started && $depth === 0) {
                        $handlers[] = substr($source, $start, $i - $start + 1);
                        $offset = $i;

                        break;
                    }
                }
            }

            if ($i >= $len) {
                break;
            }
        }

        return $handlers;
    }
}
