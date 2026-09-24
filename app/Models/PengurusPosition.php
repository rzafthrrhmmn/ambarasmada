<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'code', 'description', 'is_putra'])]
class PengurusPosition extends Model
{
    protected $casts = [
        'is_putra' => 'boolean',
    ];

    public function memberPositions(): HasMany
    {
        return $this->hasMany(MemberPosition::class);
    }

    public function assignedBy(): HasMany
    {
        return $this->hasMany(MemberPosition::class, 'assigned_by_user_id');
    }
}
