<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MemberPosition;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MemberPositionController extends Controller
{
    public function store(Request $request, Member $member): RedirectResponse
    {
        abort_unless($request->user()->role === 'Pembina', 403);
        abort_unless($member->user->role === 'Pengurus', 403, 'Hanya bisa menetapkan posisi untuk anggota dengan role Pengurus.');

        $data = $request->validate([
            'position_id' => ['required', 'exists:pengurus_positions,id'],
        ]);

        MemberPosition::create([
            'member_id' => $member->id,
            'position_id' => $data['position_id'],
            'assigned_by_user_id' => $request->user()->id,
        ]);

        return redirect()->back()->with('success', 'Posisi berhasil ditetapkan.');
    }

    public function destroy(Request $request, MemberPosition $position): RedirectResponse
    {
        abort_unless($request->user()->role === 'Pembina', 403);
        $position->delete();

        return redirect()->back()->with('success', 'Posisi berhasil dihapus.');
    }

    public function bulkAssign(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === 'Pembina', 403);
        $data = $request->validate([
            'angkatan' => ['required', 'string', 'size:3', 'regex:/^\d{3}$/', 'exists:angkatans,nomor'],
            'position_id' => ['required', 'exists:pengurus_positions,id'],
        ]);

        $members = Member::where('angkatan', $data['angkatan'])
            ->whereHas('user', fn ($q) => $q->where('role', 'Pengurus'))
            ->get();

        $assigned = 0;
        DB::transaction(function () use ($members, $data, &$assigned, $request) {
            foreach ($members as $member) {
                try {
                    MemberPosition::create([
                        'member_id' => $member->id,
                        'position_id' => $data['position_id'],
                        'assigned_by_user_id' => $request->user()->id,
                    ]);
                    $assigned++;
                } catch (QueryException $e) {
                    if ($e->getCode() !== '23000') {
                        throw $e;
                    }
                }
            }
        });

        return redirect()->back()->with('success', "Posisi berhasil ditetapkan untuk {$assigned} anggota pengurus angkatan {$data['angkatan']}.");
    }
}
