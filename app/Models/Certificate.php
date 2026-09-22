<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['ambalan_id', 'member_id', 'issued_by', 'nomor_sertifikat', 'jenis', 'judul', 'deskripsi', 'tanggal_diterbitkan', 'file_path', 'status'])]
class Certificate extends Model
{
    protected $casts = [
        'tanggal_diterbitkan' => 'date',
    ];

    public function ambalan(): BelongsTo
    {
        return $this->belongsTo(Ambalan::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
