<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\WilayahSulawesiSelatan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Layer batas kecamatan dan sorotannya.
 *
 * Dropdown kecamatan tanpa garis batasnya hanya nama. Yang membuat peta terasa
 * benar adalah kecamatan yang dipilih terlihat diberi tanda, dan cetakan
 * menampilkan garis yang sama. Berkas geojson boleh tidak ada: MapLibre
 * menolak seluruh style kalau source yang dipanggil gagal dimuat, jadi
 * pemuatan batas kecamatan harus dibuat bersyarat dan tidak boleh mematikan peta.
 */
class PetaBatasKecamatanTest extends TestCase
{
    use RefreshDatabase;

    private const PAGE = 'resources/js/Pages/Peta/MapDenganPencarian.vue';

    private const GEOJSON = 'public/storage/maps/batas_kecamatan_sulsel.geojson';

    /**
     * Sebelas kecamatan yang belum punya geometri di sumber batas yang dipakai.
     *
     * Daftar ini dijaga lewat test: kalau sumber batas diperbarui dan jumlah
     * kecamatan yang punya geometri bertambah, test gagal supaya daftar ini ikut
     * diperbarui, bukan diam-diam kedaluwarsa.
     */
    private const TANPA_GEOMETRI = [
        '73.05.10', // Kepulauan Tanakeke (Takalar)
        '73.05.11', // Polongbangkeng Timur (Takalar)
        '73.05.12', // Laikang (Takalar)
        '73.17.05', // Bua Ponrang (Luwu)
        '73.17.22', // Basse Sangtempe Utara (Luwu)
        '73.22.05', // Rongkong (Luwu Utara)
        '73.22.12', // Tana Lili (Luwu Utara)
        '73.22.13', // Sukamaju Selatan (Luwu Utara)
        '73.22.14', // Baebunta Selatan (Luwu Utara)
        '73.22.15', // Sabbang Selatan (Luwu Utara)
        '73.71.15', // Kepulauan Sangkarrang (Makassar)
    ];

    public function test_berkas_geojson_kecamatan_ada_dan_bisa_dibaca(): void
    {
        $this->assertFileExists(
            base_path(self::GEOJSON),
            'Berkas batas kecamatan harus ada di public/ supaya bisa dilayani browser.'
        );

        $geojson = $this->geojson();

        $this->assertSame('FeatureCollection', $geojson['type']);
        $this->assertNotEmpty($geojson['features']);
    }

    public function test_setiap_feature_kecamatan_punya_properti_wajib(): void
    {
        foreach ($this->geojson()['features'] as $feature) {
            $properties = $feature['properties'] ?? [];
            $nama = $properties['nama_kec'] ?? 'tanpa nama';

            foreach (['id_kec', 'nama_kec', 'id_kab', 'nama_kab'] as $kunci) {
                $this->assertArrayHasKey(
                    $kunci,
                    $properties,
                    "Feature batas {$nama} tidak punya properti {$kunci}. Filter sorotan dan popup bergantung pada kunci ini."
                );

                $this->assertNotSame(
                    '',
                    (string) $properties[$kunci],
                    "Properti {$kunci} pada batas {$nama} tidak boleh kosong, karena tidak akan pernah cocok dengan pilihan pengguna."
                );
            }

            $this->assertContains(
                $feature['geometry']['type'] ?? null,
                ['Polygon', 'MultiPolygon'],
                "Batas {$nama} harus berupa Polygon atau MultiPolygon supaya bisa digambar sebagai garis sekaligus diisi."
            );

            $this->assertNotEmpty(
                $feature['geometry']['coordinates'],
                "Batas {$nama} tidak punya koordinat, jadi tidak ada yang bisa digambar."
            );
        }
    }

