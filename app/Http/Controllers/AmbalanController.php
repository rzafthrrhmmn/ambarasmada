<?php

namespace App\Http\Controllers;

use App\Models\Ambalan;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AmbalanController extends Controller
{
    public function edit(Request $request): Response
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina'], true), 403);

        $ambalan = Ambalan::first();

        return Inertia::render('Ambalan/Edit', [
            'ambalan' => $ambalan,
        ]);
    }

    public function updateLogo(Request $request): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina'], true), 403);

        $data = $request->validate([
            'logo' => ['required', 'image', 'max:2048', 'mimes:jpg,jpeg,png,webp'],
        ]);

        $ambalan = Ambalan::firstOrFail();

        if ($request->hasFile('logo')) {
            if ($ambalan->logo_path) {
                Storage::disk('public')->delete($ambalan->logo_path);
            }
            $ambalan->logo_path = $request->file('logo')->store('ambalan/logo', 'public');
            $ambalan->save();
        }

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'ambalan.logo_updated',
            'entity_type' => Ambalan::class,
            'entity_id' => $ambalan->id,
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Logo ambalan berhasil diperbarui.');
    }

    public function destroyLogo(Request $request): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina'], true), 403);

        $ambalan = Ambalan::firstOrFail();

        if ($ambalan->logo_path) {
            Storage::disk('public')->delete($ambalan->logo_path);
            $ambalan->logo_path = null;
            $ambalan->save();
        }

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'ambalan.logo_removed',
            'entity_type' => Ambalan::class,
            'entity_id' => $ambalan->id,
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Logo ambalan berhasil dihapus.');
    }
}
