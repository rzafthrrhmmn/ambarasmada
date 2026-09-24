<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['inventory_id', 'jenis', 'jumlah', 'referensi', 'actor_id', 'catatan'])]
class InventoryMovement extends Model
{
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
