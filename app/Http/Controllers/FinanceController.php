<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Finance;
use App\Models\FinanceCategory;
use App\Models\FinancePeriod;
use App\Models\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinanceController extends Controller
{
    protected function isFinanceManager($user): bool
    {
        if (in_array($user->role, ['Admin', 'Pembina'], true)) {
            return true;
        }

        return $user->member?->memberPositions()
            ->whereHas('position', fn ($q) => $q->whereIn('code', ['juru_uang_putra', 'juru_uang_putri']))
            ->exists();
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $isJuruUang = $user->member?->memberPositions()
            ->whereHas('position', fn ($q) => $q->whereIn('code', ['juru_uang_putra', 'juru_uang_putri']))
            ->exists();

        $query = Finance::query()
            ->with(['member', 'category', 'period', 'createdBy'])
            ->orderByDesc('tgl_transaksi');

        if ($user->role === 'Anggota' || ! $isJuruUang && $user->role === 'Pengurus') {
            $query->where('member_id', $user->member?->id);
        }

        if ($request->string('jenis_transaksi')->isNotEmpty()) {
            $query->where('jenis_transaksi', $request->string('jenis_transaksi'));
        }

        if ($request->string('status')->isNotEmpty()) {
            $query->where('status', $request->string('status'));
        }

        $transactions = $query->paginate(20)->withQueryString();
        $categories = FinanceCategory::where('is_active', true)->orderBy('nama')->get();
        $periods = FinancePeriod::where('is_closed', false)->orderByDesc('starts_at')->get();
        $members = Member::where('status_aktif', 'Aktif')->orderBy('nama_lengkap')->get();
        $balance = Finance::where('status', 'Posted')->where('jenis_transaksi', 'Masuk')->sum('nominal')
            - Finance::where('status', 'Posted')->where('jenis_transaksi', 'Keluar')->sum('nominal');
        $income = Finance::where('status', 'Posted')->where('jenis_transaksi', 'Masuk')->sum('nominal');
        $expense = Finance::where('status', 'Posted')->where('jenis_transaksi', 'Keluar')->sum('nominal');

        $canManage = in_array($user->role, ['Admin', 'Pembina']) || $isJuruUang;

        $cashFlowQuery = Finance::where('status', 'Posted')
            ->whereBetween('tgl_transaksi', [now()->subMonths(6)->startOfMonth(), now()->endOfMonth()]);
        if ($user->role === 'Anggota' || ! $isJuruUang && $user->role === 'Pengurus') {
            $cashFlowQuery->where('member_id', $user->member?->id);
        }
        $chartLabels = [];
        $chartIncome = [];
        $chartExpense = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i)->format('Y-m');
            $chartLabels[] = now()->subMonths($i)->format('MMM Y');
            $in = (clone $cashFlowQuery)->whereRaw("DATE_FORMAT(tgl_transaksi, '%Y-%m') = ?", [$month])->where('jenis_transaksi', 'Masuk')->sum('nominal');
            $out = (clone $cashFlowQuery)->whereRaw("DATE_FORMAT(tgl_transaksi, '%Y-%m') = ?", [$month])->where('jenis_transaksi', 'Keluar')->sum('nominal');
            $chartIncome[] = (float) $in;
            $chartExpense[] = (float) $out;
        }

        return Inertia::render('Finance/Index', [
            'transactions' => $transactions,
            'categories' => $categories,
            'periods' => $periods,
            'members' => $members,
            'balance' => $balance,
            'income' => $income,
            'expense' => $expense,
            'canManage' => $canManage,
            'isJuruUang' => $isJuruUang,
            'memberId' => $user->member?->id,
            'chartLabels' => $chartLabels,
            'chartIncome' => $chartIncome,
            'chartExpense' => $chartExpense,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $isJuruUang = $user->member?->memberPositions()
            ->whereHas('position', fn ($q) => $q->whereIn('code', ['juru_uang_putra', 'juru_uang_putri']))
            ->exists();
        $isManager = in_array($user->role, ['Admin', 'Pembina']) || $isJuruUang;

        // Anggota bisa submit pembayaran iuran, otomatis "Posted"
        if ($user->role === 'Anggota') {
            $data = $request->validate([
                'nominal' => ['required', 'numeric', 'min:1', 'max:9999999999.99'],
                'keterangan' => ['required', 'string', 'max:2000'],
                'period_id' => ['nullable', 'exists:finance_periods,id'],
            ]);

            $iuranCategory = FinanceCategory::where('nama', 'Iuran Wajib')->first();

            $transaction = Finance::create([
                'ambalan_id' => $user->member?->ambalan_id ?? Ambalan::first()?->id,
                'member_id' => $user->member?->id,
                'category_id' => $iuranCategory?->id,
                'period_id' => $data['period_id'] ?? null,
                'jenis_transaksi' => 'Masuk',
                'nominal' => $data['nominal'],
                'keterangan' => 'Pembayaran iuran: '.$data['keterangan'],
                'tgl_transaksi' => now()->toDateString(),
                'created_by' => $user->id,
                'status' => 'Posted',
                'receipt_no' => 'KAS-'.now()->format('Ymd').'-'.str_pad((string) Finance::max('id') + 1, 5, '0', STR_PAD_LEFT),
            ]);
            AuditLog::create([
                'actor_id' => $user->id,
                'action' => 'finance.member_payment',
                'entity_type' => Finance::class,
                'entity_id' => $transaction->id,
                'metadata' => ['nominal' => $data['nominal']],
                'ip_address' => $request->ip(),
            ]);

            return back()->with('success', 'Pembayaran iuran berhasil disimpan.');
        }

        // Manager (Admin/Pembina/Juru Uang) - full management
        abort_unless($isManager, 403);
        $data = $request->validate([
            'ambalan_id' => ['nullable', 'exists:ambalans,id'],
            'member_id' => ['nullable', 'exists:members,id'],
            'category_id' => ['nullable', 'exists:finance_categories,id'],
            'period_id' => ['nullable', 'exists:finance_periods,id'],
            'jenis_transaksi' => ['required', 'in:Masuk,Keluar'],
            'nominal' => ['required', 'numeric', 'min:1', 'max:9999999999.99'],
            'keterangan' => ['required', 'string', 'max:2000'],
            'tgl_transaksi' => ['required', 'date'],
        ]);
        abort_if(isset($data['period_id']) && FinancePeriod::findOrFail($data['period_id'])->is_closed, 422, 'Periode keuangan sudah ditutup.');

        $transaction = Finance::create([
            ...$data,
            'created_by' => $request->user()->id,
            'status' => 'Draft',
        ]);
        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'finance.created',
            'entity_type' => Finance::class,
            'entity_id' => $transaction->id,
            'metadata' => ['nominal' => $data['nominal']],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('finance.index')->with('success', 'Transaksi berhasil ditambahkan.');
    }

    public function update(Request $request, Finance $finance): RedirectResponse
    {
        abort_unless($this->isFinanceManager($request->user()), 403);
        abort_if($finance->status !== 'Draft', 422, 'Transaksi yang sudah diposting tidak dapat diubah.');
        $data = $request->validate([
            'member_id' => ['nullable', 'exists:members,id'],
            'category_id' => ['nullable', 'exists:finance_categories,id'],
            'period_id' => ['nullable', 'exists:finance_periods,id'],
            'jenis_transaksi' => ['required', 'in:Masuk,Keluar'],
            'nominal' => ['required', 'numeric', 'min:1', 'max:9999999999.99'],
            'keterangan' => ['required', 'string', 'max:2000'],
            'tgl_transaksi' => ['required', 'date'],
        ]);
        $finance->update($data);

        return redirect()->route('finance.index')->with('success', 'Transaksi berhasil diperbarui.');
    }

    public function destroy(Request $request, Finance $finance): RedirectResponse
    {
        abort_unless($this->isFinanceManager($request->user()), 403);
        abort_if($finance->status !== 'Draft', 422, 'Transaksi yang sudah diposting tidak dapat dihapus.');
        $finance->delete();

        return redirect()->route('finance.index')->with('success', 'Transaksi draft berhasil dihapus.');
    }

    public function post(Request $request, Finance $finance): RedirectResponse
    {
        abort_unless($this->isFinanceManager($request->user()), 403);
        abort_if($finance->status !== 'Draft', 422);
        $finance->update([
            'status' => 'Posted',
            'receipt_no' => 'KAS-'.now()->format('Ymd').'-'.str_pad((string) $finance->id, 5, '0', STR_PAD_LEFT),
        ]);
        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'finance.posted',
            'entity_type' => Finance::class,
            'entity_id' => $finance->id,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('finance.index')->with('success', 'Transaksi berhasil diposting.');
    }

    public function reverse(Request $request, Finance $finance): RedirectResponse
    {
        abort_unless($this->isFinanceManager($request->user()), 403);
        abort_if($finance->status !== 'Posted', 422);
        Finance::create([
            'ambalan_id' => $finance->ambalan_id,
            'member_id' => $finance->member_id,
            'category_id' => $finance->category_id,
            'period_id' => $finance->period_id,
            'jenis_transaksi' => $finance->jenis_transaksi === 'Masuk' ? 'Keluar' : 'Masuk',
            'nominal' => $finance->nominal,
            'keterangan' => 'Pembalikan transaksi '.$finance->receipt_no,
            'status' => 'Posted',
            'receipt_no' => 'REV-'.now()->format('Ymd').'-'.str_pad((string) $finance->id, 5, '0', STR_PAD_LEFT),
            'created_by' => $request->user()->id,
            'tgl_transaksi' => now()->toDateString(),
        ]);
        $finance->update(['status' => 'Reversed']);

        return redirect()->route('finance.index')->with('success', 'Transaksi berhasil dibalik dengan jurnal pembalik.');
    }
}
