<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Penjaga untuk perbaikan di sisi peramban pada halaman persuratan.
 *
 * sebagian masalah di sini tidak terlihat oleh pengujian PHP: Inertia hanya
 * mengirim permintaan, sehingga pratinjau yang selalu ditolak 419, pencarian
 * yang menembak permintaan tiap ketikan, dan tautan ekspor yang tetap bisa
 * diklik walau ditandai tidak tersedia baru terasa saat halaman dibuka di
 * peramban.
 */
class LetterPageSourceTest extends TestCase
{
    private const INDEX = 'Letters/Index.vue';

    private const SHOW = 'Letters/Show.vue';

    private const PRINT = 'Letters/Print.vue';

    /**
     * Token CSRF harus diambil dari meta tag.
     *
     * Cookie XSRF-TOKEN disimpan dalam bentuk terenkripsi oleh EncryptCookies,
     * jadi isinya tidak sama dengan token di sesi. Mengirimkannya apa adanya
     * sebagai X-CSRF-TOKEN membuat setiap permintaan pratinjau ditolak 419.
     */
    public function test_token_csrf_diambil_dari_meta_tag(): void
    {
        $source = $this->source(self::INDEX);

        $this->assertStringContainsString(
            'meta[name="csrf-token"]',
            $source,
            'Halaman surat harus mengambil token CSRF dari meta tag, bukan dari cookie XSRF-TOKEN.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/document\.cookie/',
            $source,
            'Halaman surat tidak boleh membaca token CSRF dari cookie; cookie XSRF-TOKEN berisi nilai terenkripsi.'
        );
    }

    /**
     * Tanpa jeda, satu ketikan pada kolom pencarian menembak satu permintaan
     * ke server dan responsnya bisa tiba tidak berurutan.
     */
    public function test_pencarian_dijeda_sebelum_mengirim_permintaan(): void
    {
        $source = $this->source(self::INDEX);

        $this->assertStringContainsString(
            'setTimeout',
            $source,
            'Filter pencarian harus dijeda supaya tidak menembak permintaan pada setiap ketikan.'
        );

        $this->assertMatchesRegularExpression(
            '/watch\(\s*filters[\s\S]{0,900}router\.get\(/',
            $source,
            'Panggilan router.get untuk filter harus berada di dalam watcher yang dijeda.'
        );
    }

    /**
     * toISOString() memakai UTC, sehingga sebelum pukul 07.00 WITA tanggal
     * bawaan surat sudah maju satu hari.
     */
    public function test_tanggal_bawaan_memakai_waktu_lokal(): void
    {
        $source = $this->source(self::INDEX);

        $this->assertDoesNotMatchRegularExpression(
            '/toISOString\(\)\.slice/',
            $source,
            'Tanggal bawaan surat harus dihitung dari waktu lokal peramban, bukan UTC.'
        );

        $this->assertStringContainsString('getFullYear', $source);
    }

    /**
     * Atribut disabled tidak berlaku pada <a>, jadi tombol ekspor tetap bisa
     * diklik walaupun surat belum bisa diekspor.
     */
    public function test_tautan_ekspor_yang_tidak_tersedia_tidak_bisa_diklik(): void
    {
        $source = $this->source(self::SHOW);

        $this->assertDoesNotMatchRegularExpression(
            '/<a\b[^>]*\s:disabled=/',
            $source,
            'Atribut disabled tidak berlaku pada tautan; pakai aria-disabled dan kelas pointer-events-none.'
        );

        $this->assertStringContainsString(
            'aria-disabled',
            $source,
            'Tautan ekspor yang tidak bisa dipakai harus ditandai dengan aria-disabled.'
        );
    }

    /**
     * Halaman cetak menerima penjelasan saat template tidak terbaca. Tanpa
     * deklarasi prop-nya, pesan dari server tidak pernah ditampilkan.
     */
    public function test_halaman_cetak_menerima_pesan_template_tidak_terbaca(): void
    {
        $this->assertStringContainsString(
            'problem',
            $this->source(self::PRINT),
            'Halaman cetak harus menampilkan pesan ketika template tidak dapat dibaca.'
        );

        $this->assertStringContainsString(
            "'problem' => \$problem",
            $this->source('LetterController.php'),
            'LetterController::print() harus mengirim pesan masalah ke halaman cetak.'
        );
    }

    /**
     * Daftar penanda inti harus hanya ada di server.
     *
     * Sempat ada salinan daftar itu di halaman surat, dan salinannya sudah
     * berbeda dari aslinya: `${tanggal}` dan `${nama_ambalan}` masih diminta
     * sebagai isian tambahan padahal sudah punya kolom di formulir utama.
     */
    public function test_daftar_penanda_inti_tidak_diduaikan_di_peramban(): void
    {
        $source = $this->source(self::INDEX);

        $this->assertStringContainsString(
            '.core',
            $source,
            'Halaman surat memakai penanda "core" dari server untuk menentukan isian tambahan.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/const\s+(CORE_PLACEHOLDERS|ALIASES)\s*=/',
            $source,
            'Daftar penanda inti dan alias harus hidup di LetterValues, bukan di halaman surat.'
        );
    }

    private function source(string $file): string
    {
        $path = $file === 'LetterController.php'
            ? app_path('Http/Controllers/'.$file)
            : resource_path('js/Pages/'.$file);

        $this->assertFileExists($path);

        return (string) file_get_contents($path);
    }
}
