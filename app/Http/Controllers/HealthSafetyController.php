<?php

namespace App\Http\Controllers;

use App\Models\Ambalan;
use App\Models\HealthRecord;
use App\Models\SafetyCheck;
use Illuminate\Http\Request;

class HealthSafetyController extends Controller
{
    public function healthRecords(Request $request)
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);

        $query = HealthRecord::query()->with(['member', 'createdBy']);

        if ($request->filled('member_id')) {
            $query->where('member_id', $request->member_id);
        }

        $records = $query->paginate(20);

        return inertia('HealthSafety/HealthRecords', [
            'records' => $records,
            'filters' => $request->only(['member_id']),
            'user' => $request->user(),
        ]);
    }

    public function storeHealthRecord(Request $request)
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);

        $request->validate([
            'member_id' => 'required|exists:members,id',
            'riwayat_penyakit' => 'nullable|string',
            'alergi' => 'nullable|string',
            'darah' => 'nullable|string',
            'tinggi_badan' => 'nullable|string',
            'berat_badan' => 'nullable|string',
            'catatan_tambahan' => 'nullable|string',
        ]);

        HealthRecord::create([
            'ambalan_id' => $request->user()->member?->ambalan_id ?? Ambalan::first()?->id,
            'member_id' => $request->member_id,
            'created_by' => $request->user()->id,
            'riwayat_penyakit' => $request->riwayat_penyakit,
            'alergi' => $request->alergi,
            'darah' => $request->darah,
            'tinggi_badan' => $request->tinggi_badan,
            'berat_badan' => $request->berat_badan,
            'catatan_tambahan' => $request->catatan_tambahan,
        ]);

        return redirect()->back()->with('success', 'Health record saved successfully.');
    }

    public function updateHealthRecord(Request $request, HealthRecord $record)
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);

        $record->update($request->only(['riwayat_penyakit', 'alergi', 'darah', 'tinggi_badan', 'berat_badan', 'catatan_tambahan']));

        return redirect()->back()->with('success', 'Health record updated successfully.');
    }

    public function safetyChecks(Request $request)
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);

        $query = SafetyCheck::query()->with(['event', 'createdBy']);

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }
        if ($request->filled('passed')) {
            $query->where('passed', $request->passed);
        }

        $checks = $query->paginate(20);

        return inertia('HealthSafety/SafetyChecks', [
            'checks' => $checks,
            'filters' => $request->only(['kategori', 'passed']),
            'user' => $request->user(),
        ]);
    }

    public function storeSafetyCheck(Request $request)
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);

        $request->validate([
            'kategori' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'event_id' => 'nullable|exists:events,id',
            'passed' => 'required|boolean',
        ]);

        SafetyCheck::create([
            'ambalan_id' => $request->user()->member?->ambalan_id ?? Ambalan::first()?->id,
            'event_id' => $request->event_id,
            'created_by' => $request->user()->id,
            'kategori' => $request->kategori,
            'deskripsi' => $request->deskripsi,
            'passed' => $request->passed,
        ]);

        return redirect()->back()->with('success', 'Safety check saved successfully.');
    }
}