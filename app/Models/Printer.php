<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Printer extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_network' => 'boolean',
            'linked_inks' => 'array',
            'linked_toner' => 'array',
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

    public function getAssignedNameAttribute(): ?string
    {
        return $this->employee?->full_name ?: $this->assigned_to;
    }

    public function linkedInkIds(): array
    {
        return array_values(array_filter(array_map('intval', $this->linked_inks ?? [])));
    }

    public function linkedTonerIds(): array
    {
        return array_values(array_filter(array_map('intval', $this->linked_toner ?? [])));
    }
}
