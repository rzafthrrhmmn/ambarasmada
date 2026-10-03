<?php

namespace App\Support\Letters;

use App\Models\Letter;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Menyusun nilai penanda untuk satu surat.
 *
 * Seluruh nilai berasal dari isian form pada halaman persuratan. Data
 * identitas penulis surat (nama Pradana Putra/Putri, NIS, NISN, NIP, dan
 * sejenisnya) tidak diambil dari basis data anggota, karena di luar konteks
 * persuratan dan sering berbeda dengan sumber resmi.
 *
 * Nilai ini hanya dipakai untuk mengisi templat .docx milik pengguna, bukan
 * untuk menulis ke tabel pengguna atau anggota.
 */
class LetterValues
{
    /**
     * Penanda inti yang punya kolom sendiri di form.
     *
     * @var array<int, string>
     */
    public const CORE_PLACEHOLDERS = [
        'nomor_surat',
        'jenis_surat',
        'perihal',
        'isi_surat',
        'tujuan_pengirim',
        'tgl_surat',
        'waktu_kegiatan',
        'lokasi_kegiatan',
    ];

    /**
     * Penanda identitas yang selalu offered sebagai isian form.
     *
     * @var array<string, string>
     */
    public const IDENTITY_FIELDS = [
        'nama_pradana_putra' => 'Nama Pradana Putra',
        'nis_pradana_putra' => 'NIS Pradana Putra',
        'nisn_pradana_putra' => 'NISN Pradana Putra',
        'nama_pradana_putri' => 'Nama Pradana Putri',
        'nis_pradana_putri' => 'NIS Pradana Putri',
        'nisn_pradana_putri' => 'NISN Pradana Putri',
        'nama_pembina' => 'Nama Pembina',
        'nip_pembina' => 'NIP Pembina',
        'nama_kepala_sekolah' => 'Nama Kepala Sekolah',
        'nip_kepala_sekolah' => 'NIP Kepala Sekolah',
        'nama_pengirim' => 'Nama Pengirim',
        'nip_pengirim' => 'NIP Pengirim',
        'jabatan_pengirim' => 'Jabatan Pengirim',
        'nama_penerima' => 'Nama Penerima',
        'nip_penerima' => 'NIP Penerima',
        'alamat_ambalan' => 'Alamat Ambalan',
        'telepon_ambalan' => 'Telepon Ambalan',
        'email_ambalan' => 'Email Ambalan',
    ];

    /**
     * Alias penanda yang sering dipakai pengguna, dipetakan ke penanda baku.
     *
     * @var array<string, string>
     */
    public const ALIASES = [
        'nomor' => 'nomor_surat',
        'no_surat' => 'nomor_surat',
        'nomor_surat' => 'nomor_surat',
        'jenis' => 'jenis_surat',
        'jenis_surat' => 'jenis_surat',
        'hal' => 'perihal',
        'perihal' => 'perihal',
        'isi' => 'isi_surat',
        'isi_surat' => 'isi_surat',
        'badan_surat' => 'isi_surat',
        'tujuan' => 'tujuan_pengirim',
        'penerima' => 'tujuan_pengirim',
        'tujuan_pengirim' => 'tujuan_pengirim',
        'tanggal' => 'tanggal',
        'tanggal_surat' => 'tgl_surat',
        'tgl' => 'tgl_surat',
        'tgl_surat' => 'tgl_surat',
        'waktu' => 'waktu_kegiatan',
        'waktu_kegiatan' => 'waktu_kegiatan',
        'lokasi' => 'lokasi_kegiatan',
        'lokasi_kegiatan' => 'lokasi_kegiatan',
        'ambalan' => 'nama_ambalan',
        'nama_ambalan' => 'nama_ambalan',
        'nama_anggota' => 'nama_pradana_putra',
    ];

    /** Nilai yang dipakai bila penanda tidak punya isi. */
    public const BLANK = '-';

    private const BULAN = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    private const HARI = [
        0 => 'Minggu',
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
    ];

    /**
     * Nilai baku surat, dipakai form dan diekspor ke PDF tanpa templat.
     *
     * @return array<string, string>
     */
    public function base(Letter $letter): array
    {
        $tanggal = $letter->tgl_surat ? Carbon::parse($letter->tgl_surat) : null;
        $today = Carbon::today();

        return [
            'nomor_surat' => $this->clean($letter->nomor_surat),
            'jenis_surat' => $this->clean($letter->jenis_surat),
            'perihal' => $this->clean($letter->perihal),
            'isi_surat' => $this->clean($letter->isi_surat),
            'tujuan_pengirim' => $this->clean($letter->tujuan_pengirim),
            'tgl_surat' => $tanggal ? $this->tanggal($tanggal) : '-',
            'waktu_kegiatan' => $this->clean($letter->waktu_kegiatan),
            'lokasi_kegiatan' => $this->clean($letter->lokasi_kegiatan),
            'nama_ambalan' => $this->clean($letter->ambalan?->nama),
            'tanggal' => $this->tanggal($today),
            'hari' => self::HARI[(int) $today->format('w')],
            'bulan' => self::BULAN[(int) $today->format('n')],
            'tahun' => $today->format('Y'),
        ];
    }

