<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\PwaDevice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PwaController extends Controller
{
    public function registerDevice(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'url', 'max:2000'],
            'p256dh' => ['nullable', 'string', 'max:2000'],
            'auth' => ['nullable', 'string', 'max:2000'],
        ]);
        PwaDevice::updateOrCreate(
            ['user_id' => $request->user()->id, 'endpoint' => $data['endpoint']],
            [
                'p256dh' => $data['p256dh'] ?: null,
                'auth' => $data['auth'] ?: null,
                'last_seen' => now(),
            ]
        );
        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'pwa.device.registered',
            'entity_type' => PwaDevice::class,
            'entity_id' => $request->user()->id,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->back()->with('success', 'Perangkat PWA berhasil didaftarkan.');
    }
}
