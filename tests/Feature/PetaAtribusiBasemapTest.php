<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Atribusi basemap dan template cetakan.
 *
 * Dua kegagalan yang disebut di sini sama-sama tidak terlihat di layar:
 *
 * 1. Cetak di Peta/Index pernah menulis ${url} di dalam template tanpa
 *    pernah mendeklarasikan variabel itu. Setiap penekanan tombol melempar
 *    ReferenceError sebelum HTML-nya sempat ditulis, jadi tombolnya rusak total
 *    tanpa jejak di mana pun.
 * 2. Atribusi tile hanya berisi nama pembuatnya. OpenTopoMap memakai CC-BY-SA,
 *    yang mewajibkan sumber data sekaligus lisensinya, jadi "© OpenTopoMap"
 *    saja tidak memenuhi syarat.
 *
 * Ekspor PNG punya masalah serupa yang lebih halus: atribusi MapLibre
 * digambar sebagai elemen DOM di atas kanvas WebGL, jadi elemen itu tidak ikut
 * masuk ke snapshot. PNG yang menulis kredit OpenStreetMap padahal isinya citra
 * satelit berarti menampilkan karya yang tidak dikreditkan.
 */
class PetaAtribusiBasemapTest extends TestCase
{
    private const MODUL = 'resources/js/basemaps.js';

    /** Halaman yang punya template cetakan mandiri. */
    private const HALAMAN_CETAK = [
        'resources/js/Pages/Peta/Index.vue' => 'printMap',
        'resources/js/Pages/Peta/MapDenganPencarian.vue' => 'generatePrintHTML',
    ];

    /** Nama bawaan JavaScript yang boleh dipakai tanpa deklarasi. */
    private const GLOBAL = [
        'JSON', 'Math', 'Date', 'Object', 'Array', 'String', 'Number',
        'Boolean', 'Map', 'Set', 'Promise', 'encodeURIComponent',
    ];

    /**
     * Setiap ${...} di dalam fungsi cetak harus menunjuk binding yang ada.
     *
     * Ini bentuk umum dari galat ${url}: template foramatter is
     * lexicographically valid,Editor tidak Compleains, dan reference error baru
     * muncul saat tombol ditekan di browser. Memeriksa setiap interpolasi
     * terhadap deklarasi di badan fungsi yang sama menangkap kelas galat ini
     * tanpa harus menunggu pengguna menekan tombolnya.
     */
    public function test_setiap_interpolasi_template_cetak_memakai_variabel_yang_dideklarasi(): void
    {
        foreach (self::HALAMAN_CETAK as $page => $function) {
            $source = $this->pageSource($page);
            $body = $this->functionBody($source, $function);

            $declared = $this->declaredNames($source, $function, $body);

            preg_match_all('/\$\{\s*([A-Za-z_$][\w$]*)/', $body, $matches);

            $this->assertNotEmpty(
                $matches[1],
                "{$page}: fungsi {$function}() tidak punya interpolasi template sama sekali, "
                    .'sehingga test ini tidak menguji apa pun.'
            );

            foreach (array_unique($matches[1]) as $name) {
                $this->assertTrue(
                    isset($declared[$name]) || in_array($name, self::GLOBAL, true),
                    "{$page}: fungsi {$function}() menulis \${$name} di dalam template, "
                        ."tapi {$name} tidak dideklarasikan di sana. Memakai template literal tanpa deklarasi "
                        .'melempar ReferenceError saat tombol ditekan, sehingga halaman cetak tidak pernah muncul.'
                );
            }
        }
    }

