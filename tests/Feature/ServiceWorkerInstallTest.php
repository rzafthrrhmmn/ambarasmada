<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Service worker tidak pernah berhasil instalasi karena satu aset precache
 * gagal diambil.
 *
 * Cache.addAll bersifat atomik: satu URL yang gagal membuat promise menolak,
 * dan karena ada di dalam event.waitUntil(), instalasi service worker ikut
 * gagal. Akibatnya versi baru tidak pernah aktif, versi lama terus
 * mengendalikan halaman, dan tidak ada pembaruan yang bisa sampai ke pengguna
 * tanpa mereka menghapus cache secara manual.
 *
 * Kasus nyata: /robots.txt ada di public/ tetapi tidak dipetakan di routes
 * vercel.json, jadi jatuh ke catch-all Laravel dan menjawab 404.
 */
class ServiceWorkerInstallTest extends TestCase
{
    private function sw(): string
    {
        return file_get_contents(base_path('public/sw.js'));
    }

    public function test_install_does_not_use_atomic_add_all(): void
    {
        $this->assertStringNotContainsString(
            'cache.addAll(',
            $this->sw(),
            'cache.addAll bersifat atomik sehingga satu aset yang gagal menggagalkan seluruh instalasi service worker. Precache harus tolerant lewat helper precache().'
        );
    }

    public function test_precache_helper_swallows_individual_failures(): void
    {
        $sw = $this->sw();

        $this->assertSame(
            1,
            preg_match('/async function precache\(cache, urls\)([\s\S]*?)\n}\n/', $sw, $matches),
            'Helper precache() tidak ditemukan di public/sw.js.'
        );

        $body = $matches[1];

        $this->assertStringContainsString(
            'await Promise.all(',
            $body,
            'precache() harus mencoba setiap URL secara paralel.'
        );

        $this->assertStringContainsString(
            'catch',
            $body,
            'precache() harus menelan kegagalan per URL. Tanpa try/catch, satu aset yang gagal menolak seluruh promise dan menggagalkan instalasi.'
        );
    }

    public function test_every_precached_path_is_routed_by_vercel(): void
    {
        $vercel = json_decode(file_get_contents(base_path('vercel.json')), true);

        $this->assertIsArray($vercel, 'vercel.json harus berupa JSON yang valid.');
        $this->assertArrayHasKey('routes', $vercel);

        $sources = array_column($vercel['routes'], 'src');

        // Ambil daftar STATIC_ASSETS dari sw.js.
        $this->assertSame(
            1,
            preg_match('/const STATIC_ASSETS = \[([\s\S]*?)\];/', $this->sw(), $matches),
            'Daftar STATIC_ASSETS tidak ditemukan di public/sw.js.'
        );

        preg_match_all("/'([^']+)'/", $matches[1], $assets);

        $this->assertNotEmpty($assets[1], 'Tidak ada aset yang ter-parse dari STATIC_ASSETS.');

        foreach ($assets[1] as $asset) {
            if (! str_starts_with($asset, '/')) {
                continue;
            }

            // Aset dengan pola catch-all sudah tercakup; yang perlu dipastikan
            // adalah path yang punya nama file tapi tidak dipetakan eksplisit,
            // karena itu jatuh ke Laravel dan bisa saja 404.
            $isExplicitlyRouted = false;
            foreach ($sources as $src) {
                if ($src === $asset) {
                    $isExplicitlyRouted = true;
                    break;
                }

                // src bertipe "/prefix/(.*)" juga menutup path di bawahnya.
                if (str_starts_with($src, '/') && str_contains($src, '(.*)')) {
                    $prefix = explode('(.*)', $src)[0];
                    if ($asset === rtrim($prefix, '/') || str_starts_with($asset, rtrim($prefix, '/'))) {
                        $isExplicitlyRouted = true;
                        break;
                    }
                }
            }

            $this->assertTrue(
                $isExplicitlyRouted,
                "Aset precache '{$asset}' tidak dipetakan di routes vercel.json, sehingga jatuh ke catch-all Laravel dan berisiko 404. Case addAll itu menggagalkan instalasi service worker."
            );
        }
    }

    public function test_service_worker_does_not_precache_dead_pmtiles_cdn_urls(): void
    {
        // Hanya string literal yang diperiksa, bukan komentar.
        preg_match_all("/'([^'\n]*)'/", $this->sw(), $literals);

        $this->assertNotEmpty($literals[1], 'Tidak ada string literal yang ter-parse dari public/sw.js.');

        foreach ($literals[1] as $literal) {
            $this->assertStringNotContainsString(
                'unpkg.com/pmtiles',
                $literal,
                'Semua URL pmtiles di unpkg menjawab 404 sehingga hanya menghasilkan error CORS di console. Aplikasi memakai pmtiles yang ter-bundle dari npm.'
            );
        }
    }

    public function test_cache_names_share_one_version(): void
    {
        $sw = $this->sw();

        $this->assertSame(
            1,
            preg_match("/const CACHE_VERSION = '([^']+)'/", $sw, $version),
            'CACHE_VERSION tidak ditemukan.'
        );

        $this->assertStringContainsString(
            "const INERTIA_CACHE = 'jaya-jaya-jaya-inertia-' + CACHE_VERSION;",
            $sw,
            'INERTIA_CACHE harus diturunkan dari CACHE_VERSION. Hardcode membuat nama cache tidak sinkron begitu versi dinaikkan.'
        );

        $this->assertStringNotContainsString('v1.4.0', $sw, 'Ada sisa versi lama yang ter-hardcode.');
    }
}