    /**
     * Tanggal ditulis dalam bahasa Indonesia. Locale aplikasi bisa en, jadi
     * nama bulan tidak boleh mengandalkan Carbon::translatedFormat().
     */
    protected function tanggal(Carbon $date): string
    {
        return $date->day.' '.self::BULAN[(int) $date->month].' '.$date->year;
    }

    /**
     * Nilai final untuk mengisi templat: nilai baku, lalu isian tambahan dari
     * form menurut penanda yang dipakai templat.
     *
     * @param  array<int, string>  $placeholders
     * @param  array<string, mixed>  $extra
     * @return array{values: array<string, string>, unfilled: array<int, string>}
     */
    public function forTemplate(Letter $letter, array $placeholders, array $extra = []): array
    {
        $base = array_merge($this->base($letter), $this->sanitizeExtra($extra));
        $values = [];
        $unfilled = [];

        foreach ($placeholders as $placeholder) {
            $value = $this->lookup($base, $placeholder);
            $values[$placeholder] = $value;

            if ($value === self::BLANK) {
                $unfilled[] = $placeholder;
            }
        }

        return ['values' => $values, 'unfilled' => $unfilled];
    }

    /**
     * Katalog isian identitas yang bisa dipakai di form persuratan.
     *
     * @return array<string, string>
     */
    public function catalog(): array
    {
        return self::IDENTITY_FIELDS;
    }

    /**
     * Daftar isian form yang perlu ditampilkan untuk sebuah templat.
     *
     * Penanda baku dan penanda identitas punya label siap pakai, sisanya
     * dibuat dari nama penanda supaya templat bebas tetap bisa diisi lewat form.
     *
     * @param  array<int, string>  $placeholders
     * @return array<string, array{key: string, label: string, type: string, core: bool}>
     */
    public function formFields(array $placeholders): array
    {
        $fields = [];

        foreach ($placeholders as $placeholder) {
            $key = $this->canonical($placeholder);
            $label = self::IDENTITY_FIELDS[$key] ?? $this->humanize($placeholder);
            $type = $this->fieldType($key);

            $fields[$placeholder] ??= [
                'key' => $key,
                'label' => $label,
                'type' => $type,
                'core' => $type !== 'text',
            ];
        }

        return $fields;
    }

    /**
     * Ambil nilai penanda: cek nama baku, alias, lalu bentuk sederhana seperti
     * ${nama} atau ${nama_surat} yang tidak terdaftar.
     *
     * @param  array<string, string>  $values
     */
    public function lookup(array $values, string $placeholder): string
    {
        $key = $this->canonical($placeholder);

        foreach ([$placeholder, $key, Str::snake($key), Str::lower($placeholder)] as $candidate) {
            if (array_key_exists($candidate, $values) && $values[$candidate] !== '') {
                return (string) $values[$candidate];
            }
        }

        return static::BLANK;
    }

    /**
     * Buang nilai kosong agar tidak menimpa nilai baku dengan string kosong.
     *
     * @param  array<string, mixed>  $extra
     * @return array<string, string>
     */
    protected function sanitizeExtra(array $extra): array
    {
        $clean = [];

        foreach ($extra as $key => $value) {
            if (! is_string($key) || $key === '') {
                continue;
            }

            if (! is_scalar($value)) {
                continue;
            }

            $value = trim((string) $value);

            if ($value === '') {
                continue;
            }

            $clean[$key] = $value;
        }

        return $clean;
    }

    protected function canonical(string $placeholder): string
    {
        $key = Str::of($placeholder)->lower()->snake()->toString();

        return self::ALIASES[$key] ?? $key;
    }

    protected function fieldType(string $key): string
    {
        return in_array($key, ['isi_surat', 'catatan', 'keterangan', 'isi', 'badan_surat'], true)
            ? 'textarea'
            : 'text';
    }

    protected function humanize(string $placeholder): string
    {
        $words = str_replace(['_', '-', '.'], ' ', Str::lower($placeholder));

        return Str::title(trim($words));
    }

    protected function clean(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }
}
