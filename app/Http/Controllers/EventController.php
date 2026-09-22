<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Event::query()
            ->with(['ambalan', 'createdBy'])
            ->orderByDesc('tanggal');

        if ($request->string('jenis')->isNotEmpty()) {
            $query->where('jenis', $request->string('jenis'));
        }
        if ($request->string('status')->isNotEmpty()) {
            $query->where('status', $request->string('status'));
        }

        $events = $query->paginate(15)->withQueryString();

        return Inertia::render('Events/Index', [
            'events' => $events,
            'filters' => $request->only(['jenis', 'status']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'tanggal' => ['required', 'date'],
            'waktu_mulai' => ['nullable', 'date_format:H:i'],
            'waktu_selesai' => ['nullable', 'date_format:H:i'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:2048', 'mimes:jpg,jpeg,png,webp'],
            'jenis' => ['required', 'in:Latihan,Kegiatan,Pertemuan,Outbound,Jambore,Lainnya'],
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('events/gallery', 'public');
        }

        $event = Event::create([
            ...$data,
            'ambalan_id' => $request->user()->member?->ambalan_id ?? \App\Models\Ambalan::first()?->id,
            'created_by' => $request->user()->id,
            'status' => 'Draft',
        ]);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'event.created',
            'entity_type' => Event::class,
            'entity_id' => $event->id,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('events.index')->with('success', 'Kegiatan berhasil dibuat.');
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'tanggal' => ['required', 'date'],
            'waktu_mulai' => ['nullable', 'date_format:H:i'],
            'waktu_selesai' => ['nullable', 'date_format:H:i'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:2048', 'mimes:jpg,jpeg,png,webp'],
            'jenis' => ['required', 'in:Latihan,Kegiatan,Pertemuan,Outbound,Jambore,Lainnya'],
            'status' => ['required', 'in:Draft,Aktif,Selesai,Dibatalkan'],
        ]);

        if ($request->hasFile('image')) {
            if ($event->image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($event->image);
            }
            $data['image'] = $request->file('image')->store('events/gallery', 'public');
        }

        $event->update($data);

        return redirect()->route('events.index')->with('success', 'Kegiatan berhasil diperbarui.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $event->delete();
        return redirect()->route('events.index')->with('success', 'Kegiatan dihapus.');
    }

    public function join(Request $request, Event $event): RedirectResponse
    {
        $member = $request->user()->member;
        abort_unless($member, 403);

        EventParticipant::create([
            'event_id' => $event->id,
            'member_id' => $member->id,
            'status' => 'Pending',
        ]);

        return back()->with('success', 'Anda telah mendaftar kegiatan ini.');
    }

    public function updateParticipant(Request $request, Event $event, EventParticipant $participant): RedirectResponse
    {
        abort_unless($participant->member_id === $request->user()->member?->id || in_array($request->user()->role, ['Admin', 'Pembina'], true), 403);

        $data = $request->validate([
            'status' => ['required', 'in:Pending,Hadir,Izin,Tidak Hadir'],
        ]);

        $participant->update($data);

        return back()->with('success', 'Status kehadiran diperbarui.');
    }

    public function show(Event $event): Response
    {
        $event->load(['participants.member.user', 'ambalan']);
        return Inertia::render('Events/Show', ['event' => $event]);
    }
}
