<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Angkatan extends Model
{
    protected $fillable = ['angkatan', 'nomor', 'nama', 'is_active', 'is_current'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_current' => 'boolean',
    ];

    public function members(): HasMany
    {
        return $this->hasMany(Member::class, 'angkatan', 'nomor');
    }
}
