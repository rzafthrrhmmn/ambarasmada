<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        error_log('[VERCEL-MIDDLEWARE] SecurityHeaders: before next');
        $response = $next($request);
        error_log('[VERCEL-MIDDLEWARE] SecurityHeaders: after next, status: '.$response->getStatusCode());

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        // Kamera dipakai pemindai QR dan geolokasi dipakai verifikasi geofence
        // presensi. Nilai kosong berarti fitur diblokir total di browser, jadi
        // keduanya harus '(self)': boleh untuk origin aplikasi ini saja, tetap
        // tertutup untuk origin pihak ketiga yang di-embed.
        $response->headers->set(
            'Permissions-Policy',
            'camera=(self), geolocation=(self), microphone=(), payment=(), usb=(), interest-cohort=()'
        );

        if ($request->isSecure() || $request->header('X-Forwarded-Proto') === 'https') {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        $isLocal = app()->environment('local');

        $viteSources = $isLocal
            ? 'http://localhost:5173 http://localhost:5174 http://[::1]:5173 http://[::1]:5174'
            : '';

        // Arsip PMTiles dibaca browser lewat HTTP Range ke host tempat file
        // disimpan. Host itu harus diizinkan connect-src, kalau tidak
        // permintaan Range diblokir dan layer kontur tidak pernah dimuat.
        $pmtilesSource = $this->originOf((string) config('map.pmtiles_url'));

        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' {$viteSources} https://unpkg.com https://tile.openstreetmap.org",
            "style-src 'self' 'unsafe-inline' {$viteSources} https://unpkg.com https://fonts.googleapis.com",
            "font-src 'self' data: https://fonts.gstatic.com {$viteSources}",
            "img-src 'self' data: https: blob:",
            // Nominatim dipakai pencarian lokasi pada LocationPicker; tanpa ini
            // fetch-nya diblokir CSP dan muncul sebagai "Failed to fetch".
            // OpenTopoMap (basemap Terrain + hillshade) dan Stadia (basemap Dark)
            // juga wajib ada, kalau tidak memilih basemap itu menghasilkan kanvas kosong.
            // MapLibre membuat worker dari URL same-origin, jadi 'self' cukup.
            // https://*.supabase.co menutupi endpoint Storage mana pun untuk
            // bucket publik, sehingga PMTILES_URL tidak harus ditulis ulang
            // di sini setiap kali project Supabase diganti.
            // fonts.openmaptiles.org melayani glyph untuk layer symbol dan
            // s3.amazonaws.com melayani DEM terrarium untuk hillshade; tanpa
            // keduanya di sini, label dan hillshade gagal dimuat.
            "connect-src 'self' {$viteSources} https://tile.openstreetmap.org https://nominatim.openstreetmap.org https://server.arcgisonline.com https://tile.opentopomap.org https://tiles.stadiamaps.com https://unpkg.com https://fonts.openmaptiles.org https://s3.amazonaws.com https://*.supabase.co {$pmtilesSource}",
            "worker-src 'self' blob:",
            // Pratinjau surat ditampilkan di dalam <iframe> yang sumbernya
            // blob: dari hasil balasan /letters/preview. Tanpa frame-src, CSP
            // memakai default-src 'self' dan memblokirnya, jadi pratinjau
            // selalu kosong walaupun server sudah mengirim HTML-nya.
            //
            // blob: di sini aman: blob URL hanya bisa dibuat oleh skrip
            // same-origin, jadi isinya tetap dokumen milik aplikasi sendiri,
            // bukan sumber luar. 'self' tetap dituliskan supaya halaman
            // normal bisa dibingkai juga.
            "frame-src 'self' blob:",
            "frame-ancestors 'none'",
            "form-action 'self'",
            "base-uri 'self'",
            "object-src 'none'",
        ];

        $response->headers->set('Content-Security-Policy', implode('; ', $csp));

        error_log('[VERCEL-MIDDLEWARE] SecurityHeaders: headers set, returning response');

        return $response;
    }

    /**
     * Ambil "scheme://host" dari sebuah URL absolut, atau string kosong
     * bila URL-nya tidak bisa dipakai sebagai sumber CSP.
     */
    private function originOf(string $url): string
    {
        if ($url === '') {
            return '';
        }

        $parts = parse_url($url);

        if (! isset($parts['scheme'], $parts['host'])) {
            return '';
        }

        return $parts['scheme'].'://'.$parts['host'];
    }
}
