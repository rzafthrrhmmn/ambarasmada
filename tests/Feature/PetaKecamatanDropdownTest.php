<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\WilayahSulawesiSelatan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Dropdown kecamatan di halaman peta.
 *
 * Dropdown kedua hanya berguna kalau isinya benar-benar milik kabupaten yang
 * dipilih. Kalau daftarnya lepas dari kabupaten, pengguna yang memilih Maros bisa
 * memilih "Rantepao" (Toraja Utara) sehingga peta bergerak ke tempat yang salah dan
 * judul cetakannya jadi tidak sesuai dengan isinya.
 *
 * Cetak diuji karena satu-satunya jalan keluarnya: judul, cakupan, dan nama berkas
 * harus berasal dari wilayah yang dipilih, bukan dari kamera yang kebetulan sedang
 * terlihat.
 */
class PetaKecamatanDropdownTest extends TestCase
{
    use RefreshDatabase;

    private const PAGE = 'resources/js/Pages/Peta/MapDenganPencarian.vue';

    public function test_halaman_peta_mengirim_kecamatan_di_setiap_kabupaten(): void
    {
        $this->actingAs($this->makeUser())
            ->get('/peta')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Peta/MapDenganPencarian')
                ->has('kabupatens', 24)
                ->has('kabupatens.0.kecamatans')
            );
    }

    public function test_maros_menampilkan_kecamatan_miliknya(): void
    {
        $maros = $this->kabupaten('7309');

        $this->assertSame('Maros', $maros['nama_kab']);
        $this->assertCount(14, $maros['kecamatans']);

        $nama = array_column($maros['kecamatans'], 'nama_kec');

        foreach (['Turikale', 'Bontoa', 'Mandai', 'Moncongloe'] as $harusAda) {
            $this->assertContains($harusAda, $nama, "Maros harus punya kecamatan {$harusAda}.");
        }

        // Kecamatan milik kabupaten lain tidak boleh bocor ke daftar Maros.
        foreach (['Rantepao', 'Pangkajene', 'Sengkang'] as $bukanMaros) {
            $this->assertNotContains($bukanMaros, $nama, "Kecamatan {$bukanMaros} bukan milik Maros.");
        }
    }

    public function test_kode_kecamatan_memakai_awalan_kode_kabupaten(): void
    {
        foreach (WilayahSulawesiSelatan::kabupatens() as $kabupaten) {
            $awalan = '73.'.substr($kabupaten['id_kab'], 2).'.';

            foreach ($kabupaten['kecamatans'] as $kecamatan) {
                $this->assertStringStartsWith(
                    $awalan,
                    $kecamatan['id_kec'],
                    "Kecamatan {$kecamatan['nama_kec']} ({$kecamatan['id_kec']}) tidak berkode di bawah {$kabupaten['nama_kab']}."
                );
            }
        }
    }

    public function test_setiap_kabupaten_punya_kecamatan_dan_bbox_yang_wajar(): void
    {
        foreach (WilayahSulawesiSelatan::kabupatens() as $kabupaten) {
            $this->assertNotEmpty(
                $kabupaten['kecamatans'],
                "Dropdown kecamatan untuk {$kabupaten['nama_kab']} akan kosong."
            );

            foreach ($kabupaten['kecamatans'] as $kecamatan) {
                if ($kecamatan['bbox'] === null) {
                    continue;
                }

                [$west, $south, $east, $north] = $kecamatan['bbox'];

                $this->assertLessThan(
                    $east,
                    $west,
                    "BBOX bujur {$kecamatan['nama_kec']} terbalik: barat {$west} >= timur {$east}."
                );
                $this->assertLessThan(
                    $north,
                    $south,
                    "BBOX lintang {$kecamatan['nama_kec']} terbalik: selatan {$south} >= utara {$north}."
                );
            }
        }
    }

    public function test_halaman_kontur_tidak_mengirim_daerah_yang_tidak_dipakai(): void
    {
        // Peta kontur tidak punya pemilih wilayah. Mengirim daftar kabupaten
        // beserta 313 kecamatannya hanya menambah bobot halaman tanpa dipakai.
        $this->actingAs($this->makeUser())
            ->get('/peta/kontur')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Peta/Index')
                ->missing('kabupatens')
            );
    }

    public function test_dropdown_kecamatan_hanya_menampilkan_daftar_kabupaten_aktif(): void
    {
        $source = file_get_contents(base_path(self::PAGE));

        $this->assertStringContainsString(
            'const kecamatanOptions = computed(() => selectedKabData.value?.kecamatans ?? []);',
            $source,
            'Dropdown kecamatan harus diturunkan dari kabupaten yang dipilih, bukan dari daftar seluruh Sulawesi.'
        );

        $this->assertStringContainsString(
            'v-for="kec in kecamatanOptions"',
            $source,
            'Opsi dropdown kedua harus looping daftar kecamatan kabupaten terpilih.'
        );
    }

    public function test_ganti_kabupaten_membuang_kecamatan_lama(): void
    {
        $source = file_get_contents(base_path(self::PAGE));

        $this->assertStringContainsString(
            "selectedKecamatan.value = '';",
            $this->functionBody($source, 'onKabupatenSelect'),
            'Ganti kabupaten harus mengosongkan kecamatan. Kalau tidak, pilihan lama masih tertahan '
                .'meski tidak lagi ada di daftar kabupaten yang baru.'
        );

        $hapus = $this->functionBody($source, 'clearSelection');

        $this->assertStringContainsString("selectedKabupaten.value = '';", $hapus);
        $this->assertStringContainsString(
            "selectedKecamatan.value = '';",
            $hapus,
            'Tombol Hapus harus mengosongkan kabupaten dan kecamatan sekaligus.'
        );
    }

    public function test_dropdown_kecamatan_nonaktif_sampai_kabupaten_dipilih(): void
    {
        $source = file_get_contents(base_path(self::PAGE));

        $this->assertStringContainsString(
            ':disabled="!selectedKabupaten"',
            $source,
            'Dropdown kecamatan harus nonaktif selama belum ada kabupaten, karena isinya belum diketahui.'
        );
    }

    public function test_cetak_memakai_wilayah_terpilih(): void
    {
        $source = file_get_contents(base_path(self::PAGE));

        // Cetak lewat browser mengambil kamera dari bbox wilayah terpilih, bukan
        // dari getCenter()/getZoom() yang isinya bisa sudah digeser pengguna.
        $this->assertStringContainsString(
            'cameraForBounds',
            $source,
            'Cetak harus membingkai peta ke wilayah terpilih supaya judul cetakan tidak berbohong soal isi peta.'
        );

        $this->assertStringContainsString(
            'generatePrintHTML(camera, wilayah)',
            $source,
            'Halaman cetak harus menerima wilayah terpilih untuk judul dan kodenya.'
        );

        $this->assertStringContainsString(
            'Kode wilayah: ${escapeHtml(wilayah.kode)}',
            $source,
            'Cetakan perlu kode wilayah supaya hasil cetak bisa ditelusuri kembali ke wilayah asalnya.'
        );

        // Ekspor PNG memakai nama wilayah yang sama supaya berkas dan judulnya
        // tidak berbeda isi.
        $this->assertStringContainsString(
            'const name = area?.name ?? wilayah?.name ?? \'Peta Kontur Sulawesi Selatan\';',
            $source,
            'Ekspor PNG harus memakai nama wilayah terpilih sebagai judul dan nama berkas.'
        );
    }

    public function test_teks_nama_wilayah_tidak_disisipkan_langsung_ke_html_cetak(): void
    {
        $source = file_get_contents(base_path(self::PAGE));

        $this->assertStringContainsString(
            'const title = wilayah',
            $source,
            'Judul cetakan harus menyesuaikan wilayah terpilih.'
        );

        $this->assertStringNotContainsString(
            '<h1>${title}</h1>',
            $source,
            'Nama wilayah berasal dari data dan harus di-escape sebelum masuk HTML cetakan.'
        );

        $this->assertStringContainsString(
            '<h1>${escapeHtml(title)}</h1>',
            $source,
            'Judul cetakan harus di-escape sebelum ditulis ke dokumen cetak.'
        );
    }

    /**
     * Dialog "Unduh Peta Offline" punya pemilih wilayah sendiri.
     *
     * Kalau dialog ini berhenti di tingkat kabupaten, paket tile untuk offline
     * selalu memuat seluruh kabupaten bahkan ketika yang dibutuhkan peta satu
     * kecamatan. Preview-nya lalu menampilkan potongan berbeda dari yang diunduh,
     * sehingga perkiraan jumlah tile juga meleset.
     */
    public function test_dialog_unduh_offline_memilih_sampai_kecamatan(): void
    {
        $source = file_get_contents(base_path(self::PAGE));

        $this->assertStringContainsString(
            'v-model="selectedOfflineKecamatan"',
            $source,
            'Dialog unduhan offline harus punya pemilih kecamatan.'
        );

        $this->assertStringContainsString(
            ':disabled="!selectedOfflineRegion"',
            $source,
            'Pemilih kecamatan di dialog harus nonaktif sebelum kabupaten dipilih.'
        );

        $this->assertStringContainsString(
            'const offlineKecamatanOptions = computed(() => selectedOfflineRegionData.value?.kecamatans ?? []);',
            $source,
            'Daftar kecamatan pada dialog harus berasal dari kabupaten yang dipilih di dialog.'
        );
    }

    public function test_ganti_kabupaten_di_dialog_membuang_kecamatan_lama(): void
    {
        $source = file_get_contents(base_path(self::PAGE));

        $this->assertStringContainsString(
            "selectedOfflineKecamatan.value = '';",
            $this->watchBody($source, 'selectedOfflineRegion'),
            'Ganti kabupaten di dialog harus mengosongkan kecamatan. Kalau tidak, kode lama '
                .'tetap tertahan dan paket tile diunduh untuk wilayah yang salah.'
        );
    }

    public function test_estimasi_dan_preview_ikuti_wilayah_dialog(): void
    {
        $source = file_get_contents(base_path(self::PAGE));

        // currentBBox menentukan berapa tile yang diunduh, initMiniMap
        // menentukan apa yang dipratinjau. Keduanya harus membaca wilayah yang
        // sama supaya perkiraan dan hasilnya tidak berbeda.
        $this->assertStringContainsString(
            "if (downloadMode.value === 'region' && selectedOfflineWilayah.value?.bbox) {",
            $this->computedBody($source, 'currentBBox'),
            'Cakupan unduhan harus dihitung dari wilayah pilihan di dialog, bukan dari kabupaten saja.'
        );

        $preview = $this->functionBody($source, 'initMiniMap');

        $this->assertStringContainsString(
            'selectedOfflineWilayah.value',
            $preview,
            'Preview harus memakai wilayah pilihan, bukan hanya kabupatennya.'
        );

        $this->assertStringNotContainsString(
            'selectedOfflineRegionData',
            $preview,
            'Preview yang masih membaca data kabupaten mentah mengikuti kabupaten, bukan kecamatan.'
        );

        $this->assertStringContainsString(
            "const wilayah = downloadMode.value === 'region' ? selectedOfflineWilayah.value : null;",
            $this->functionBody($source, 'downloadOffline'),
            'Judul PNG dan paket tile harus memakai wilayah yang sama dari dialog.'
        );
    }

    /**
     * Bentuk wilayah dipakai dua kali: cetak di peta utama dan unduhan offline.
     * Kalau dipisah, nama berkas PNG dan halaman peta offline bisa memakai
     * rumusan berbeda untuk wilayah yang sama.
     */
    public function test_dua_jalur_wilayah_memakai_bentuk_yang_sama(): void
    {
        $source = file_get_contents(base_path(self::PAGE));

        $this->assertStringContainsString(
            'const selectedWilayah = computed(() => wilayahInfo(selectedKabData.value, selectedKecData.value));',
            $source,
            'Peta utama harus memakai wilayahInfo().'
        );

        $this->assertStringContainsString(
            'wilayahInfo(selectedOfflineRegionData.value, selectedOfflineKecData.value)',
            $source,
            'Dialog unduhan harus memakai wilayahInfo() yang sama supaya nama wilayah di '
                .'judul PNG dan di halaman peta offline tidak berbeda.'
        );
    }

    /** Badan satu Arrow function di dalam computed(). */
    private function computedBody(string $source, string $name): string
    {
        $mulai = strpos($source, "const {$name} = computed(");
        $this->assertNotFalse($mulai, "computed {$name} tidak ditemukan di halaman peta.");

        return $this->balancedBody($source, $mulai);
    }

    /** Badan satu watcher, mulai dari watch(nama, ...). */
    private function watchBody(string $source, string $name): string
    {
        $mulai = strpos($source, "watch({$name},");
        $this->assertNotFalse($mulai, "watch({$name}, ...) tidak ditemukan di halaman peta.");

        return $this->balancedBody($source, $mulai);
    }

    /**
     * Ambil isi kurung kurawal yang seimbang mulai dari posisi tertentu.
     *
     * Dipakai supaya komentar di dalam badan tidak membuat pola regex harus
     * tahu urutan baris yang bisa berubah-ubah.
     */
    private function balancedBody(string $source, int $from): string
    {
        $buka = strpos($source, '{', $from);
        $this->assertNotFalse($buka, 'Tubuh tanpa kurung kurawal.');

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

        $this->fail('Tubuh tidak tertutup kurung kurawal.');
    }

    /**
     * Isi badan satu fungsi di <script setup>.
     *
     * Pencocokan kurung kurawal dipakai supaya komentar di dalam badan tidak
     * membuat pola regex harus tahu urutan baris yang bisa berubah-ubah.
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

    private function kabupaten(string $idKab): array
    {
        foreach (WilayahSulawesiSelatan::kabupatens() as $kabupaten) {
            if ($kabupaten['id_kab'] === $idKab) {
                return $kabupaten;
            }
        }

        $this->fail("Kabupaten dengan id {$idKab} tidak ada di sumber data wilayah.");
    }

    private function makeUser(): User
    {
        return User::create([
            'username' => 'peta.kecamatan@test.local',
            'name' => 'Petugas Peta',
            'email' => 'peta.kecamatan@test.local',
            'password' => Hash::make('rahasia12345'),
            'role' => 'Anggota',
            'is_active' => true,
            'status' => 'approved',
            'email_verified_at' => now(),
        ]);
    }
}
