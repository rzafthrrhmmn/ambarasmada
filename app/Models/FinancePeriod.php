<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['ambalan_id', 'nama', 'starts_at', 'ends_at', 'is_closed'])]
class FinancePeriod extends Model
{
    protected function casts(): array
    {
        return [
            'is_closed' => 'boolean',
        ];
    }

    public function ambalan(): BelongsTo
    {
        return $this->belongsTo(Ambalan::class);
    }

    public function finances(): HasMany
    {
        return $this->hasMany(Finance::class, 'period_id');
    }
}
