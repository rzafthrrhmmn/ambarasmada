<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['ambalan_id', 'kode_barang', 'nama_barang', 'jenis', 'satuan', 'jumlah', 'kondisi', 'status_pinjam'])]
class Inventory extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
        ];
    }

    public function ambalan(): BelongsTo
    {
        return $this->belongsTo(Ambalan::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(InventoryLoan::class);
    }
}
