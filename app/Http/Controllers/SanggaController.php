<?php

namespace App\Http\Controllers;

use App\Models\Ambalan;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\Sangga;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SanggaController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);

        $ambalanId = $request->user()->member?->ambalan_id ?? Ambalan::first()?->id;

        $sanggas = Sangga::where('ambalan_id', $ambalanId)
            ->with(['kepalaSanga', 'members'])
            ->orderBy('nama')
            ->get();

        $members = Member::where('ambalan_id', $ambalanId)
            ->orderBy('nama_lengkap')
            ->get(['id', 'nama_lengkap', 'nta', 'tingkatan', 'kelas', 'sanga_id']);

        return Inertia::render('Sangga/Index', [
            'sanggas' => $sanggas,
            'members' => $members,
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina'], true), 403);

        $ambalanId = $request->user()->member?->ambalan_id ?? Ambalan::first()?->id;

        $members = Member::where('ambalan_id', $ambalanId)
            ->whereNull('sanga_id')
            ->orderBy('nama_lengkap')
            ->get(['id', 'nama_lengkap', 'nta', 'tingkatan', 'kelas']);

        return Inertia::render('Sangga/Create', [
            'members' => $members,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina'], true), 403);

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'kode' => ['required', 'string', 'max:20', 'unique:sanggas,kode'],
            'deskripsi' => ['nullable', 'string'],
            'kepala_sanga_id' => ['nullable', 'integer', 'exists:members,id'],
        ]);

        $ambalanId = $request->user()->member?->ambalan_id ?? Ambalan::first()?->id;

        if (! $ambalanId) {
            ValidationException::withMessages([
                'ambalan_id' => 'Belum ada data Ambalan. Tambahkan Ambalan terlebih dahulu.',
            ]);
        }

        $sangga = Sangga::create([
            ...$data,
            'ambalan_id' => $ambalanId,
        ]);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'sangga.created',
            'entity_type' => Sangga::class,
            'entity_id' => $sangga->id,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('sanggas.index')->with('success', 'Sangga berhasil dibuat.');
    }

    public function show(Request $request, Sangga $sangga): Response
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);

        $ambalanId = $request->user()->member?->ambalan_id ?? Ambalan::first()?->id;
        abort_unless($sangga->ambalan_id === $ambalanId, 403);

        $sangga->load(['kepalaSanga', 'members']);

        return Inertia::render('Sangga/Show', [
            'sangga' => $sangga,
        ]);
    }

    public function edit(Request $request, Sangga $sangga): Response
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina'], true), 403);

        $ambalanId = $request->user()->member?->ambalan_id ?? Ambalan::first()?->id;
        abort_unless($sangga->ambalan_id === $ambalanId, 403);

        $members = Member::where('ambalan_id', $ambalanId)
            ->where(function ($query) use ($sangga) {
                $query->whereNull('sanga_id')
                    ->orWhere('id', $sangga->kepala_sanga_id);
            })
            ->orderBy('nama_lengkap')
            ->get(['id', 'nama_lengkap', 'nta', 'tingkatan', 'kelas', 'sanga_id']);

        return Inertia::render('Sangga/Edit', [
            'sangga' => $sangga,
            'members' => $members,
        ]);
    }

    public function update(Request $request, Sangga $sangga): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina'], true), 403);

        $ambalanId = $request->user()->member?->ambalan_id ?? Ambalan::first()?->id;
        abort_unless($sangga->ambalan_id === $ambalanId, 403);

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'kode' => ['required', 'string', 'max:20', 'unique:sanggas,kode,'.$sangga->id],
            'deskripsi' => ['nullable', 'string'],
            'kepala_sanga_id' => ['nullable', 'integer', 'exists:members,id'],
        ]);

        $sangga->update($data);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'sangga.updated',
            'entity_type' => Sangga::class,
            'entity_id' => $sangga->id,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('sanggas.index')->with('success', 'Sangga diperbarui.');
    }

    public function destroy(Request $request, Sangga $sangga): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina'], true), 403);

        $ambalanId = $request->user()->member?->ambalan_id ?? Ambalan::first()?->id;
        abort_unless($sangga->ambalan_id === $ambalanId, 403);

        if ($sangga->members()->count() > 0) {
            return back()->withErrors(['sangga' => 'Tidak dapat menghapus Sangga yang masih memiliki anggota. Pindahkan anggota ke Sangga lain atau kosongkan terlebih dahulu.']);
        }

        $sangga->delete();

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'sangga.deleted',
            'entity_type' => Sangga::class,
            'entity_id' => $sangga->id,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('sanggas.index')->with('success', 'Sangga dihapus.');
    }

    public function assignMember(Request $request, Sangga $sangga): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina'], true), 403);

        $ambalanId = $request->user()->member?->ambalan_id ?? Ambalan::first()?->id;
        abort_unless($sangga->ambalan_id === $ambalanId, 403);

        $data = $request->validate([
            'member_ids' => ['required', 'array'],
            'member_ids.*' => ['integer', 'exists:members,id'],
        ]);

        Member::where('ambalan_id', $ambalanId)
            ->whereIn('id', $data['member_ids'])
            ->update(['sanga_id' => $sangga->id]);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'sangga.members_assigned',
            'entity_type' => Sangga::class,
            'entity_id' => $sangga->id,
            'metadata' => ['member_ids' => $data['member_ids']],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('sanggas.show', $sangga)->with('success', count($data['member_ids']).' anggota berhasil ditambahkan ke Sangga.');
    }

    public function unassignMember(Request $request, Sangga $sangga, Member $member): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina'], true), 403);

        $ambalanId = $request->user()->member?->ambalan_id ?? Ambalan::first()?->id;
        abort_unless($sangga->ambalan_id === $ambalanId, 403);
        abort_unless($member->ambalan_id === $ambalanId, 403);

        if ($member->sanga_id !== $sangga->id) {
            return back()->withErrors(['member' => 'Anggota ini tidak berada di Sangga ini.']);
        }

        $member->update(['sanga_id' => null]);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'sangga.member_unassigned',
            'entity_type' => Sangga::class,
            'entity_id' => $sangga->id,
            'metadata' => ['member_id' => $member->id],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('sanggas.show', $sangga)->with('success', 'Anggota berhasil dikeluarkan dari Sangga.');
    }
}
