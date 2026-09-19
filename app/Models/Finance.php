<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['ambalan_id', 'member_id', 'category_id', 'period_id', 'jenis_transaksi', 'nominal', 'keterangan', 'status', 'receipt_no', 'created_by', 'tgl_transaksi'])]
class Finance extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'nominal' => 'decimal:2',
            'tgl_transaksi' => 'date',
        ];
    }

    public function ambalan(): BelongsTo
    {
        return $this->belongsTo(Ambalan::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FinanceCategory::class, 'category_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(FinancePeriod::class, 'period_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
