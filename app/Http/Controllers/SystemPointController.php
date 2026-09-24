<?php

namespace App\Http\Controllers;

use App\Models\SystemPoint;
use App\Models\Ambalan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SystemPointController extends Controller
{
    public function index(Request $request)
    {
        $query = SystemPoint::query()->with(['member', 'ambalan']);

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }
        if ($request->filled('member_id')) {
            $query->where('member_id', $request->member_id);
        }

        $points = $query->orderByDesc('created_at')->paginate(20);

        $ambalanId = $request->user()->member?->ambalan_id ?? Ambalan::first()?->id;
        $totalPoints = SystemPoint::where('ambalan_id', $ambalanId)
            ->sum('poin');

        return Inertia::render('SystemPoints/Index', [
            'points' => $points,
            'totalPoints' => $totalPoints,
            'filters' => $request->only(['kategori', 'member_id']),
            'user' => $request->user(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);

        $request->validate([
            'member_id' => 'required|exists:members,id',
            'kategori' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'poin' => 'required|integer',
        ]);

        SystemPoint::create([
            'ambalan_id' => $request->user()->member?->ambalan_id ?? Ambalan::first()?->id,
            'member_id' => $request->member_id,
            'kategori' => $request->kategori,
            'deskripsi' => $request->deskripsi,
            'poin' => $request->poin,
        ]);

        return redirect()->back()->with('success', 'Point added successfully.');
    }

    public function destroy(Request $request, SystemPoint $point)
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);

        $point->delete();

        return redirect()->back()->with('success', 'Point deleted successfully.');
    }
}