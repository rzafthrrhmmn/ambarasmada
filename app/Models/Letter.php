<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['ambalan_id', 'nomor_surat', 'jenis_surat', 'perihal', 'isi_surat', 'tujuan_pengirim', 'tgl_surat', 'waktu_kegiatan', 'lokasi_kegiatan', 'file_path', 'template_id', 'created_by_user_id'])]
class Letter extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    public function ambalan(): BelongsTo
    {
        return $this->belongsTo(Ambalan::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(LetterTemplate::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