    /**
     * Atribusi OpenTopoMap harus menyebut sumber data dan lisensinya.
     *
     * Tile-nya dirakit dari OSM dan SRTM, dan tampilan kartunya milik
     * OpenTopoMap. Menyebutkan satu nama saja tidak memenuhi syarat CC-BY-SA.
     */
    public function test_atribusi_opentopomap_memuat_sumber_data_dan_lisensi(): void
    {
        $modul = $this->moduleSource();

        // Teks yang diminta halaman "Verwendung" miliknya, ditulis persis.
        $this->assertStringContainsString(
            "'Kartendaten: © OpenStreetMap-Mitwirkende, SRTM | Kartendarstellung: © OpenTopoMap (CC-BY-SA)'",
            $modul,
            'Atribusi OpenTopoMap harus menyebut data OSM, data SRTM, dan lisensi CC-BY-SA sesuai syarat lisensinya.'
        );

        $this->assertStringNotContainsString(
            "attribution: '© OpenTopoMap'",
            $modul,
            'Atribusi yang hanya menyebut nama OpenTopoMap tidak memenuhi syarat CC-BY-SA.'
        );
    }

    public function test_setiap_basemap_punya_atribusi(): void
    {
        $modul = $this->moduleSource();

        foreach (['osm', 'satellite', 'terrain', 'dark'] as $key) {
            // Pola ini harus melintasi karakter kurung kurawal, karena URL tile
            // memuat placeholder {z}/{x}/{y}. Pola yang berhenti di kurung
            // kurawal pertama tidak pernah mencapai baris attribution.
            $this->assertMatchesRegularExpression(
                "/\\b{$key}:\\s*\\{[\\s\\S]*?attribution:/",
                $modul,
                "Basemap {$key} tidak punya atribusi. Tanpa itu tile-nya tampil tanpa kredit, "
                    .'padahal lisensi sumber tile mewajibkan pengakuan.'
            );
        }

        // Tile OSM adalah karya kontributornya, bukan milik proyek OSM.
        $this->assertStringContainsString(
            "'© OpenStreetMap contributors'",
            $modul,
            'Atribusi OSM wajib menyebut kontributor. "© OpenStreetMap" saja kehilangan sebagian kredit yang diwajibkan tile policy.'
        );
    }

    /**
     * Kedua halaman harus membaca definisi yang sama.
     *
     * Atribusi pernah ditulis inline di dua halaman sekaligus. Setelah salah
     * satu diperbaiki dan yang lain tidak, halaman yang satu menampilkan kredit
     * berbeda dari yang lain untuk tile yang sama, dan tidak ada yang menyadarinya.
     */
    public function test_kedua_halaman_tidak_menulis_definisi_basemap_sendiri(): void
    {
        foreach (['resources/js/Pages/Peta/Index.vue', 'resources/js/Pages/Peta/MapDenganPencarian.vue'] as $page) {
            $source = $this->pageSource($page);

            $this->assertStringContainsString(
                "from '@/basemaps.js'",
                $source,
                "{$page}: basemap harus diambil dari modul bersama supaya teks lisensinya tidak bisa berbeda antar halaman."
            );

            // Definisi tile inline adalah bentuk duplikasi yang harus hilang.
            $this->assertStringNotContainsString(
                "attribution: '© OpenStreetMap'",
                $source,
                "{$page}: atribusi masih ditulis inline. Duplikat seperti inilah yang membuat kredit halaman ini tertinggal dari halaman lain."
            );
        }
    }

