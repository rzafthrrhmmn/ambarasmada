<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Ambalan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CandidateController extends Controller
{
    public function index(Request $request)
    {
        $query = Candidate::query()->with(['createdBy']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('nama_lengkap')) {
            $query->where('nama_lengkap', 'like', '%' . $request->nama_lengkap . '%');
        }

        $candidates = $query->paginate(20);

        return Inertia::render('Candidates/Index', [
            'candidates' => $candidates,
            'filters' => $request->only(['status', 'nama_lengkap']),
            'user' => $request->user(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);

        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'tempat_lahir' => 'nullable|string|max:255',
            'tanggal_lahir' => 'nullable|date',
            'jenis_kelamin' => 'nullable|string|in:Laki-laki,Perempuan',
            'kelas' => 'nullable|string|max:50',
            'no_hp' => 'nullable|string|max:20',
            'riwayat_pramuka' => 'nullable|string',
            'catatan' => 'nullable|string',
        ]);

        Candidate::create([
            'ambalan_id' => $request->user()->member?->ambalan_id ?? Ambalan::first()?->id,
            'created_by' => $request->user()->id,
            'nama_lengkap' => $request->nama_lengkap,
            'tempat_lahir' => $request->tempat_lahir,
            'tanggal_lahir' => $request->tanggal_lahir,
            'jenis_kelamin' => $request->jenis_kelamin,
            'kelas' => $request->kelas,
            'no_hp' => $request->no_hp,
            'riwayat_pramuka' => $request->riwayat_pramuka,
            'catatan' => $request->catatan,
            'status' => $request->status ?? 'Pending',
        ]);

        return redirect()->back()->with('success', 'Candidate added successfully.');
    }

    public function update(Request $request, Candidate $candidate)
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);

        $request->validate([
            'status' => 'required|in:Pending,Diterima,Ditolak',
            'nama_lengkap' => 'sometimes|required|string|max:255',
            'tempat_lahir' => 'nullable|string|max:255',
            'tanggal_lahir' => 'nullable|date',
            'jenis_kelamin' => 'nullable|string',
            'kelas' => 'nullable|string|max:50',
            'no_hp' => 'nullable|string|max:20',
            'riwayat_pramuka' => 'nullable|string',
            'catatan' => 'nullable|string',
        ]);

        $candidate->update($request->only(['nama_lengkap', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'kelas', 'no_hp', 'riwayat_pramuka', 'catatan', 'status']));

        return redirect()->back()->with('success', 'Candidate updated successfully.');
    }

    public function destroy(Request $request, Candidate $candidate)
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina'], true), 403);

        $candidate->delete();

        return redirect()->back()->with('success', 'Candidate deleted successfully.');
    }
}