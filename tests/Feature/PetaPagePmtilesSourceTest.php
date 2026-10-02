<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Arsip PMTiles Sulawesi Selatan tidak dikirim ke Vercel karena .vercelignore
 * mengecualikan *.pmtiles, sehingga file-nya di-host di Supabase Storage dan
 * dibaca browser lewat HTTP Range.
 *
 * Konsekuensinya ada tiga: URL yang dikirim ke frontend harus absolut,
 * hasPmtiles tidak boleh bergantung pada file_exists() lokal, dan CSP harus
 * mengizinkan host tempat arsip berada.
 */
class PetaPagePmtilesSourceTest extends TestCase
{
    use RefreshDatabase;

    private const REMOTE_URL = 'https://contoh-project.supabase.co/storage/v1/object/public/maps/sulsel_kontur.pmtiles';

    protected function setUp(): void
    {
        parent::setUp();

        // Setiap test memulai dari kondisi tanpa PMTILES_URL, sama seperti
        // server produksi sebelum variabel itu diisi.
        config(['map.pmtiles_url' => null]);
    }

    public function test_remote_url_is_used_when_configured(): void
    {
        config(['map.pmtiles_url' => self::REMOTE_URL]);

        $this->actingAs($this->makeUser())
            ->get('/peta')
            ->assertInertia(fn (Assert $page) => $page
                ->where('mapConfig.pmtilesUrl', self::REMOTE_URL)
                ->where('mapConfig.hasPmtiles', true)
            );
    }

    public function test_remote_url_is_used_on_kontur_page_too(): void
    {
        config(['map.pmtiles_url' => self::REMOTE_URL]);

        $this->actingAs($this->makeUser())
            ->get('/peta/kontur')
            ->assertInertia(fn (Assert $page) => $page
                ->where('mapConfig.pmtilesUrl', self::REMOTE_URL)
                ->where('mapConfig.hasPmtiles', true)
            );
    }

    public function test_local_file_is_used_when_no_remote_url_configured(): void
    {
        config(['map.pmtiles_url' => null]);

        $expected = asset(config('map.pmtiles_path'));

        $this->actingAs($this->makeUser())
            ->get('/peta')
            ->assertInertia(fn (Assert $page) => $page
                ->where('mapConfig.pmtilesUrl', $expected)
                ->where('mapConfig.hasPmtiles', file_exists(public_path(config('map.pmtiles_path'))))
            );
    }

    public function test_has_pmtiles_is_true_even_without_local_file(): void
    {
        // Kombinasi yang terjadi di produksi: .vercelignore membuang berkas
        // lokal, jadi file_exists() salah, padahal arsipnya ada di Supabase.
        config([
            'map.pmtiles_url' => self::REMOTE_URL,
            'map.pmtiles_path' => 'storage/maps/tidak-ada-di-server.pmtiles',
        ]);

        $this->assertFileDoesNotExist(public_path(config('map.pmtiles_path')));

        $this->actingAs($this->makeUser())
            ->get('/peta')
            ->assertInertia(fn (Assert $page) => $page->where('mapConfig.hasPmtiles', true));
    }

    public function test_peta_requires_authentication(): void
    {
        config(['map.pmtiles_url' => self::REMOTE_URL]);

        $this->get('/peta')->assertRedirect();
    }

    public function test_csp_allows_supabase_storage_origin(): void
    {
        $response = $this->actingAs($this->makeUser())->get('/peta');

        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString(
            'https://*.supabase.co',
            $csp,
            'connect-src harus mengizinkan Supabase Storage, jika tidak HTTP Range ke arsip PMTiles diblokir dan layer kontur kosong.'
        );
    }

    public function test_csp_allows_the_exact_configured_host(): void
    {
        config(['map.pmtiles_url' => 'https://cdn.example.test/maps/kontur.pmtiles']);

        $csp = $this->actingAs($this->makeUser())->get('/peta')->headers->get('Content-Security-Policy');

        $this->assertStringContainsString('https://cdn.example.test', $csp);
    }

    public function test_frontend_keeps_the_pmtiles_url_absolute(): void
    {
        foreach (['Peta/MapDenganPencarian.vue', 'Peta/Index.vue'] as $page) {
            $source = file_get_contents(base_path('resources/js/Pages/'.$page));

            $this->assertSame(
                1,
                preg_match('/const pmtilesSourceUrl = computed\(\(\) => `pmtiles:\/\/\$\{([^}]+)\}`\);/', $source, $matches),
                "{$page}: pmtilesSourceUrl harus menyisipkan URL arsip apa adanya ke skema pmtiles://."
            );

            $this->assertStringNotContainsString(
                '.replace(',
                $matches[0],
                "{$page}: pmtilesSourceUrl tidak boleh mengubah URL. Pustaka pmtiles mengambil byte arsip dengan HTTP Range, jadi URL absolut dari Supabase wajib dipertahankan."
            );
        }
    }

    public function test_offline_download_passes_absolute_pmtiles_url(): void
    {
        $source = file_get_contents(base_path('resources/js/Pages/Peta/MapDenganPencarian.vue'));

        $this->assertStringContainsString(
            'const pmtilesUrl = props.mapConfig.pmtilesUrl;',
            $source,
            'downloadOffline() harus meneruskan URL absolut ke service worker, jika tidak reader PMTiles di service worker diambil dari host yang salah.'
        );
    }

    public function test_service_worker_accepts_multi_segment_pmtiles_path(): void
    {
        $sw = file_get_contents(base_path('public/sw.js'));

        $this->assertStringContainsString(
            '/^\/(.*\.pmtiles)\/(\d+)\/(\d+)\/(\d+)\.(pbf|mvt)$/',
            $sw,
            'Pola pmtiles:// di service worker harus menerima path bersegmen banyak. Path Supabase (/storage/v1/object/public/maps/...) ditolak pola satu segmen.'
        );
    }

    private function makeUser(): User
    {
        return User::create([
            'username' => 'peta.pmtiles@test.local',
            'name' => 'Petugas Peta',
            'email' => 'peta.pmtiles@test.local',
            'password' => Hash::make('rahasia12345'),
            'role' => 'Anggota',
            'is_active' => true,
            'status' => 'approved',
            'email_verified_at' => now(),
        ]);
    }
}