    /**
     * Ekspor PNG harus mengikuti basemap yang sedang terlihat.
     */
    public function test_kredit_png_mengikuti_basemap_yang_tampil(): void
    {
        foreach (['resources/js/Pages/Peta/Index.vue', 'resources/js/Pages/Peta/MapDenganPencarian.vue'] as $page) {
            $source = $this->pageSource($page);

            $this->assertStringContainsString(
                '`Sumber: ${basemapAttribution(basemap.value)}, PMTiles Kontur Sulsel`',
                $source,
                "{$page}: kredit PNG harus dihitung dari basemap aktif. MapLibre menulis atribusi ke DOM di atas kanvas WebGL, "
                    .'jadi elemen itu tidak ikut masuk ke snapshot dan PNG harus menulis kreditnya sendiri.'
            );

            $this->assertStringContainsString(
                'sources: sourceCredit.value,',
                $this->functionBody($source, 'printMapPng'),
                "{$page}: exporter PNG harus menerima kredit sumber, kalau tidak kakinya tetap memakai teks tetap yang tidak cocok dengan isinya."
            );
        }

        $export = file_get_contents(base_path('resources/js/Composables/useMapPngExport.js'));

        $this->assertStringContainsString(
            'sources = ',
            $export,
            'buildMapPng harus menerima kredit sumber sebagai opsi.'
        );

        $this->assertStringNotContainsString(
            "fillText('Sumber: OpenStreetMap",
            $export,
            'Kredit PNG tidak boleh lagi ditulis tetap di dalam penggambar: berkas yang berisi citra satelit '
                .'akan tercetak mencantumkan OpenStreetMap yang tidak ada di dalamnya.'
        );
    }

    /**
     * Halaman peta offline dibangun ulang sebagai dokumen sendiri, terpisah dari
     * bundle aplikasi, jadi teks atribusinya tidak bisa ikut dari modul.
     */
    public function test_halaman_peta_offline_memberi_kredit_pada_tile_osm(): void
    {
        $sw = file_get_contents(base_path('public/sw.js'));

        $this->assertMatchesRegularExpression(
            "/'osm':\s*\{[\s\S]*?attribution:\s*'© OpenStreetMap contributors'/",
            $sw,
            'Sumber OSM di halaman peta offline wajib punya attribution. Tanpa itu tile tampil tanpa kredit '
                .'karena MapLibre menulis atribusi hanya dari property source.'
        );
    }

    /**
     * Paket offline yang sudah diunduh masih memakai halaman versi lama.
     *
     * activate membandingkan meta tag versi di dalam HTML yang tersimpan, jadi
     * memperbaiki template tanpa menaikkan versi berarti perbaikan itu tidak
     * pernah sampai ke perangkat yang sudah mengunduh paket.
     */
    public function test_versi_generator_halaman_offline_naik_setiap_perubahan_isinya(): void
    {
        $this->assertMatchesRegularExpression(
            "/const OFFLINE_MAP_GENERATOR = 'v([4-9]|[1-9][0-9])';/",
            file_get_contents(base_path('public/sw.js')),
            'Versi generator harus dinaikkan ke v4 atau lebih tinggi supaya halaman peta offline tanpa kredit dibuang saat activate.'
        );
    }

    /**
     * Host jamak OpenTopoMap punya sertifikat TLS yang rusak.
     *
     * Bentuk yang boleh dipakai tetap host tunggal, dan bentuk tiga-subdomain
     * hanya boleh dipakai kalau CSP mengizinkan ketiga hostnya satu per satu.
     * Test ini menjaga agar keduanya tidak berubah diam-diam.
     */
    public function test_host_tile_opentopomap_tetap_bentuk_yang_sah(): void
    {
        $modul = $this->moduleSource();

        $this->assertStringContainsString(
            "tiles: ['https://tile.opentopomap.org/{z}/{x}/{y}.png']",
            $modul,
            'Host tile OpenTopoMap harus host tunggal yang sertifikatnya sah.'
        );

        // Hanya array tiles: yang diperiksa, bukan komentar. Komentar memang
        // harus menyebut host rusak supaya alasannya tercatat, dan mengikutinya
        // akan membuat test ini selalu gagal pada file yang justru benar.
        preg_match_all('/tiles:\s*\[([^\]]*)\]/', $modul, $matches);

        $this->assertNotEmpty($matches[1], 'Tidak ada array tiles di modul basemap, jadi test ini tidak menguji apa pun.');

        foreach ($matches[1] as $urls) {
            $this->assertStringNotContainsString(
                'tiles.opentopomap.org',
                $urls,
                'Host jamak tiles.opentopomap.org menyajikan sertifikat TLS yang tidak cocok dengan nama hostnya, '
                    .'sehingga browser menolak koneksi dan terrain tidak pernah punya data.'
            );
        }
    }

