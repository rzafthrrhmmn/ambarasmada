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
                    'role' => $request->user()->role,
                    'foto' => $request->user()->foto,
                    'member_id' => $request->user()->member?->id,
                    'is_juru_uang' => $request->user()->member?->memberPositions()
                        ->whereHas('position', fn ($q) => $q->whereIn('code', ['juru_uang_putra', 'juru_uang_putri']))
                        ->exists(),
                ] : null,
            ],
            'unreadNotificationCount' => $request->user() ? Notification::where('user_id', $request->user()->id)->where('is_read', false)->count() : 0,
            'pendingCount' => $request->user() && in_array($request->user()->role, ['Admin', 'Pembina'], true)
                ? User::where('status', 'pending')->count()
                : null,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
