<?php

namespace App\Http\Middleware;

use App\Models\Ambalan;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        $ambalan = Ambalan::first();

        return [
            ...parent::share($request),
            'ambalan' => $ambalan ? [
                'id' => $ambalan->id,
                'nama' => $ambalan->nama,
                'kode' => $ambalan->kode,
                'logo_path' => $ambalan->logo_path,
                'logo_url' => $ambalan->logo_url,
            ] : null,
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'username' => $request->user()->username,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'email_verified_at' => optional($request->user()->email_verified_at)->toIso8601String(),
                    'role' => $request->user()->role,
                    'status' => $request->user()->status,
                    'member_id' => $request->user()->member?->id,
                    'nta' => $request->user()->member?->nta,
                    'is_juru_uang' => $request->user()->member?->memberPositions()
                        ->whereHas('position', fn ($q) => $q->whereIn('code', ['juru_uang_putra', 'juru_uang_putri']))
                        ->exists(),
                ] : null,
            ],
            'unreadNotificationCount' => $request->user() ? Notification::where('user_id', $request->user()->id)->where('is_read', false)->count() : 0,
            'pendingCount' => $request->user() && in_array($request->user()->role, ['Admin', 'Pembina'], true)
                ? User::where('status', 'pending')->count()
                : null,
            'flash' => $this->flashMessages($request),
        ];
    }

    /**
     * Flash yang dibagikan ke halaman hanya berisi pesan yang benar-benar ada.
     *
     * Sebelumnya 'success' dan 'error' selalu dikirim, walau nilainya null.
     * Sisi klien hanya memeriksa jumlah key, jadi setiap muat halaman (termasuk
     * hasil refresh) memunculkan kotak notifikasi tanpa teks. Pesan yang kosong
     * karena itu diabaikan di sini, dan supaya tidak perlu perubahan di tiap
     * pengirim pesan, flash apa pun yang bukan string kosong ikut diteruskan.
     *
     * @return array<string, string>
     */
    protected function flashMessages(Request $request): array
    {
        $messages = [];

        foreach (['success', 'info', 'warning', 'error'] as $key) {
            $message = $request->session()->get($key);

            $message = is_string($message) ? $message : ($message === null ? '' : (string) $message);

            if (trim($message) !== '') {
                $messages[$key] = $message;
            }
        }

        return $messages;
    }
}
