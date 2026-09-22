<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['ambalan_id', 'assessor_id', 'member_id', 'periode', 'nilai_kehadiran', 'nilai_disiplin', 'nilai_keterampilan', 'nilai_kepemimpinan', 'nilai_keseluruhan', 'catatan', 'status'])]
class Assessment extends Model
{
    protected $casts = [
        'nilai_kehadiran' => 'decimal:2',
        'nilai_disiplin' => 'decimal:2',
        'nilai_keterampilan' => 'decimal:2',
        'nilai_kepemimpinan' => 'decimal:2',
        'nilai_keseluruhan' => 'decimal:2',
    ];

    public function ambalan(): BelongsTo
    {
        return $this->belongsTo(Ambalan::class);
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessor_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(AssessmentDetail::class);
    }
}
