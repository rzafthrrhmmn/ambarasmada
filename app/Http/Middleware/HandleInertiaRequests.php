<?php

namespace App\Http\Middleware;

use App\Models\Ambalan;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

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
            'unreadNotificationCount' => $request->user() ? \App\Models\Notification::where('user_id', $request->user()->id)->where('is_read', false)->count() : 0,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
