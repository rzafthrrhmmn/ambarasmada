<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['inventory_id', 'member_id', 'peminjam_nama', 'tgl_pinjam', 'tgl_kembali', 'status', 'kondisi', 'approved_by', 'catatan'])]
class InventoryLoan extends Model
{
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
