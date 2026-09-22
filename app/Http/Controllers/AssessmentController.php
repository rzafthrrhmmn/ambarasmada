<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\AssessmentDetail;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AssessmentController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina'], true), 403);

        $query = Assessment::query()
            ->with(['member.user', 'assessor'])
            ->orderByDesc('created_at');

        if ($request->string('periode')->isNotEmpty()) {
            $query->where('periode', $request->string('periode'));
        }

        $assessments = $query->paginate(15)->withQueryString();
        $members = \App\Models\Member::where('status_aktif', 'Aktif')->orderBy('nama_lengkap')->get();

        return Inertia::render('Assessments/Index', [
            'assessments' => $assessments,
            'members' => $members,
            'filters' => $request->only(['periode']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina'], true), 403);

        $data = $request->validate([
            'member_id' => ['required', 'exists:members,id'],
            'periode' => ['required', 'string', 'max:50'],
            'nilai_kehadiran' => ['required', 'numeric', 'min:0', 'max:100'],
            'nilai_disiplin' => ['required', 'numeric', 'min:0', 'max:100'],
            'nilai_keterampilan' => ['required', 'numeric', 'min:0', 'max:100'],
            'nilai_kepemimpinan' => ['required', 'numeric', 'min:0', 'max:100'],
            'catatan' => ['nullable', 'string'],
        ]);

        $nilaiKeseluruhan = (
            $data['nilai_kehadiran'] +
            $data['nilai_disiplin'] +
            $data['nilai_keterampilan'] +
            $data['nilai_kepemimpinan']
        ) / 4;

        $assessment = Assessment::create([
            ...$data,
            'ambalan_id' => $request->user()->member?->ambalan_id ?? \App\Models\Ambalan::first()?->id,
            'assessor_id' => $request->user()->id,
            'nilai_keseluruhan' => round($nilaiKeseluruhan, 2),
            'status' => 'Draft',
        ]);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'assessment.created',
            'entity_type' => Assessment::class,
            'entity_id' => $assessment->id,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('assessments.index')->with('success', 'Penilaian berhasil dibuat.');
    }

    public function update(Request $request, Assessment $assessment): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina'], true), 403);

        $data = $request->validate([
            'nilai_kehadiran' => ['required', 'numeric', 'min:0', 'max:100'],
            'nilai_disiplin' => ['required', 'numeric', 'min:0', 'max:100'],
            'nilai_keterampilan' => ['required', 'numeric', 'min:0', 'max:100'],
            'nilai_kepemimpinan' => ['required', 'numeric', 'min:0', 'max:100'],
            'catatan' => ['nullable', 'string'],
            'status' => ['required', 'in:Draft,Selesai'],
        ]);

        $nilaiKeseluruhan = (
            $data['nilai_kehadiran'] +
            $data['nilai_disiplin'] +
            $data['nilai_keterampilan'] +
            $data['nilai_kepemimpinan']
        ) / 4;

        $assessment->update(array_merge($data, ['nilai_keseluruhan' => round($nilaiKeseluruhan, 2)]));

        return back()->with('success', 'Penilaian diperbarui.');
    }

    public function destroy(Assessment $assessment): RedirectResponse
    {
        $assessment->delete();
        return back()->with('success', 'Penilaian dihapus.');
    }

    public function show(Assessment $assessment): Response
    {
        $assessment->load(['details', 'member.user', 'assessor', 'ambalan']);
        return Inertia::render('Assessments/Show', ['assessment' => $assessment]);
    }

    public function storeDetail(Request $request, Assessment $assessment): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina'], true), 403);

        $data = $request->validate([
            'kategori' => ['required', 'string', 'max:255'],
            'deskripsi' => ['required', 'string'],
            'nilai' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $assessment->details()->create($data);

        return back()->with('success', 'Detail penilaian ditambahkan.');
    }
}
