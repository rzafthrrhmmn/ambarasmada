<?php

namespace App\Http\Controllers;

use App\Models\Ambalan;
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
            $date = now()->subMonths($i);
            $month = (int) $date->format('m');
            $year = (int) $date->format('Y');
            $chartLabels[] = $date->format('MMM Y');
            $in = (clone $cashFlowQuery)->whereMonth('tgl_transaksi', $month)->whereYear('tgl_transaksi', $year)->where('jenis_transaksi', 'Masuk')->sum('nominal');
            $out = (clone $cashFlowQuery)->whereMonth('tgl_transaksi', $month)->whereYear('tgl_transaksi', $year)->where('jenis_transaksi', 'Keluar')->sum('nominal');
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
                'receipt_no' => 'KAS-'.now()->format('Ymd').'-'.str_pad((string) ((int) Finance::max('id') + 1), 5, '0', STR_PAD_LEFT),
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

    public function categories(Request $request): Response
    {
        abort_unless($this->isFinanceManager($request->user()), 403);

        $categories = FinanceCategory::query()
            ->with('ambalan')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $ambalans = Ambalan::orderBy('nama')->get();

        return Inertia::render('Finance/Categories', [
            'categories' => $categories,
            'ambalans' => $ambalans,
            'filters' => $request->only(['search']),
        ]);
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        abort_unless($this->isFinanceManager($request->user()), 403);

        $data = $request->validate([
            'ambalan_id' => ['nullable', 'exists:ambalans,id'],
            'nama' => ['required', 'string', 'max:255'],
            'jenis' => ['required', 'in:Masuk,Keluar', 'max:30'],
            'is_active' => ['boolean'],
        ]);

        FinanceCategory::create([
            'ambalan_id' => $data['ambalan_id'] ?? $request->user()->member?->ambalan_id ?? Ambalan::first()?->id,
            'nama' => $data['nama'],
            'jenis' => $data['jenis'],
            'is_active' => $data['is_active'] ?? true,
        ]);

        return redirect()->route('finance.categories.index')->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function updateCategory(Request $request, FinanceCategory $category): RedirectResponse
    {
        abort_unless($this->isFinanceManager($request->user()), 403);

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'jenis' => ['required', 'in:Masuk,Keluar', 'max:30'],
            'is_active' => ['boolean'],
        ]);

        $category->update($data);

        return redirect()->route('finance.categories.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function toggleCategory(Request $request, FinanceCategory $category): RedirectResponse
    {
        abort_unless($this->isFinanceManager($request->user()), 403);

        $category->update(['is_active' => ! $category->is_active]);

        return redirect()->route('finance.categories.index')->with('success', 'Status kategori berhasil diubah.');
    }

    public function destroyCategory(Request $request, FinanceCategory $category): RedirectResponse
    {
        abort_unless($this->isFinanceManager($request->user()), 403);
        abort_if($category->finances()->exists(), 422, 'Kategori yang sudah digunakan tidak dapat dihapus.');

        $category->delete();

        return redirect()->route('finance.categories.index')->with('success', 'Kategori berhasil dihapus.');
    }

    public function periods(Request $request): Response
    {
        abort_unless($this->isFinanceManager($request->user()), 403);

        $query = FinancePeriod::query()
            ->with('ambalan')
            ->orderByDesc('starts_at');

        if ($request->filled('search')) {
            $query->where('nama', 'like', '%'.$request->string('search').'%');
        }

        $periods = $query->paginate(20)->withQueryString();

        $ambalans = Ambalan::orderBy('nama')->get();

        return Inertia::render('Finance/Periods', [
            'periods' => $periods,
            'ambalans' => $ambalans,
            'filters' => $request->only(['search']),
        ]);
    }

    public function storePeriod(Request $request): RedirectResponse
    {
        abort_unless($this->isFinanceManager($request->user()), 403);

        $data = $request->validate([
            'ambalan_id' => ['nullable', 'exists:ambalans,id'],
            'nama' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ]);

        FinancePeriod::create([
            'ambalan_id' => $data['ambalan_id'] ?? $request->user()->member?->ambalan_id ?? Ambalan::first()?->id,
            'nama' => $data['nama'],
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'is_closed' => false,
        ]);

        return redirect()->route('finance.periods.index')->with('success', 'Periode keuangan berhasil ditambahkan.');
    }

    public function updatePeriod(Request $request, FinancePeriod $period): RedirectResponse
    {
        abort_unless($this->isFinanceManager($request->user()), 403);
        abort_if($period->is_closed, 422, 'Periode yang sudah ditutup tidak dapat diubah.');

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ]);

        $period->update($data);

        return redirect()->route('finance.periods.index')->with('success', 'Periode keuangan berhasil diperbarui.');
    }

    public function closePeriod(Request $request, FinancePeriod $period): RedirectResponse
    {
        abort_unless($this->isFinanceManager($request->user()), 403);
        abort_if($period->is_closed, 422, 'Periode sudah ditutup.');

        $period->update(['is_closed' => true]);

        return redirect()->route('finance.periods.index')->with('success', 'Periode keuangan berhasil ditutup.');
    }

    public function destroyPeriod(Request $request, FinancePeriod $period): RedirectResponse
    {
        abort_unless($this->isFinanceManager($request->user()), 403);
        abort_if($period->finances()->exists(), 422, 'Periode yang sudah memiliki transaksi tidak dapat dihapus.');

        $period->delete();

        return redirect()->route('finance.periods.index')->with('success', 'Periode keuangan berhasil dihapus.');
    }
}
