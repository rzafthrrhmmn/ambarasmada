<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['member_id', 'sku_point_id', 'bukti_kegiatan', 'status', 'catatan', 'verified_by', 'tgl_verifikasi'])]
class SkuSubmission extends Model
{
    use SoftDeletes;

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function skuPoint(): BelongsTo
    {
        return $this->belongsTo(SkuPoint::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function media(): HasMany
    {
        return $this->hasMany(SkuSubmissionMedia::class);
    }
}
