<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\TeamTask;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);

        $teams = Team::with(['ambalan', 'members', 'tasks'])
            ->orderBy('nama')
            ->get();

        return Inertia::render('Teams/Index', ['teams' => $teams]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina'], true), 403);

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'kode' => ['required', 'string', 'max:50'],
            'deskripsi' => ['nullable', 'string'],
        ]);

        $team = Team::create([
            ...$data,
            'ambalan_id' => $request->user()->member?->ambalan_id ?? \App\Models\Ambalan::first()?->id,
        ]);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'team.created',
            'entity_type' => Team::class,
            'entity_id' => $team->id,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('teams.index')->with('success', 'Gugus depan berhasil dibuat.');
    }

    public function update(Request $request, Team $team): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina'], true), 403);

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'kode' => ['required', 'string', 'max:50'],
            'deskripsi' => ['nullable', 'string'],
        ]);

        $team->update($data);
        return back()->with('success', 'Gugus depan diperbarui.');
    }

    public function destroy(Request $request, Team $team): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina'], true), 403);

        $team->delete();
        return redirect()->route('teams.index')->with('success', 'Gugus depan dihapus.');
    }

    public function addMember(Request $request, Team $team): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina'], true), 403);

        $data = $request->validate([
            'member_id' => ['required', 'exists:members,id'],
            'peran' => ['required', 'string', 'max:255'],
        ]);

        TeamMember::create([
            ...$data,
            'team_id' => $team->id,
        ]);

        return back()->with('success', 'Anggota ditambahkan ke gugus depan.');
    }

    public function removeMember(Request $request, Team $team, TeamMember $teamMember): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina'], true), 403);

        $teamMember->delete();
        return back()->with('success', 'Anggota dihapus dari gugus depan.');
    }

    public function createTask(Request $request, Team $team): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);

        $data = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'assigned_to' => ['required', 'exists:members,id'],
            'tenggat' => ['nullable', 'date'],
        ]);

        TeamTask::create([
            ...$data,
            'team_id' => $team->id,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Tugas ditambahkan.');
    }

    public function updateTask(Request $request, TeamTask $task): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina'], true), 403);

        $data = $request->validate([
            'status' => ['required', 'in:Belum Dimulai,Berlangsung,Selesai,Terlewat'],
        ]);

        $task->update($data);
        return back()->with('success', 'Status tugas diperbarui.');
    }
}