<?php

namespace App\Http\Controllers;

use App\Models\Reminder;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReminderController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Reminder::query()
            ->with(['user', 'createdBy'])
            ->orderBy('jadwal');

        if (in_array($request->user()->role, ['Admin', 'Pembina'], true)) {
            $reminders = $query->paginate(20)->withQueryString();
        } else {
            $reminders = $query->where('user_id', $request->user()->id)->paginate(20)->withQueryString();
        }

        return Inertia::render('Reminders/Index', [
            'reminders' => $reminders,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'jadwal' => ['required', 'date_format:Y-m-d H:i'],
            'jenis' => ['required', 'in:Pengingat,Tugas,Iuran,Kegiatan,Lainnya'],
        ]);

        Reminder::create([
            ...$data,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('reminders.index')->with('success', 'Pengingat berhasil dibuat.');
    }

    public function update(Request $request, Reminder $reminder): RedirectResponse
    {
        abort_unless($reminder->created_by === $request->user()->id || in_array($request->user()->role, ['Admin', 'Pembina'], true), 403);

        $data = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'jadwal' => ['required', 'date_format:Y-m-d H:i'],
            'jenis' => ['required', 'in:Pengingat,Tugas,Iuran,Kegiatan,Lainnya'],
        ]);

        $reminder->update($data);
        return back()->with('success', 'Pengingat diperbarui.');
    }

    public function destroy(Reminder $reminder): RedirectResponse
    {
        $reminder->delete();
        return redirect()->route('reminders.index')->with('success', 'Pengingat dihapus.');
    }

    public function markAsSent(Request $request, Reminder $reminder): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina'], true), 403);
        $reminder->update(['is_sent' => true]);
        return back()->with('success', 'Pengingat ditandai sudah dikirim.');
    }
}
