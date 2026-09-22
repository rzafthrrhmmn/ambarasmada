<?php

namespace App\Http\Controllers;

use App\Models\HealthRecord;
use App\Models\SafetyCheck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HealthSafetyController extends Controller
{
    public function healthRecords(Request $request)
    {
        $query = HealthRecord::query()->with(['member', 'createdBy']);

        if ($request->filled('member_id')) {
            $query->where('member_id', $request->member_id);
        }

        $records = $query->paginate(20);

        return inertia('HealthSafety/HealthRecords', [
            'records' => $records,
            'filters' => $request->only(['member_id']),
            'user' => Auth::user(),
        ]);
    }

    public function storeHealthRecord(Request $request)
    {
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
            'ambalan_id' => Auth::user()->ambalan_id,
            'member_id' => $request->member_id,
            'created_by' => Auth::id(),
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
        $record->update($request->only(['riwayat_penyakit', 'alergi', 'darah', 'tinggi_badan', 'berat_badan', 'catatan_tambahan']));

        return redirect()->back()->with('success', 'Health record updated successfully.');
    }

    public function safetyChecks(Request $request)
    {
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
            'user' => Auth::user(),
        ]);
    }

    public function storeSafetyCheck(Request $request)
    {
        $request->validate([
            'kategori' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'event_id' => 'nullable|exists:events,id',
            'passed' => 'required|boolean',
        ]);

        SafetyCheck::create([
            'ambalan_id' => Auth::user()->ambalan_id,
            'event_id' => $request->event_id,
            'created_by' => Auth::id(),
            'kategori' => $request->kategori,
            'deskripsi' => $request->deskripsi,
            'passed' => $request->passed,
        ]);

        return redirect()->back()->with('success', 'Safety check saved successfully.');
    }
}
