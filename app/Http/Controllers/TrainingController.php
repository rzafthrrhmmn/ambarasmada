<?php

namespace App\Http\Controllers;

use App\Models\Training;
use App\Models\TrainingProgress;
use App\Models\Ambalan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TrainingController extends Controller
{
    public function index(Request $request)
    {
        $query = Training::query()->with(['createdBy', 'progress']);

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $trainings = $query->paginate(20);

        return Inertia::render('Trainings/Index', [
            'trainings' => $trainings,
            'filters' => $request->only(['kategori', 'status']),
            'user' => $request->user(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina'], true), 403);

        $request->validate([
            'judul' => 'required|string|max:255',
            'kategori' => 'required|string|max:255',
            'konten' => 'nullable|string',
            'video_url' => 'nullable|url',
            'file_path' => 'nullable|string|max:500',
            'tanggal' => 'nullable|date',
        ]);

        Training::create([
            'ambalan_id' => $request->user()->member?->ambalan_id ?? Ambalan::first()?->id,
            'created_by' => $request->user()->id,
            'judul' => $request->judul,
            'konten' => $request->konten,
            'kategori' => $request->kategori,
            'video_url' => $request->video_url,
            'file_path' => $request->file_path,
            'tanggal' => $request->tanggal,
            'status' => $request->status ?? 'Draft',
        ]);

        return redirect()->route('trainings.index')->with('success', 'Training created successfully.');
    }

    public function update(Request $request, Training $training)
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);

        $request->validate([
            'judul' => 'sometimes|required|string|max:255',
            'kategori' => 'sometimes|required|string|max:255',
            'konten' => 'nullable|string',
            'video_url' => 'nullable|url',
            'file_path' => 'nullable|string|max:500',
            'tanggal' => 'nullable|date',
            'status' => 'sometimes|required|in:Draft,Aktif,Selesai',
        ]);

        $training->update($request->only(['judul', 'kategori', 'konten', 'video_url', 'file_path', 'tanggal', 'status']));

        return redirect()->back()->with('success', 'Training updated successfully.');
    }

    public function destroy(Request $request, Training $training)
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina'], true), 403);

        $training->delete();

        return redirect()->route('trainings.index')->with('success', 'Training deleted successfully.');
    }

    public function toggleComplete(Request $request, Training $training, TrainingProgress $progress)
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);

        $progress->update([
            'completed' => !$progress->completed,
            'catatan' => $progress->catatan,
        ]);

        return redirect()->back()->with('success', 'Progress updated successfully.');
    }

    public function markAllComplete(Request $request, Training $training)
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);

        TrainingProgress::where('training_id', $training->id)
            ->update(['completed' => true]);

        return redirect()->back()->with('success', 'All members marked as completed.');
    }
}