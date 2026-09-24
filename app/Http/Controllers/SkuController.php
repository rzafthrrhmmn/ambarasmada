<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Member;
use App\Models\SkuPoint;
use App\Models\SkuSubmission;
use App\Models\SkuSubmissionMedia;
use App\Models\TkkPoint;
use App\Models\TkkSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SkuController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $member = Member::where('user_id', $user->id)->first();

        $query = SkuSubmission::query()
            ->with(['member.user', 'skuPoint', 'verifiedBy', 'media'])
            ->orderByDesc('created_at');

        if ($user->role === 'Anggota' && $member) {
            $query->where('member_id', $member->id);
        }

        if ($request->string('status')->isNotEmpty()) {
            $query->where('status', $request->string('status'));
        }

        $submissions = $query->paginate(20)->withQueryString();
        $pointsQuery = SkuPoint::where('is_active', true);
        $completed = false;

        if ($user->role === 'Anggota') {
            abort_unless($member, 403, 'Anggota tidak memiliki data member.');

            $tingkatan = $member->tingkatan;
            if ($tingkatan === 'Bantara') {
                $pointsQuery->where('tingkatan', 'Laksana');
            } elseif ($tingkatan === 'Laksana') {
                $pointsQuery->whereRaw('1=0');
            } else {
                $pointsQuery->where('tingkatan', 'Bantara');
            }
        } else {
            if ($request->string('tingkatan')->isNotEmpty()) {
                $pointsQuery->where('tingkatan', $request->string('tingkatan'));
            }
        }

        $points = $pointsQuery->orderBy('tingkatan')
            ->orderBy('nomor_poin')
            ->get();

        $skuPointsByLevel = [];
        $tkkPointsWithStatus = [];
        $memberSubmissions = $user->role === 'Anggota' && $member
            ? $member->skuSubmissions()->pluck('status', 'sku_point_id')
            : [];
        $memberTkkSubmissions = $user->role === 'Anggota' && $member
            ? $member->tkkSubmissions()->pluck('status', 'tkk_point_id')
            : [];

        foreach ($points as $point) {
            $skuPointsByLevel[$point->tingkatan][] = [
                'id' => $point->id,
                'nomor_poin' => $point->nomor_poin,
                'deskripsi_poin' => $point->deskripsi_poin,
                'status' => $memberSubmissions[$point->id] ?? null,
            ];
        }

        $tkkPointsWithStatus = [];

        if ($user->role === 'Anggota' && $member && $member->tingkatan === 'Laksana') {
            $activeTkkCount = TkkPoint::where('is_active', true)->count();
            $approvedTkkCount = $member->tkkSubmissions()
                ->where('status', 'Approved')
                ->count();

            if ($activeTkkCount > 0 && $approvedTkkCount >= $activeTkkCount) {
                $completed = true;
            }
        }

        if (! $completed) {
            $tkkPoints = TkkPoint::where('is_active', true)
                ->orderBy('nama')
                ->get();

            $tkkAggregate = [];
            if ($user->role !== 'Anggota') {
                $tkkPointIds = $tkkPoints->pluck('id')->toArray();
                $aggregate = TkkSubmission::whereIn('tkk_point_id', $tkkPointIds)
                    ->selectRaw('tkk_point_id, status, count(*) as cnt')
                    ->groupBy('tkk_point_id', 'status')
                    ->get();

                foreach ($aggregate as $row) {
                    $tkkAggregate[$row->tkk_point_id][$row->status] = $row->cnt;
                }
            }

            foreach ($tkkPoints as $tkkPoint) {
                $tkkPointsWithStatus[] = [
                    'id' => $tkkPoint->id,
                    'nama' => $tkkPoint->nama,
                    'deskripsi' => $tkkPoint->deskripsi,
                    'is_active' => $tkkPoint->is_active,
                    'status' => $memberTkkSubmissions[$tkkPoint->id] ?? null,
                    'aggregate' => $tkkAggregate[$tkkPoint->id] ?? null,
                ];
            }
        }

        $progress = $member ? $this->progress($member) : null;

        $pendingCount = SkuSubmission::where('status', 'Pending')->count();
        $approvedCount = SkuSubmission::where('status', 'Approved')->count();
        $rejectedCount = SkuSubmission::where('status', 'Rejected')->count();

        return Inertia::render('Sku/Index', [
            'submissions' => $submissions,
            'points' => $points,
            'progress' => $progress,
            'skuPointsByLevel' => $skuPointsByLevel,
            'tkkPointsWithStatus' => $tkkPointsWithStatus,
            'submissionStats' => [
                'pending' => $pendingCount,
                'approved' => $approvedCount,
                'rejected' => $rejectedCount,
            ],
            'completed' => $completed,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === 'Anggota', 403);

        $data = $request->validate([
            'sku_point_id' => ['required', 'exists:sku_points,id'],
            'description' => ['required', 'string', 'max:2000'],
            'bukti_kegiatan' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,pdf,doc,docx'],
            'foto' => ['nullable', 'array', 'max:6'],
            'foto.*' => ['image', 'max:5120', 'mimes:jpg,jpeg,png,webp'],
        ]);

        $member = Member::where('user_id', $request->user()->id)->firstOrFail();

        $point = SkuPoint::where('id', $data['sku_point_id'])
            ->where('is_active', true)
            ->firstOrFail();

        abort_unless($point->tingkatan === $member->tingkatan, 422, 'Poin SKU tidak sesuai dengan tingkatan anggota.');

        $path = null;
        if ($request->hasFile('bukti_kegiatan')) {
            $path = $request->file('bukti_kegiatan')->store('sku-evidence', 'public');
        }

        $submission = SkuSubmission::withTrashed()
            ->where('member_id', $member->id)
            ->where('sku_point_id', $point->id)
            ->first();

        $submission = DB::transaction(function () use ($submission, $data, $member, $point, $path, $request): SkuSubmission {
            $foto = $request->file('foto', []);
            $mediaPaths = [];
            foreach ($foto as $file) {
                $mediaPaths[] = $file->store('sku-evidence', 'public');
            }

            if ($submission) {
                abort_if($submission->status !== 'Rejected', 422, 'Poin SKU ini sudah pernah diajukan.');
                $submission->update([
                    'bukti_kegiatan' => $path,
                    'status' => 'Pending',
                    'catatan' => $data['description'],
                    'verified_by' => null,
                    'tgl_verifikasi' => null,
                    'deleted_at' => null,
                ]);
                $submission->media()->delete();
            } else {
                $submission = SkuSubmission::create([
                    'member_id' => $member->id,
                    'sku_point_id' => $point->id,
                    'bukti_kegiatan' => $path,
                    'status' => 'Pending',
                    'catatan' => $data['description'],
                ]);
            }

            foreach ($mediaPaths as $mediaPath) {
                SkuSubmissionMedia::create([
                    'sku_submission_id' => $submission->id,
                    'file_path' => $mediaPath,
                    'tipe' => 'photo',
                ]);
            }

            return $submission;
        });

        $submission->load('media');

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'sku.submitted',
            'entity_type' => SkuSubmission::class,
            'entity_id' => $submission->id,
            'metadata' => ['sku_point_id' => $point->id],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('sku.index')->with('success', 'Pengajuan SKU berhasil dikirim.');
    }

    public function update(Request $request, SkuPoint $skuPoint): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina'], true), 403);

        $data = $request->validate([
            'tingkatan' => ['required', 'in:Bantara,Laksana'],
            'nomor_poin' => [
                'required',
                'integer',
                'min:1',
                'max:999',
                Rule::unique('sku_points', 'nomor_poin')
                    ->where(fn ($query) => $query->where('tingkatan', $request->input('tingkatan')))
                    ->ignore($skuPoint->id),
            ],
            'deskripsi_poin' => ['required', 'string', 'max:5000'],
            'is_active' => ['required', 'boolean'],
        ]);

        $skuPoint->update($data);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'sku.point.updated',
            'entity_type' => SkuPoint::class,
            'entity_id' => $skuPoint->id,
            'metadata' => ['tingkatan' => $skuPoint->tingkatan, 'nomor_poin' => $skuPoint->nomor_poin],
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Poin SKU berhasil diperbarui.');
    }

    public function storePoint(Request $request): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina'], true), 403);

        $data = $request->validate([
            'tingkatan' => ['required', 'in:Bantara,Laksana'],
            'nomor_poin' => [
                'required',
                'integer',
                'min:1',
                'max:999',
                Rule::unique('sku_points', 'nomor_poin')
                    ->where(fn ($query) => $query->where('tingkatan', $request->input('tingkatan'))),
            ],
            'deskripsi_poin' => ['required', 'string', 'max:5000'],
            'is_active' => ['required', 'boolean'],
        ]);

        $skuPoint = SkuPoint::create($data);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'sku.point.created',
            'entity_type' => SkuPoint::class,
            'entity_id' => $skuPoint->id,
            'metadata' => ['tingkatan' => $skuPoint->tingkatan, 'nomor_poin' => $skuPoint->nomor_poin],
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Poin SKU berhasil ditambahkan.');
    }

    public function approve(Request $request, SkuSubmission $skuSubmission): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);

        $data = $request->validate(['catatan' => ['nullable', 'string', 'max:2000']]);

        $skuSubmission->update([
            'status' => 'Approved',
            'catatan' => $data['catatan'] ?: null,
            'verified_by' => $request->user()->id,
            'tgl_verifikasi' => now(),
        ]);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'sku.approved',
            'entity_type' => SkuSubmission::class,
            'entity_id' => $skuSubmission->id,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('sku.index')->with('success', 'SKU berhasil disetujui.');
    }

    public function reject(Request $request, SkuSubmission $skuSubmission): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);

        $data = $request->validate(['catatan' => ['required', 'string', 'max:2000']]);

        $skuSubmission->update([
            'status' => 'Rejected',
            'catatan' => $data['catatan'],
            'verified_by' => $request->user()->id,
            'tgl_verifikasi' => now(),
        ]);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'sku.rejected',
            'entity_type' => SkuSubmission::class,
            'entity_id' => $skuSubmission->id,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('sku.index')->with('success', 'SKU ditolak dan catatan koreksi disimpan.');
    }

    public function cancel(Request $request, SkuSubmission $submission): RedirectResponse
    {
        $member = Member::where('user_id', $request->user()->id)->first();
        abort_if(! $member || $submission->member_id !== $member->id, 403);
        abort_if($submission->status !== 'Pending', 422);
        $submission->delete();

        return redirect()->route('sku.index')->with('success', 'Pengajuan SKU dibatalkan.');
    }

    /**
     * @return array<string, int|float|null>
     */
    private function progress(Member $member): array
    {
        $total = SkuPoint::where('tingkatan', $member->tingkatan)
            ->where('is_active', true)
            ->count();
        $approved = $member->skuSubmissions()
            ->where('status', 'Approved')
            ->count();

        return [
            'approved' => $approved,
            'total' => $total,
            'percentage' => $total ? round(($approved / $total) * 100) : 0,
        ];
    }
}