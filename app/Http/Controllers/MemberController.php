<?php

namespace App\Http\Controllers;

use App\Models\Ambalan;
use App\Models\Angkatan;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\MemberPosition;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Factory;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class MemberController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);

        $query = Member::query()
            ->with(['user', 'ambalan'])
            ->whereHas('user');

        if ($request->string('search')->isNotEmpty()) {
            $query->where('nama_lengkap', 'like', '%'.$request->string('search').'%');
        }

        if ($request->string('status')->isNotEmpty()) {
            $statusMap = [
                'Aktif' => 'Aktif',
                'Non-Aktif' => 'Non-Aktif',
                'Alumni' => 'Alumni',
            ];
            if (isset($statusMap[$request->string('status')])) {
                $query->where('status_aktif', $statusMap[$request->string('status')]);
            }
        }

        $members = $query->orderBy('nama_lengkap')->paginate(20)->withQueryString();
        $ambalans = Ambalan::orderBy('nama')->get();
        $angkatanOptions = Angkatan::where('is_active', true)->orderBy('angkatan')->get();
        $angkatanList = Angkatan::orderBy('angkatan')->get();
        $gudepPrefix = (string) config('app.gudep_prefix', '31082008');
        $positions = MemberPosition::with('position')->get()->groupBy('member_id');
        $pengurus = Member::whereHas('user', fn ($q) => $q->where('role', 'Pengurus'))
            ->with(['user', 'memberPositions.position'])
            ->get();

        return Inertia::render('Members/Index', [
            'members' => $members,
            'filters' => $request->only(['search', 'status']),
            'ambalans' => $ambalans,
            'angkatanOptions' => $angkatanOptions,
            'angkatanList' => $angkatanList,
            'gudepPrefix' => $gudepPrefix,
            'positions' => $positions->values(),
            'pengurus' => $pengurus,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);

        $data = $request->validate([
            'ambalan_id' => ['required', 'exists:ambalans,id'],
            'angkatan' => ['required', 'string', 'size:3', 'regex:/^\d{3}$/', 'exists:angkatans,nomor'],
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'tempatlahir' => ['nullable', 'string', 'max:255'],
            'tanggallahir' => ['nullable', 'date'],
            'jeniskelamin' => ['nullable', 'in:Laki-laki,Perempuan'],
            'kelas' => ['required', 'string', 'max:50'],
            'tingkatan' => ['required', 'in:Tamu,Calon,Bantara,Laksana,Alumni'],
            'status_aktif' => ['required', 'in:Aktif,Non-Aktif,Alumni'],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $member = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['nama_lengkap'],
                'email' => null,
                'username' => $data['nama_lengkap'],
                'password' => Hash::make($data['password']),
                'role' => 'Anggota',
                'is_active' => true,
                'status' => 'active',
            ]);

            $member = Member::create([
                'ambalan_id' => $data['ambalan_id'],
                'user_id' => $user->id,
                'angkatan' => $data['angkatan'],
                'nama_lengkap' => $data['nama_lengkap'],
                'tempat_lahir' => $data['tempatlahir'] ?? null,
                'tanggal_lahir' => $data['tanggallahir'] ?? null,
                'jenis_kelamin' => $data['jeniskelamin'] ?? null,
                'kelas' => $data['kelas'],
                'tingkatan' => $data['tingkatan'],
                'status_aktif' => $data['status_aktif'],
                'no_hp' => $data['no_hp'] ?? null,
            ]);

            return $member;
        });

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'member.created',
            'entity_type' => Member::class,
            'entity_id' => $member->id,
            'metadata' => [
                'nama_lengkap' => $member->nama_lengkap,
                'angkatan' => $member->angkatan,
                'status_aktif' => $member->status_aktif,
            ],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('members.index')->with('success', 'Anggota berhasil ditambahkan.');
    }

    public function update(Request $request, Member $member): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);

        $data = $request->validate([
            'angkatan' => ['required', 'string', 'size:3', 'regex:/^\d{3}$/', 'exists:angkatans,nomor'],
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'tempatlahir' => ['nullable', 'string', 'max:255'],
            'tanggallahir' => ['nullable', 'date'],
            'jeniskelamin' => ['nullable', 'in:Laki-laki,Perempuan'],
            'kelas' => ['required', 'string', 'max:50'],
            'tingkatan' => ['required', 'in:Tamu,Calon,Bantara,Laksana,Alumni'],
            'status_aktif' => ['required', 'in:Aktif,Non-Aktif,Alumni'],
            'no_hp' => ['nullable', 'string', 'max:20'],
        ]);

        $member->update([
            'angkatan' => $data['angkatan'],
            'nama_lengkap' => $data['nama_lengkap'],
            'tempat_lahir' => $data['tempatlahir'] ?? null,
            'tanggal_lahir' => $data['tanggallahir'] ?? null,
            'jenis_kelamin' => $data['jeniskelamin'] ?? null,
            'kelas' => $data['kelas'],
            'tingkatan' => $data['tingkatan'],
            'status_aktif' => $data['status_aktif'],
            'no_hp' => $data['no_hp'] ?? null,
        ]);

        if ($member->user) {
            $member->user->update([
                'name' => $data['nama_lengkap'],
            ]);
        }

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'member.updated',
            'entity_type' => Member::class,
            'entity_id' => $member->id,
            'metadata' => [
                'nama_lengkap' => $member->nama_lengkap,
                'angkatan' => $member->angkatan,
                'status_aktif' => $member->status_aktif,
            ],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('members.index')->with('success', 'Anggota berhasil diperbarui.');
    }

    public function destroy(Request $request, Member $member): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);
        $member->delete();

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'member.deleted',
            'entity_type' => Member::class,
            'entity_id' => $member->id,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('members.index')->with('success', 'Anggota berhasil dihapus.');
    }

    public function bulkChangeRole(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === 'Pembina', 403);
        $data = $request->validate([
            'angkatan' => ['required', 'string', 'size:3', 'regex:/^\d{3}$/', 'exists:angkatans,nomor'],
            'new_role' => ['required', 'in:Anggota,Pengurus,Pembina,Alumni'],
        ]);

        $members = Member::where('angkatan', $data['angkatan'])->get();
        foreach ($members as $member) {
            if ($member->user) {
                $member->user->update(['role' => $data['new_role']]);
            }
        }

        return redirect()->route('members.index')->with('success', 'Role berhasil diubah untuk '.$members->count()." anggota angkatan {$data['angkatan']}.");
    }

    public function angkatanIndex(Request $request): Response
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);
        $angkatan = Angkatan::orderBy('angkatan')->paginate(20);

        return Inertia::render('Members/AngkatanIndex', ['angkatan' => $angkatan]);
    }

    public function angkatanStore(Request $request): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);
        $data = $request->validate([
            'tahun' => ['required', 'string', 'max:4'],
            'nomor' => ['required', 'string', 'size:3', 'regex:/^\d{3}$/'],
            'nama' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'is_current' => ['required', 'boolean'],
        ]);

        $angkatan = Angkatan::create([
            'angkatan' => $data['tahun'],
            'nomor' => $data['nomor'],
            'nama' => $data['nama'],
            'is_active' => $data['is_active'] ?? true,
            'is_current' => $data['is_current'],
        ]);

        if ($data['is_current']) {
            Angkatan::where('id', '!=', $angkatan->id)->update(['is_current' => false]);
        }

        return redirect()->route('members.index')->with('success', 'Angkatan berhasil ditambahkan.');
    }

    public function angkatanUpdate(Request $request, Angkatan $angkatan): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);
        $data = $request->validate([
            'tahun' => ['required', 'string', 'max:4'],
            'nomor' => ['required', 'string', 'size:3', 'regex:/^\d{3}$/'],
            'nama' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'is_current' => ['required', 'boolean'],
        ]);

        $angkatan->update([
            'angkatan' => $data['tahun'],
            'nomor' => $data['nomor'],
            'nama' => $data['nama'],
            'is_active' => $data['is_active'] ?? true,
            'is_current' => $data['is_current'],
        ]);

        if ($data['is_current']) {
            Angkatan::where('id', '!=', $angkatan->id)->update(['is_current' => false]);
        }

        return redirect()->route('members.index')->with('success', 'Angkatan berhasil diperbarui.');
    }

    public function angkatanDestroy(Request $request, Angkatan $angkatan): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Pembina', 'Pengurus'], true), 403);
        if ($angkatan->is_current) {
            $replacement = Angkatan::where('is_active', true)
                ->where('id', '!=', $angkatan->id)
                ->orderByDesc('is_current')
                ->first();
            if ($replacement) {
                $replacement->update(['is_current' => true]);
            }
        }
        $angkatan->update(['is_active' => false, 'is_current' => false]);

        return redirect()->route('members.index')->with('success', 'Angkatan berhasil diarsipkan.');
    }

    public function profile(Request $request): Response|SymfonyResponse
    {
        $member = Member::where('user_id', $request->user()->id)
            ->with(['user', 'attendances.session', 'ambalan'])
            ->first();

        if (! $member) {
            $currentAngkatan = Angkatan::query()
                ->where('is_active', true)
                ->where('is_current', true)
                ->first()
                ?? Angkatan::query()
                    ->where('is_active', true)
                    ->orderByDesc('nomor')
                    ->first();

            if (! $currentAngkatan) {
                $year = (string) now()->year;
                $currentAngkatan = Angkatan::firstOrCreate(
                    ['angkatan' => $year],
                    [
                        'nomor' => '001',
                        'nama' => "Angkatan {$year}",
                        'is_active' => true,
                        'is_current' => true,
                    ]
                );
            }

            $member = Member::create([
                'user_id' => $request->user()->id,
                'nta' => $request->user()->username,
                'angkatan' => $currentAngkatan->nomor,
                'nomor_urut' => 999,
                'nta_username' => $request->user()->username,
                'nama_lengkap' => $request->user()->name,
                'ambalan_id' => Ambalan::first()?->id,
                'kelas' => '-',
                'tingkatan' => 'Tamu',
                'tahun_lulus' => null,
                'status_aktif' => 'Aktif',
                'no_hp' => '-',
            ]);
        }

        return Inertia::render('Profile/Show', [
            'member' => $member,
        ]);
    }

    public function updateProfile(Request $request): SymfonyResponse
    {
        $member = Member::where('user_id', $request->user()->id)->first();

        if (! $member) {
            $currentAngkatan = Angkatan::query()
                ->where('is_active', true)
                ->where('is_current', true)
                ->first()
                ?? Angkatan::query()
                    ->where('is_active', true)
                    ->orderByDesc('nomor')
                    ->first();

            if (! $currentAngkatan) {
                $year = (string) now()->year;
                $currentAngkatan = Angkatan::firstOrCreate(
                    ['angkatan' => $year],
                    [
                        'nomor' => '001',
                        'nama' => "Angkatan {$year}",
                        'is_active' => true,
                        'is_current' => true,
                    ]
                );
            }

            $member = Member::create([
                'user_id' => $request->user()->id,
                'nta' => $request->user()->username,
                'angkatan' => $currentAngkatan->nomor,
                'nomor_urut' => 999,
                'nta_username' => $request->user()->username,
                'nama_lengkap' => $request->user()->name,
                'ambalan_id' => Ambalan::first()?->id,
                'kelas' => '-',
                'tingkatan' => 'Tamu',
                'tahun_lulus' => null,
                'status_aktif' => 'Aktif',
                'no_hp' => '-',
            ]);
        }

        $data = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'tempat_lahir' => ['nullable', 'string', 'max:255'],
            'tanggal_lahir' => ['nullable', 'date'],
            'jenis_kelamin' => ['nullable', 'in:Laki-laki,Perempuan'],
            'kelas' => ['required', 'string', 'max:255'],
            'tingkatan' => ['required', 'in:Tamu,Calon,Bantara,Laksana,Alumni'],
            'tahun_lulus' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'status_aktif' => ['required', 'in:Aktif,Non-Aktif,Alumni'],
            'foto' => ['nullable', 'file', 'max:2048', 'mimetypes:image/jpeg,image/png,image/webp'],
            'delete_foto' => ['nullable', 'boolean'],
        ]);

        $member->update([
            'nama_lengkap' => $data['nama_lengkap'],
            'no_hp' => $data['no_hp'] ?? null,
            'tempat_lahir' => $data['tempat_lahir'] ?? null,
            'tanggal_lahir' => $data['tanggal_lahir'] ?? null,
            'jenis_kelamin' => $data['jenis_kelamin'] ?? null,
            'kelas' => $data['kelas'],
            'tingkatan' => $data['tingkatan'] ?? null,
            'tahun_lulus' => $data['tahun_lulus'] ?? null,
            'status_aktif' => $data['status_aktif'] ?? null,
        ]);

        if ($data['delete_foto'] ?? false) {
            if ($member->user && $member->user->foto) {
                Storage::disk('public')->delete($member->user->foto);
                $member->user->update(['foto' => null]);
            }
        } elseif ($request->hasFile('foto')) {
            if ($member->user && $member->user->foto) {
                Storage::disk('public')->delete($member->user->foto);
            }
            $file = $request->file('foto');
            $this->validateFileContent($file);
            $path = $file->store('profile-photos', 'public');
            $member->user->update(['foto' => $path]);
        }

        if ($member->user) {
            $member->user->update(['name' => $data['nama_lengkap']]);
        }

        return redirect()->route('profile.show')->with('success', 'Profil berhasil diperbarui.');
    }

    protected function validateFileContent(UploadedFile $file): void
    {
        $allowedMimes = [
            'image/jpeg' => "\xFF\xD8\xFF",
            'image/png' => "\x89PNG\r\n\x1A\n",
            'image/webp' => 'RIFF',
        ];

        $mime = $file->getMimeType();
        if (! isset($allowedMimes[$mime])) {
            throw new ValidationException(
                Factory::make()->make([], [], ['foto' => 'Tipe file tidak diizinkan. Hanya JPEG, PNG, dan WebP.'])
            );
        }

        $header = file_get_contents($file->getRealPath(), false, null, 0, 12);
        $expectedHeader = $allowedMimes[$mime];

        if (strpos($header, $expectedHeader) !== 0) {
            throw new ValidationException(
                Factory::make()->make([], [], ['foto' => 'Konten file tidak sesuai dengan tipe yang dideklarasikan.'])
            );
        }
    }
}
