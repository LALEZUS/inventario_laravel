<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Peripheral extends Model
{
    protected $guarded = [];

    public function computer(): BelongsTo
    {
        return $this->belongsTo(HardwareAsset::class, 'computer_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function files(): MorphMany
    {
        return $this->morphMany(AssetFile::class, 'asset', 'asset_type', 'asset_id');
    }

    public function getAssignedNameAttribute(): ?string
    {
        return $this->employee?->full_name ?: $this->assigned_to;
    }
}
