<?php

namespace App\Http\Controllers;

use App\Models\SystemPoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        $totalPoints = SystemPoint::where('ambalan_id', Auth::user()->ambalan_id)
            ->sum('poin');

        return inertia('SystemPoints/Index', [
            'points' => $points,
            'totalPoints' => $totalPoints,
            'filters' => $request->only(['kategori', 'member_id']),
            'user' => Auth::user(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'member_id' => 'required|exists:members,id',
            'kategori' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'poin' => 'required|integer',
        ]);

        SystemPoint::create([
            'ambalan_id' => Auth::user()->ambalan_id,
            'member_id' => $request->member_id,
            'kategori' => $request->kategori,
            'deskripsi' => $request->deskripsi,
            'poin' => $request->poin,
        ]);

        return redirect()->back()->with('success', 'Point added successfully.');
    }

    public function destroy(SystemPoint $point)
    {
        $point->delete();

        return redirect()->back()->with('success', 'Point deleted successfully.');
    }
}
