<?php

namespace App\Support;

use App\Models\Ambalan;

/**
 * Sumber branding untuk email transaksional (aktivasi akun & reset password).
 *
 * Logo di dalam badan email harus berupa URL absolut yang dapat diakses publik
 * oleh mail client penerima. Karena itu `Ambalan::logo_url` tidak dipakai
 * langsung: accessor tersebut mengembalikan null saat aplikasi berjalan di
 * Vercel (lihat Ambalan::getLogoUrlAttribute), dan berkas yang diunggah ke
 * disk lokal tidak ikut ter-deploy ke serverless.
 *
 * Strategi: pakai `logo_path` hanya bila sudah berupa URL absolut (mis. URL
 * S3/CDN), selain itu jatuh ke logo statis `public/images/Logo_Ambalan.png`
 * yang memang dilayani oleh route `/images/(.*)` di vercel.json.
 */
final class MailBranding
{
    /**
     * Path logo statis bawaan yang ikut ter-deploy ke produksi.
     */
    public static function fallbackLogoPath(): string
    {
        return 'images/Logo_Ambalan.png';
    }

    /**
     * URL absolut logo untuk ditampilkan di dalam email.
     */
    public static function logoUrl(): string
    {
        $path = self::uploadedLogoUrl();

        return $path ?? asset(self::fallbackLogoPath());
    }

    /**
     * Nama ambalan untuk ditampilkan di header/footer email.
     */
    public static function ambalanName(): string
    {
        try {
            $nama = Ambalan::first()?->nama;
        } catch (\Throwable) {
            $nama = null;
        }

        return is_string($nama) && trim($nama) !== ''
            ? trim($nama)
            : 'Ambalan UPT SMAN 2 Maros';
    }

    /**
     * Nama aplikasi untuk subject dan header email.
     */
    public static function appName(): string
    {
        $name = config('app.name');

        return is_string($name) && trim($name) !== ''
            ? trim($name)
            : 'AMBARA-SISTEM DIGITAL';
    }

    /**
     * Logo hasil unggahan, hanya bila Stored sebagai URL absolut.
     */
    private static function uploadedLogoUrl(): ?string
    {
        try {
            $path = Ambalan::first()?->logo_path;
        } catch (\Throwable) {
            return null;
        }

        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        $path = trim($path);

        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }

        return null;
    }

    /**
     * Host aplikasi, mis. "ambarasmada.com".
     */
    public static function appHost(): ?string
    {
        return self::extractHost((string) config('app.url'));
    }

    /**
     * Host pengirim email, mis. "ambarasmada.com".
     */
    public static function sendingHost(): ?string
    {
        return self::extractHost((string) config('mail.from.address'));
    }

    /**
     * Ambil host dari URL maupun dari alamat email polos ("noreply@a.com"),
     * yang tidak dapat diurai dengan parse_url() karena tanpa skema.
     */
    private static function extractHost(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        // From boleh ditulis sebagai "AMBARA <noreply@ambarasmada.com>".
        if (preg_match('/<([^>]+)>/', $value, $m) === 1) {
            $value = trim($m[1]);
        }

        if (str_contains($value, '@')) {
            $parts = explode('@', $value);
            $value = trim(end($parts));
        }

        $host = parse_url($value, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            $host = preg_split('#[:/]#', $value)[0] ?? null;
        }

        return is_string($host) && $host !== '' ? strtolower($host) : null;
    }

    /**
     * Apakah link di dalam email (link verifikasi + logo) berasal dari domain
     * yang sama dengan domain pengirim.
     *
     * Resend menandai domain sebagai "needs attention" bila link dan gambar
     * email menunjuk ke domain lain, karena menurunkan kepercayaan dan
     * deliverability.
     *
     * @return array{app_host: ?string, sending_host: ?string, matches: bool}
     */
    public static function linkDomainStatus(): array
    {
        $app = self::appHost();
        $sending = self::sendingHost();

        // Host pengirim yang tidak bisa diurai (mis. masih kosong) dianggap
        // tidak mismatch agar tidak membingungkan.
        $matches = $app === null || $sending === null || $app === $sending;

        return [
            'app_host' => $app,
            'sending_host' => $sending,
            'matches' => $matches,
        ];
    }
}
