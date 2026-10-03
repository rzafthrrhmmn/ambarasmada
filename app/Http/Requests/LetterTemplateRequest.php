<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LetterTemplateRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'file' => [
                'required',
                'file',
                'max:10240',
                // Aturan "mimes" menebak tipe dari isi berkas dan melempar
                // galat kalau tipenya tidak dikenal. Berkas .docx yang rusak
                // justru memicu galat 500 di sini, jadi jenis berkas dicek dari
                // ekstensi yang diunggah dan dari tipe yang dilaporkan server.
                // Isi berkasnya diperiksa terpisah oleh validateDocxContent().
                'extensions:docx',
                'mimetypes:application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/zip,application/octet-stream',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama template wajib diisi.',
            'file.required' => 'Berkas template .docx wajib diunggah.',
            'file.extensions' => 'Template harus berupa berkas .docx.',
            'file.mimetypes' => 'Template harus berupa berkas .docx.',
            'file.max' => 'Ukuran template maksimal 10 MB.',
        ];
    }
}
