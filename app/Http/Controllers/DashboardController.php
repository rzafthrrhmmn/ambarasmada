<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Finance;
use App\Models\Member;
use App\Models\MemberTku;
use App\Models\SkuPoint;
use App\Models\SkuSubmission;
use App\Models\TkkPoint;
use App\Models\TkkSubmission;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $member = Member::where('user_id', $user->id)->first();
        $announcements = Announcement::whereNotNull('published_at')
            ->orderByDesc('published_at')
            ->limit(5)
            ->get();

        $stats = $this->stats($user, $member);
        $pendingSku = SkuSubmission::where('status', 'Pending')
            ->with(['member', 'skuPoint'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $upcomingSessions = AttendanceSession::query()
            ->where('tanggal', '>=', now()->toDateString())
            ->where('tanggal', '<=', now()->addDays(30)->toDateString())
            ->withCount('attendances')
            ->orderBy('tanggal')
            ->get();

        $attendedSessionIds = $member
            ? Attendance::where('member_id', $member->id)
                ->whereIn('attendance_session_id', $upcomingSessions->pluck('id'))
                ->pluck('attendance_session_id')
                ->all()
            : null;

        $tkkPoints = [];
        $tkuData = [];
        $skuPointsByLevel = [];

        if (in_array($user->role, ['Admin', 'Pembina'], true)) {
            $tkkPoints = TkkPoint::where('is_active', true)
                ->orderBy('nama')
                ->get()
                ->map(function ($point) {
                    $aggregate = TkkSubmission::where('tkk_point_id', $point->id)
                        ->selectRaw('status, count(*) as cnt')
                        ->groupBy('status')
                        ->get()
                        ->pluck('cnt', 'status')
                        ->toArray();

                    return [
                        'id' => $point->id,
                        'nama' => $point->nama,
                        'slug' => $point->slug,
                        'deskripsi' => $point->deskripsi,
                        'is_active' => $point->is_active,
                        'aggregate' => $aggregate,
                    ];
                });

            $tkuData = MemberTku::with(['member.user', 'awardedBy'])
                ->orderByDesc('awarded_at')
                ->get()
                ->map(function ($tku) {
                    return [
                        'id' => $tku->id,
                        'member' => $tku->member,
                        'tingkatan' => $tku->tingkatan,
                        'awarded_at' => $tku->awarded_at,
                        'catatan' => $tku->catatan,
                        'awarded_by' => $tku->awardedBy,
                    ];
                });

            $skuPointsByLevel = SkuPoint::where('is_active', true)
                ->orderBy('tingkatan')
                ->orderBy('nomor_poin')
                ->get()
                ->groupBy('tingkatan')
                ->map(function ($points) {
                    return $points->map(function ($point) {
                        $aggregate = SkuSubmission::where('sku_point_id', $point->id)
                            ->selectRaw('status, count(*) as cnt')
                            ->groupBy('status')
                            ->get()
                            ->pluck('cnt', 'status')
                            ->toArray();

                        return [
                            'id' => $point->id,
                            'tingkatan' => $point->tingkatan,
                            'nomor_poin' => $point->nomor_poin,
                            'deskripsi_poin' => $point->deskripsi_poin,
                            'is_active' => $point->is_active,
                            'aggregate' => $aggregate,
                        ];
                    });
                })
                ->toArray();
        }

        return Inertia::render('Dashboard', [
            'member' => $member,
            'announcements' => $announcements,
            'stats' => $stats,
            'pendingSku' => $pendingSku,
            'upcomingSessions' => $upcomingSessions,
            'attendedSessionIds' => $attendedSessionIds,
            'tkkPoints' => $tkkPoints,
            'tkuData' => $tkuData,
            'skuPointsByLevel' => $skuPointsByLevel,
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function stats($user, ?Member $member): array
    {
        if ($user->role === 'Anggota' && $member) {
            return [
                'members' => 0,
                'attendance' => $member->attendances()->count(),
                'sku' => $member->approvedSkuCount(),
                'finance' => $member->finances()->count(),
            ];
        }

        if ($user->role === 'Pengurus' && $member) {
            return [
                'members' => 0,
                'attendance' => $member->attendances()->count(),
                'sku' => $member->approvedSkuCount(),
                'finance' => $member->finances()->count(),
            ];
        }

        return [
            'members' => Member::count(),
            'attendance' => Attendance::count(),
            'sku' => SkuSubmission::where('status', 'Pending')->count(),
            'finance' => Finance::count(),
        ];
    }
}