    public function test_id_kecamatan_pada_geojson_sesuai_dengan_daftar_wilayah(): void
    {
        $diketahui = [];

        foreach (WilayahSulawesiSelatan::kabupatens() as $kabupaten) {
            foreach ($kabupaten['kecamatans'] as $kecamatan) {
                $diketahui[$kecamatan['id_kec']] = [
                    'nama' => $kecamatan['nama_kec'],
                    'id_kab' => $kabupaten['id_kab'],
                    'nama_kab' => $kabupaten['nama_kab'],
                ];
            }
        }

        $this->assertCount(313, $diketahui, 'Daftar wilayah harus memuat 313 kecamatan.');

        $diGeojson = [];

        foreach ($this->geojson()['features'] as $feature) {
            $properties = $feature['properties'];
            $idKec = $properties['id_kec'];

            $this->assertArrayHasKey(
                $idKec,
                $diketahui,
                "Geojson memuat {$properties['nama_kec']} ({$idKec}) yang tidak ada di daftar wilayah, sehingga sorotannya tidak akan pernah cocok."
            );

            ['nama' => $nama, 'id_kab' => $idKab, 'nama_kab' => $namaKab] = $diketahui[$idKec];

            $this->assertSame($nama, $properties['nama_kec'], "Nama {$idKec} berbeda antara geojson dan daftar wilayah.");
            $this->assertSame($idKab, $properties['id_kab'], "Kode kabupaten {$idKec} berbeda antara geojson dan daftar wilayah.");
            $this->assertSame($namaKab, $properties['nama_kab'], "Nama kabupaten {$idKec} berbeda antara geojson dan daftar wilayah.");

            $diGeojson[$idKec] = true;
        }

        $tanpaGeometri = array_keys(array_diff_key($diketahui, $diGeojson));
        sort($tanpaGeometri);

        $harusTanpaGeometri = self::TANPA_GEOMETRI;
        sort($harusTanpaGeometri);

        $this->assertSame(
            $harusTanpaGeometri,
            $tanpaGeometri,
            'Daftar kecamatan yang belum punya geometri berubah. Kalau bertambah, daftar fallback bbox harus ikut diperbarui; kalau berkurang, geojson perlu dibangun ulang.'
        );
    }

    public function test_geojson_kecamatan_muat_ke_cache_service_worker(): void
    {
        $sw = file_get_contents(base_path('public/sw.js'));

        $this->assertStringContainsString(
            "'/storage/maps/batas_kecamatan_sulsel.geojson',",
            $sw,
            'Batas kecamatan harus ikut di-precache. Tanpa itu sorotannya hilang tepat saat paling dibutuhkan, yaitu di perangkat offline.'
        );
    }

