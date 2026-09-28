<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Cellphone extends Model
{
    protected $guarded = [];

    protected $hidden = [
        'password',
        'updated_password',
        'app_lock_password',
        'app_lock_answer',
        'app_lock_pattern',
    ];

    protected function casts(): array
    {
        return [
            'has_app_lock' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function files(): MorphMany
    {
        return $this->morphMany(AssetFile::class, 'asset', 'asset_type', 'asset_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'asset_id')
            ->where('asset_type', 'cellphone')
            ->latest('date_assigned')
            ->latest('id');
    }

    public function getAssignedNameAttribute(): ?string
    {
        return $this->employee?->full_name ?: $this->employee_name_legacy;
    }
}
