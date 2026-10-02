<?php

namespace App\Http\Controllers;

use App\Models\Ambalan;
use App\Models\HealthRecord;
use App\Models\Member;
use App\Models\SafetyCheck;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Response;

/**
 * Rekam medis dan pemeriksaan keselamatan.
 *
 * Data rekam medis adalah data pribadi yang paling sensitif di aplikasi ini:
 * riwayat penyakit, alergi, golongan darah, tinggi, dan berat. Karena itu
 * cakupannya selalu dibatasi ke ambalan milik pemohon, dan ambigu diselesaikan
 * oleh peran sehingga peran baru otomatis ikut sempit.
 */
class HealthSafetyController extends Controller
{
    /**
     * Ambalan tempat data pastas belongs.
     *
     * Dipakai bersama oleh pembacaan dan penulisan supaya keduanya tidak bisa
     * berbeda. Sebelumnya penulisan memakai ambalan anggota atau, bila tidak
     * ada, ambalan pertama; pembacaan tidak memakai filter sama sekali, jadi
     * satu ambalan bisa membaca rekam medis ambalan lain.
     */
    private function resolveAmbalanId(Request $request): ?int
    {
        return $request->user()?->member?->ambalan_id ?? Ambalan::first()?->id;
    }

    public function healthRecords(Request $request): Response
    {
        Roles::guardManagement($request->user());

        $ambalanId = $this->resolveAmbalanId($request);

        $query = HealthRecord::query()
            ->with(['member', 'createdBy'])
            ->where('ambalan_id', $ambalanId);

        if ($request->filled('member_id')) {
            $query->where('member_id', $request->integer('member_id'));
        }

        $records = $query->latest()->paginate(20)->withQueryString();

        return inertia('HealthSafety/HealthRecords', [
            'records' => $records,
            'filters' => $request->only(['member_id']),
            // Dropdown "pilih anggota" pada halaman ini memakai daftar ini.
            // Tanpa prop tersebut select-nya selalu kosong dan formulir simpan
            // tidak mungkin terisi karena member_id wajib diisi.
            'allMembers' => Member::where('ambalan_id', $ambalanId)
                ->where('status_aktif', 'Aktif')
                ->orderBy('nama_lengkap')
                ->get(['id', 'nama_lengkap', 'nta', 'angkatan']),
        ]);
    }

    public function storeHealthRecord(Request $request)
    {
        Roles::guardManagement($request->user());

        $ambalanId = $this->resolveAmbalanId($request);

        $data = $request->validate([
            'member_id' => [
                'required',
                // Validasi keberadaan anggota harus yang di ambalan ini, bukan
                // cukup ada di tabel members. Kalau tidak, formulir bisa
                // menyimpan rekam medis untuk anggota ambalan lain.
                Rule::exists('members', 'id')->where('ambalan_id', $ambalanId),
            ],
            'riwayat_penyakit' => 'nullable|string',
            'alergi' => 'nullable|string',
            'darah' => 'nullable|string',
            'tinggi_badan' => 'nullable|string',
            'berat_badan' => 'nullable|string',
            'catatan_tambahan' => 'nullable|string',
        ]);

        HealthRecord::create([
            'ambalan_id' => $ambalanId,
            'member_id' => $data['member_id'],
            'created_by' => $request->user()->id,
            'riwayat_penyakit' => $data['riwayat_penyakit'] ?? null,
            'alergi' => $data['alergi'] ?? null,
            'darah' => $data['darah'] ?? null,
            'tinggi_badan' => $data['tinggi_badan'] ?? null,
            'berat_badan' => $data['berat_badan'] ?? null,
            'catatan_tambahan' => $data['catatan_tambahan'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Health record saved successfully.');
    }

    public function updateHealthRecord(Request $request, HealthRecord $record)
    {
        Roles::guardManagement($request->user());

        $this->guardSameAmbalan($request, $record->ambalan_id);

        $record->update($request->validate([
            'riwayat_penyakit' => 'nullable|string',
            'alergi' => 'nullable|string',
            'darah' => 'nullable|string',
            'tinggi_badan' => 'nullable|string',
            'berat_badan' => 'nullable|string',
            'catatan_tambahan' => 'nullable|string',
        ]));
    }

    /**
     * Hapus rekam medis.
     *
     * Rute ini sebelumnya tidak ada, sementara HealthRecords.vue sudah memanggil
     * router.delete() dari tombol Hapus. Akibatnya tombolnya selalu gagal
     * dengan 404, bukan 403.
     */
    public function destroyHealthRecord(Request $request, HealthRecord $record)
    {
        Roles::guardManagement($request->user());

        $this->guardSameAmbalan($request, $record->ambalan_id);

        $record->delete();

        return redirect()->back()->with('success', 'Health record deleted successfully.');
    }

    public function safetyChecks(Request $request): Response
    {
        Roles::guardManagement($request->user());

        $query = SafetyCheck::query()
            ->with(['event', 'createdBy'])
            ->where('ambalan_id', $this->resolveAmbalanId($request));

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->string('kategori'));
        }

        if ($request->filled('passed')) {
            $query->where('passed', $request->boolean('passed'));
        }

        $checks = $query->latest()->paginate(20)->withQueryString();

        return inertia('HealthSafety/SafetyChecks', [
            'checks' => $checks,
            'filters' => $request->only(['kategori', 'passed']),
        ]);
    }

    public function storeSafetyCheck(Request $request)
    {
        Roles::guardManagement($request->user());

        $data = $request->validate([
            'kategori' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'event_id' => 'nullable|exists:events,id',
            'passed' => 'required|boolean',
        ]);

        SafetyCheck::create([
            'ambalan_id' => $this->resolveAmbalanId($request),
            'event_id' => $data['event_id'] ?? null,
            'created_by' => $request->user()->id,
            'kategori' => $data['kategori'],
            'deskripsi' => $data['deskripsi'],
            'passed' => $data['passed'],
        ]);

        return redirect()->back()->with('success', 'Safety check saved successfully.');
    }

    /**
     * Tolak tindakan terhadap baris milik ambalan lain.
     *
     * Rute sudah dibatasi per peran, tetapi peran managing boleh ada di lebih
     * dari satu ambalan. Tanpa pemeriksaan ini, pengelola ambalan B bisa
     * mengubah atau menghapus rekam medis anggota ambalan A hanya dengan
     * menebak id pada URL.
     */
    private function guardSameAmbalan(Request $request, mixed $ambalanId): void
    {
        abort_unless($ambalanId === $this->resolveAmbalanId($request), 403);
    }
}