    public function test_halaman_peta_mengirim_konfigurasi_batas_kecamatan(): void
    {
        $this->actingAs($this->makeUser())
            ->get('/peta')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Peta/MapDenganPencarian')
                ->has('mapConfig.kecamatanGeojsonUrl')
                ->has('mapConfig.hasKecamatanGeojson')
            );
    }

    /**
     * Peta utama harus menggambar batas kecamatan dan menyorot yang dipilih.
     */
    public function test_peta_utama_menggambar_dan_menyorot_batas_kecamatan(): void
    {
        $source = file_get_contents(base_path(self::PAGE));

        $this->assertStringContainsString(
            "id: 'kecamatan-batas'",
            $source,
            'Batas kecamatan harus punya layer sendiri, terpisah dari batas kabupaten yang jauh lebih kasar.'
        );

        $this->assertStringContainsString(
            "id: 'kecamatan-highlight'",
            $source,
            'Kecamatan yang dipilih harus punya layer sorotan. Tanpa itu peta hanya menampilkan nama tanpa tanda.'
        );

        $this->assertStringContainsString(
            "id: 'kecamatan-highlight-line'",
            $source,
            'Sorotan perlu garis tepi. Kalau hanya isinya yang diberi warna, batas kecamatan pilihan tidak terbaca jelas.'
        );

        // Sorotannya harus berdasarkan kode, bukan nama: nama kecamatan bisa sama
        // dengan kabupaten berbeda dan sering ditulis berbeda ejaannya.
        $this->assertStringContainsString(
            "['==', ['get', 'id_kec'], idKec ?? '']",
            $this->functionBody($source, 'highlightKecamatan'),
            'Sorotan batas kecamatan harus mencocokkan id_kec dari wilayah yang dipilih.'
        );

        $this->assertStringContainsString(
            'highlightKecamatan(kec.id_kec);',
            $this->functionBody($source, 'onKecamatanSelect'),
            'Memilih kecamatan harus langsung menyorot batasnya.'
        );

        $this->assertStringContainsString(
            "highlightKecamatan('');",
            $this->functionBody($source, 'clearSelection'),
            'Tombol Hapus harus membatalkan sorotan. Kalau tidak, peta kembali ke tampilan Sulawesi Selatan sambil masih menyorot satu kecamatan di tengahnya.'
        );
    }

    public function test_layer_kecamatan_hanya_dibuat_saat_berkas_batasnya_ada(): void
    {
        $source = file_get_contents(base_path(self::PAGE));

        $this->assertStringContainsString(
            'const hasKecamatanGeojson = computed(() => props.mapConfig?.hasKecamatanGeojson ?? false);',
            $source,
            'Frontend harus tahu apakah berkas batas kecamatan benar-benar ada di server.'
        );

        $this->assertStringContainsString(
            '...(hasKecamatanGeojson.value',
            $source,
            'Source dan layer kecamatan harus dibuat bersyarat. MapLibre menolak seluruh style kalau source yang dipanggil gagal dimuat, sehingga geojson yang hilang akan mematikan peta, bukan cuma sorotan.'
        );
    }

    /**
     * Sorotan dipanggil saat peta baru dibuat, jadi pemanggilnya belum tentu
     * menemukan layer. Dalam keadaan itu sorotan harus diam, bukan melempar galat.
     */
    public function test_sorotan_aman_kala_layer_belum_ada(): void
    {
        $sorot = $this->functionBody(file_get_contents(base_path(self::PAGE)), 'highlightKecamatan');

        $this->assertStringContainsString(
            'if (map.value.getLayer(\'kecamatan-highlight\')) {',
            $sorot,
            'Sorotan harus memeriksa keberadaan layer sebelum menyetel filternya, dan berhenti begitu tahu layer itu ada.'
        );

        // Menunggu event load hanya berguna saat peta sedang dimuat. Kalau peta
        // sudah selesai dimuat tanpa layer ini, event itu tidak akan pernah
        // datang lagi, jadi memanggilnya hanya meninggalkan penggantung.
        $this->assertStringContainsString(
            'if (!map.value.loaded()) return;',
            $sorot,
            'Setelah peta selesai dimuat tanpa layer kecamatan, sorotan harus berhenti, bukan menunggu event load yang tidak akan datang.'
        );

        $this->assertStringContainsString(
            "if (map.value?.getLayer('kecamatan-highlight')) apply(map.value);",
            $sorot,
            'Penunggu load harus memeriksa ulang layer: peta bisa sudah dibuang, dan getLayer pada peta yang dibuang melempar galat.'
        );
    }

    /**
     * Cetakan lewat browser dan pratinjau unduhan harus menampilkan garis yang
     * sama dengan peta utama. Kalau tidak, hasilnya berbeda dari layar.
     */
    public function test_cetak_dan_pratinjau_memakai_batas_kecamatan(): void
    {
        $source = file_get_contents(base_path(self::PAGE));

        $cetak = $this->functionBody($source, 'generatePrintHTML');

        $this->assertStringContainsString(
            "'kecamatan-highlight'",
            $cetak,
            'Halaman cetak harus menyorot kecamatan yang dipilih. Kalau tidak, judul cetakan menyebut satu kecamatan tanpa ada yang ditandai.'
        );

        // ${JSON.stringify(...)} adalah interpolasi JavaScript, bukan PHP,
        // jadi string-nya harus tunggal agar tidak ditafsirkan sebagai variabel
        // PHP ${JSON}.
        $this->assertStringContainsString(
            'filter: [\'==\', [\'get\', \'id_kec\'], ${JSON.stringify(wilayah.kode)}]',
            $cetak,
            'Sorotan di cetakan harus memakai kode wilayah yang dicetak.'
        );

        $this->assertStringContainsString(
            'const cetakKecamatanLayers = hasKecamatanGeojson.value',
            $cetak,
            'Layer cetak harus dibuat bersyarat, sama seperti peta utama.'
        );

        $this->assertStringContainsString(
            'mini-kecamatan-highlight',
            $this->functionBody($source, 'initMiniMap'),
            'Pratinjau dialog unduhan harus menyorot kecamatan yang dipilih, supaya isinya cocok dengan paket yang diunduh.'
        );
    }

    public function test_service_worker_menerima_url_batas_dan_kode_kecamatan(): void
    {
        $unduhan = $this->functionBody(file_get_contents(base_path(self::PAGE)), 'downloadOffline');

        $this->assertStringContainsString(
            'kecamatanGeojsonUrl: kecamatanGeojsonUrlRelative,',
            $unduhan,
            'URL batas kecamatan harus diteruskan ke service worker supaya halaman peta offline bisa menggambarnya.'
        );

        $this->assertStringContainsString(
            'highlightKecId,',
            $unduhan,
            'Kode kecamatan terpilih harus diteruskan ke service worker supaya bisa disorot di halaman peta offline.'
        );
    }

    public function test_halaman_peta_offline_menggambar_batas_kecamatan(): void
    {
        $sw = file_get_contents(base_path('public/sw.js'));

        $this->assertStringContainsString(
            'async function handleOfflineDownload(port, bbox, zoomMin, zoomMax, pmtilesUrl, geojsonUrl, layoutOptions, areaName, kecamatanGeojsonUrl, highlightKecId)',
            $sw,
            'Penangan unduhan offline harus menerima URL batas kecamatan dan kode yang disorot.'
        );

        $this->assertStringContainsString(
            'async function generateOfflineMapHTML(db, bbox, zoomMin, zoomMax, geojsonUrl, layoutOptions, areaName, kecamatanGeojsonUrl, highlightKecId)',
            $sw,
            'Pembuat halaman peta offline harus meneruskan batas kecamatan ke template HTML.'
        );

        $this->assertStringContainsString(
            "'kecamatan-highlight', type: 'fill', source: 'kecamatan'",
            $sw,
            'Halaman peta offline harus menyorot kecamatan yang diunduh.'
        );

        // Template HTML ditulis ke dalam <script>, jadi nilai yang disisipkan
        // harus berupa literal JSON. Nilai yang disisipkan mentah di dalam string
        // HTML bisa menutup blok script lebih awal dan mematikan seluruh peta.
        $this->assertStringContainsString(
            'kecamatanGeojsonUrl: kecamatanGeojsonUrl ? JSON.stringify(kecamatanGeojsonUrl) : null,',
            $sw,
            'URL dan kode yang masuk ke <script> halaman offline harus ditulis sebagai literal JSON.'
        );

        // activate membandingkan meta tag versi di dalam HTML yang tersimpan.
        // Kalau versi tidak ikut dinaikkan, perangkat yang sudah pernah
        // mengunduh paket akan terus membuka halaman lama yang tidak punya batas
        // kecamatan, dan perubahan di sini tidak akan pernah terlihat.
        $this->assertMatchesRegularExpression(
            "/const OFFLINE_MAP_GENERATOR = 'v([3-9]|[1-9][0-9])';/",
            $sw,
            'Versi generator halaman peta offline harus dinaikkan ke v3 atau lebih tinggi supaya halaman lama dibuang saat activate.'
        );
    }

    /**
     * Kecamatan tanpa geometri harus tetap punya jalan keluar.
     *
     * Fallback ke bbox kabupaten dan status informatif di peta sudah menutupi
     * sebelas kecamatan ini, jadi test yang gagal berarti daftar wilayahnya
     * berubah dan daftar TANPA_GEOMETRI perlu ditinjau lagi.
     */
    public function test_kecamatan_tanpa_geometri_tetap_punya_fallback_bbox_kabupaten(): void
    {
        $tanpaBbox = [];

        foreach (WilayahSulawesiSelatan::kabupatens() as $kabupaten) {
            foreach ($kabupaten['kecamatans'] as $kecamatan) {
                if ($kecamatan['bbox'] !== null) {
                    continue;
                }

                $tanpaBbox[] = $kecamatan['id_kec'];

                $this->assertContains(
                    $kecamatan['id_kec'],
                    self::TANPA_GEOMETRI,
                    "Kecamatan {$kecamatan['nama_kec']} ({$kecamatan['id_kec']}) tidak ada di geojson tetapi tidak dicatat sebagai tanpa geometri."
                );

                $this->assertNotNull(
                    $kabupaten['bbox'],
                    "Kecamatan {$kecamatan['nama_kec']} tidak punya bbox sendiri dan kabupatennya juga tidak, sehingga tidak ada bingkai yang bisa dipakai."
                );
            }
        }

        sort($tanpaBbox);

        $this->assertCount(
            count(self::TANPA_GEOMETRI),
            $tanpaBbox,
            'Jumlah kecamatan tanpa bbox harus sama dengan jumlah kecamatan tanpa geometri. Kalau berbeda, ada kecamatan yang bisa disorot tetapi tidak punya bingkai sendiri, atau sebaliknya.'
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function geojson(): array
    {
        $path = base_path(self::GEOJSON);
        $this->assertFileExists($path, 'Berkas batas kecamatan tidak ada.');

        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Isi badan satu fungsi di <script setup>.
     *
     * Pencocokan kurung kurawal dipakai supaya komentar di dalam badan tidak
     * membuat pola harus tahu urutan baris yang bisa berubah-ubah.
     */
    private function functionBody(string $source, string $name): string
    {
        $mulai = strpos($source, "function {$name}(");
        $this->assertNotFalse($mulai, "Fungsi {$name}() tidak ditemukan di halaman peta.");

        $buka = strpos($source, '{', $mulai);
        $this->assertNotFalse($buka, "Fungsi {$name}() tidak punya badan.");

        $depth = 0;
        $panjang = strlen($source);

        for ($i = $buka; $i < $panjang; $i++) {
            if ($source[$i] === '{') {
                $depth++;
            } elseif ($source[$i] === '}') {
                $depth--;

                if ($depth === 0) {
                    return substr($source, $buka, $i - $buka + 1);
                }
            }
        }

        $this->fail("Badan fungsi {$name}() tidak tertutup.");
    }

    private function makeUser(): User
    {
        return User::create([
            'username' => 'peta.batas@test.local',
            'name' => 'Petugas Peta',
            'email' => 'peta.batas@test.local',
            'password' => Hash::make('rahasia12345'),
            'role' => 'Anggota',
            'is_active' => true,
            'status' => 'approved',
            'email_verified_at' => now(),
        ]);
    }
}
