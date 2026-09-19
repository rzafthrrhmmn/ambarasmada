<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\TkkPoint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TkkPointController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === 'Pembina', 403);

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150', 'unique:tkk_points,nama'],
            'deskripsi' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        $data['slug'] = str()->slug($data['nama']);

        $tkkPoint = TkkPoint::create($data);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'tkk.point.created',
            'entity_type' => TkkPoint::class,
            'entity_id' => $tkkPoint->id,
            'metadata' => ['nama' => $tkkPoint->nama],
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Poin TKK berhasil ditambahkan.');
    }

    public function update(Request $request, TkkPoint $tkkPoint): RedirectResponse
    {
        abort_unless($request->user()->role === 'Pembina', 403);

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150', Rule::unique('tkk_points', 'nama')->ignore($tkkPoint->id)],
            'deskripsi' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        $data['slug'] = str()->slug($data['nama']);

        $tkkPoint->update($data);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'tkk.point.updated',
            'entity_type' => TkkPoint::class,
            'entity_id' => $tkkPoint->id,
            'metadata' => ['nama' => $tkkPoint->nama],
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Poin TKK berhasil diperbarui.');
    }
}