    /**
     * Tag script di dalam template cetak harus tag sungguhan.
     *
     * Peta/Index menulis tag script dalam bentuk entitas HTML. Browser
     * memperlakukan entitas itu sebagai teks biasa, bukan tag, karena parser
     * HTML tidak mengurai ulang isi teks menjadi markup. Akibatnya seluruh
     * JavaScript cetakan tampil sebagai teks di atas kertas dan kotak peta terisi
     * kosong, tanpa satu pun galat di konsol. Penutupnya tetap boleh di-escape
     * memakai garis miring supaya berkas ini tidak memotong diri sendiri saat
     * diproses.
     */
    public function test_tag_script_di_template_cetak_bukan_teks_ter_escape(): void
    {
        foreach (self::HALAMAN_CETAK as $page => $function) {
            $body = $this->functionBody($this->pageSource($page), $function);

            $this->assertStringContainsString(
                '<script',
                $body,
                "{$page}: fungsi {$function}() harus menulis tag script sungguhan supaya cetakan punya peta."
            );

            $this->assertStringNotContainsString(
                '&lt;script',
                $body,
                "{$page}: tag script ter-escape menjadi teks, bukan markup. Peta hasil cetak tidak akan pernah muncul."
            );
        }
    }

    private function moduleSource(): string
    {
        $path = base_path(self::MODUL);
        $this->assertFileExists($path, 'Modul basemap bersama tidak ditemukan.');

        return (string) file_get_contents($path);
    }

    private function pageSource(string $page): string
    {
        $path = base_path($page);
        $this->assertFileExists($path, "Halaman {$page} tidak ditemukan.");

        return (string) file_get_contents($path);
    }

    /**
     * Nama yang boleh dipakai di dalam satu fungsi.
     *
     * Tiga lapis, karena <script setup> menaruh seluruh isinya pada satu
     * scope modul: nama yang diimpor, deklarasi top-level modul, dan apa pun
     * yang dideklarasikan di dalam badan fungsinya sendiri.
     *
     * Yang sengaja TIDAK ikut dihitung adalah deklarasi yang tertanam di dalam
     * closure lain. `url` pada Peta/Index pernah dideklarasikan di dalam arrow
     * function milik computed geojsonUrl, yang tidak ada hubungannya dengan
     * fungsi cetak. Kalau deklarasi seketus itu ikut dihitung, test ini akan
     * lolos padahal printMap() tetap tidak punya variabel url.
     *
     * @return array<string, true>
     */
    private function declaredNames(string $source, string $function, string $body): array
    {
        $names = [];

        // Impor modul. Yang jadi nama variabel adalah nama setelah "as", dan
        // untuk impor bawaan seperti { ref } namanya sama dengan aslinya.
        preg_match_all('/^import\s+(.+?)\s+from\s/m', $source, $imports);

        foreach ($imports[1] as $clause) {
            $clause = trim($clause);

            preg_match('/\{([^}]*)\}/', $clause, $kurung);

            if ($kurung !== []) {
                foreach (explode(',', $kurung[1]) as $piece) {
                    $piece = trim($piece);

                    if (preg_match('/\bas\s+([A-Za-z_$][\w$]*)/', $piece, $alias) === 1) {
                        $names[$alias[1]] = true;
                    } elseif (preg_match('/^([A-Za-z_$][\w$]*)/', $piece, $match) === 1) {
                        $names[$match[1]] = true;
                    }
                }

                $clause = str_replace($kurung[0], '', $clause);
            }

            foreach (explode(',', $clause) as $piece) {
                $piece = trim($piece);

                if (preg_match('/^([A-Za-z_$][\w$]*)/', $piece, $match) === 1) {
                    $names[$match[1]] = true;
                }
            }
        }

        // Deklarasi top-level modul, yaitu yang ditulis tanpa indentasi.
        preg_match_all('/^(?:const|let|var)\s+([A-Za-z_$][\w$]*)/m', $source, $top);
        preg_match_all('/^(?:async\s+)?function\s+([A-Za-z_$][\w$]*)/m', $source, $topFungsi);

        foreach (array_merge($top[1], $topFungsi[1]) as $name) {
            $names[$name] = true;
        }

        // Deklarasi di dalam badan fungsi yang sedang diperiksa.
        preg_match_all('/\b(?:const|let|var|function)\s+([A-Za-z_$][\w$]*)/', $body, $decl);
        foreach ($decl[1] as $name) {
            $names[$name] = true;
        }

        // Parameter fungsi yang dipanggil, misalnya printMapPng({ silent = false }).
        $mulai = strpos($source, "function {$function}(");
        $this->assertNotFalse($mulai, "Fungsi {$function}() tidak ditemukan.");
        $awal = strpos($source, '(', $mulai);
        $akhir = $this->balanced($source, $awal, '(', ')');

        foreach ($this->parameterNames(substr($source, $awal + 1, $akhir - $awal - 1)) as $name) {
            $names[$name] = true;
        }

        // Arrow function di dalam badan, misalnya const apply = (target) => ...
        preg_match_all('/\(([^)]*)\)\s*=>/', $body, $arrows);
        foreach ($arrows[1] as $parameterList) {
            foreach ($this->parameterNames($parameterList) as $name) {
                $names[$name] = true;
            }
        }

        return $names;
    }

