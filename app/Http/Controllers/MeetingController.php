<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\MeetingAttendee;
use App\Models\MeetingMinute;
use App\Models\MeetingVote;
use App\Models\MeetingAgenda;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MeetingController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Meeting::query()
            ->with(['ambalan', 'createdBy'])
            ->orderByDesc('tanggal');

        if ($request->string('jenis')->isNotEmpty()) {
            $query->where('jenis', $request->string('jenis'));
        }
        if ($request->string('status')->isNotEmpty()) {
            $query->where('status', $request->string('status'));
        }

        $meetings = $query->paginate(15)->withQueryString();

        return Inertia::render('Meetings/Index', [
            'meetings' => $meetings,
            'filters' => $request->only(['jenis', 'status']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);

        $data = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'agenda' => ['nullable', 'string'],
            'tanggal' => ['required', 'date'],
            'waktu_mulai' => ['nullable', 'date_format:H:i'],
            'waktu_selesai' => ['nullable', 'date_format:H:i'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'jenis' => ['required', 'in:Musyawarah,Rapat Pembina,Rapat Anggota,Sidang,Lainnya'],
        ]);

        $meeting = Meeting::create([
            ...$data,
            'ambalan_id' => $request->user()->member?->ambalan_id ?? \App\Models\Ambalan::first()?->id,
            'created_by' => $request->user()->id,
            'status' => 'Draft',
        ]);

        if ($data['agenda']) {
            $items = explode("\n", $data['agenda']);
            foreach ($items as $index => $item) {
                $item = trim($item);
                if ($item) {
                    $meeting->agendas()->create(['judul' => $item, 'urutan' => $index + 1]);
                }
            }
        }

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'meeting.created',
            'entity_type' => Meeting::class,
            'entity_id' => $meeting->id,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('meetings.index')->with('success', 'Rapat berhasil dibuat.');
    }

    public function update(Request $request, Meeting $meeting): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);

        $data = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'agenda' => ['nullable', 'string'],
            'tanggal' => ['required', 'date'],
            'waktu_mulai' => ['nullable', 'date_format:H:i'],
            'waktu_selesai' => ['nullable', 'date_format:H:i'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'jenis' => ['required', 'in:Musyawarah,Rapat Pembina,Rapat Anggota,Sidang,Lainnya'],
            'status' => ['required', 'in:Draft,Berlangsung,Selesai,Dibatalkan'],
        ]);

        $meeting->update($data);

        if ($data['agenda']) {
            $meeting->agendas()->delete();
            $items = explode("\n", $data['agenda']);
            foreach ($items as $index => $item) {
                $item = trim($item);
                if ($item) {
                    $meeting->agendas()->create(['judul' => $item, 'urutan' => $index + 1]);
                }
            }
        }

        return redirect()->route('meetings.index')->with('success', 'Rapat berhasil diperbarui.');
    }

    public function destroy(Request $request, Meeting $meeting): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);

        $meeting->delete();
        return redirect()->route('meetings.index')->with('success', 'Rapat dihapus.');
    }

    public function show(Meeting $meeting): Response
    {
        $meeting->load(['agendas.votes', 'attendees.member.user', 'minutes', 'ambalan']);
        return Inertia::render('Meetings/Show', ['meeting' => $meeting]);
    }

    public function addAgenda(Request $request, Meeting $meeting): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);

        $data = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'urutan' => ['required', 'integer', 'min:1'],
        ]);

        $meeting->agendas()->create($data);

        return back()->with('success', 'Agenda ditambahkan.');
    }

    public function storeMinute(Request $request, Meeting $meeting): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);

        $data = $request->validate([
            'notulen' => ['required', 'string'],
            'keputusan' => ['nullable', 'string'],
            'tindak_lanjut' => ['nullable', 'string'],
        ]);

        MeetingMinute::create([
            ...$data,
            'meeting_id' => $meeting->id,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Notulen rapat berhasil disimpan.');
    }

    public function vote(Request $request, Meeting $meeting, MeetingAgenda $agenda): RedirectResponse
    {
        $member = $request->user()->member;
        abort_unless($member, 403);

        $data = $request->validate([
            'pilihan' => ['required', 'in:Setuju,Tidak Setuju,Abstain'],
        ]);

        MeetingVote::updateOrCreate([
            'meeting_id' => $meeting->id,
            'member_id' => $member->id,
            'agenda_id' => $agenda->id,
        ], $data);

        return back()->with('success', 'Suara Anda telah dicatat.');
    }

    public function toggleAttendee(Request $request, Meeting $meeting): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina'], true), 403);

        $data = $request->validate([
            'member_id' => ['required', 'exists:members,id'],
            'status' => ['required', 'in:Hadir,Izin,Tidak Hadir'],
        ]);

        MeetingAttendee::updateOrCreate([
            'meeting_id' => $meeting->id,
            'member_id' => $data['member_id'],
        ], ['status' => $data['status']]);

        return back()->with('success', 'Kehadiran diperbarui.');
    }
}
