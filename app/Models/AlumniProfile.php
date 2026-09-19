<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['member_id', 'status_saat_ini', 'instansi_kampus', 'pekerjaan', 'domisili', 'media_sosial', 'show_contact'])]
class AlumniProfile extends Model
{
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
