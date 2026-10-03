<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LetterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['Admin', 'Pembina', 'Pengurus'], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ambalan_id' => ['nullable', 'integer', 'exists:ambalans,id'],
            'nomor_surat' => ['nullable', 'string', 'max:100'],
            'jenis_surat' => ['required', Rule::in(['Masuk', 'Keluar', 'Keputusan'])],
            'perihal' => ['required', 'string', 'max:255'],
            'isi_surat' => ['nullable', 'string', 'max:20000'],
            'tujuan_pengirim' => ['required', 'string', 'max:150'],
            'tgl_surat' => ['required', 'date'],
            'waktu_kegiatan' => ['nullable', 'string', 'max:255'],
            'lokasi_kegiatan' => ['nullable', 'string', 'max:255'],
            'template_id' => ['nullable', 'integer', 'exists:letter_templates,id'],
            'placeholder_values' => ['nullable', 'array'],
            'placeholder_values.*' => ['nullable', 'string', 'max:2000'],
            'file' => [
                'nullable',
                'file',
                'max:10240',
                'mimetypes:application/pdf,image/jpeg,image/png',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'jenis_surat.required' => 'Pilih jenis surat.',
            'jenis_surat.in' => 'Jenis surat tidak dikenal.',
            'perihal.required' => 'Perihal surat wajib diisi.',
            'tujuan_pengirim.required' => 'Tujuan atau pengirim surat wajib diisi.',
            'tgl_surat.required' => 'Tanggal surat wajib diisi.',
            'tgl_surat.date' => 'Tanggal surat tidak valid.',
            'template_id.exists' => 'Template surat yang dipilih sudah tidak ada.',
            'placeholder_values.array' => 'Data tambahan surat tidak valid.',
            'placeholder_values.*.string' => 'Data tambahan surat harus berupa teks.',
            'placeholder_values.*.max' => 'Data tambahan surat terlalu panjang.',
            'file.mimetypes' => 'Lampiran harus berupa PDF atau gambar (JPG/PNG).',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function letterData(): array
    {
        $data = $this->safe()->except(['file']);

        $data['ambalan_id'] = $data['ambalan_id'] ?? null;
        $data['nomor_surat'] = $data['nomor_surat'] ?? null;
        $data['isi_surat'] = $data['isi_surat'] ?? null;
        $data['waktu_kegiatan'] = $data['waktu_kegiatan'] ?? null;
        $data['lokasi_kegiatan'] = $data['lokasi_kegiatan'] ?? null;
        $data['template_id'] = $data['template_id'] ?? null;
        $data['placeholder_values'] = $this->safe()->input('placeholder_values') ?: [];

        return $data;
    }
}
