<?php

namespace App\Support;

use App\Models\User;

/**
 * Sumber tunggal daftar peran untuk sisi server.
 *
 * Sebelumnya tiap controller menulis sendiri daftarnya, misalnya
 * `in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true)`.
 * Daftar itu muncul di tiga puluh-an controller dan tidak selalu sama satu
 * sama lain, sehingga "Admin boleh mengubahCipher" berubah jadi "Admin dan
 * Pembina boleh" hanya karena satu controller lupa menambah Pembina.
 *
 * Berkas ini adalah padanan server dari resources/js/Access/capabilities.js.
 * Keduanya harus diubah bersama: satu untuk menegakan, satu untuk menampilkan.
 */
final class Roles
{
    public const ADMIN = 'Admin';

    public const PEMBINA = 'Pembina';

    public const PENGURUS = 'Pengurus';

    public const ANGGOTA = 'Anggota';

    public const ALUMNI = 'Alumni';

    /** Peran yang mengelola modul operasional harian. */
    public const MANAGEMENT = [self::ADMIN, self::PEMBINA, self::PENGURUS];

    /** Peran yang boleh mengambil keputusan, mis. verifikasi keanggotaan. */
    public const APPROVER = [self::ADMIN, self::PEMBINA];

    /** Peran dengan akses ke aplikasi inti. Alumni punya portal terpisah. */
    public const ACTIVE = [self::ADMIN, self::PEMBINA, self::PENGURUS, self::ANGGOTA];

    /**
     * Daftar peran yang valid untuk sebuah akun.
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [self::ADMIN, self::PEMBINA, self::PENGURUS, self::ANGGOTA, self::ALUMNI];
    }

    public static function isManagement(?User $user): bool
    {
        return $user !== null && in_array($user->role, self::MANAGEMENT, true);
    }

    public static function isApprover(?User $user): bool
    {
        return $user !== null && in_array($user->role, self::APPROVER, true);
    }

    public static function isActive(?User $user): bool
    {
        return $user !== null && in_array($user->role, self::ACTIVE, true);
    }

    public static function isMember(?User $user): bool
    {
        return $user !== null && $user->role === self::ANGGOTA;
    }

    /**
     * Apakah pengguna adalah juru uang.
     *
     * Juru uang bukan peran tersendiri melainkan posisi yang dipegang seorang
     * Pengurus, sehingga role-nya tetap 'Pengurus'. Lihat juga
     * resources/js/Access/capabilities.js yang memakai basis yang sama.
     */
    public static function isFinanceOfficer(?User $user): bool
    {
        return $user !== null && $user->role === self::PENGURUS && (bool) $user->is_juru_uang;
    }

    public static function canViewFinance(?User $user): bool
    {
        return self::isApprover($user) || self::isFinanceOfficer($user) || ($user !== null && $user->role === self::ANGGOTA);
    }

    /**
     * Guard untuk modul yang hanya boleh dibuka pengelola.
     */
    public static function guardManagement(?User $user): void
    {
        abort_unless(self::isManagement($user), 403);
    }

    /**
     * Guard untuk modul yang hanya boleh dibuka pengambil keputusan.
     */
    public static function guardApprover(?User $user): void
    {
        abort_unless(self::isApprover($user), 403);
    }
}