    /**
     * Nama parameter dari satu daftar parameter.
     *
     * Nama yang ditulis sebagai object shorthand seperti { silent = false }
     * ikut diambil, karena itulah nama variabelnya.
     *
     * @return list<string>
     */
    private function parameterNames(string $list): array
    {
        $names = [];

        foreach (explode(',', $list) as $parameter) {
            $parameter = trim(ltrim(trim($parameter), '{'), '}');
            $parameter = preg_replace('/=.*$/s', '', $parameter);
            $parameter = trim($parameter);

            if (preg_match('/^([A-Za-z_$][\w$]*)/', $parameter, $match) === 1) {
                $names[] = $match[1];
            }
        }

        return $names;
    }

    /**
     * Isi badan satu fungsi di <script setup>, kurung kurawal-balanced.
     *
     * Pencarian badan dimulai setelah daftar parameter ditutup, bukan dari
     * kurung kurawal pertama setelah nama fungsi. Fungsi yang parameternya
     * berupa destructuring, misalnya printMapPng({ silent = false } = {}),
     * punya kurung kurawal yang bukan badan, dan pencarian dari sana
     * menghasilkan potongan yang salah.
     */
    private function functionBody(string $source, string $name): string
    {
        $mulai = strpos($source, "function {$name}(");
        $this->assertNotFalse($mulai, "Fungsi {$name}() tidak ditemukan.");

        $parameter = strpos($source, '(', $mulai);
        $tutupParameter = $this->balanced($source, $parameter, '(', ')');

        $badan = strpos($source, '{', $tutupParameter);
        $this->assertNotFalse($badan, "Fungsi {$name}() tidak punya badan.");

        return substr($source, $badan, $this->balanced($source, $badan, '{', '}') - $badan + 1);
    }

    /**
     * Posisi penutup dari satu pasangan kurung yang seimbang.
     */
    private function balanced(string $source, int $from, string $open, string $close): int
    {
        $depth = 0;
        $panjang = strlen($source);

        for ($i = $from; $i < $panjang; $i++) {
            if ($source[$i] === $open) {
                $depth++;
            } elseif ($source[$i] === $close) {
                $depth--;

                if ($depth === 0) {
                    return $i;
                }
            }
        }

        $this->fail("Kurung {$open} mulai di posisi {$from} tidak pernah tertutup.");
    }
}
