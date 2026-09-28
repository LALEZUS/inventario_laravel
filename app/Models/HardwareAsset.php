<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class HardwareAsset extends Model
{
    protected $table = 'hardware_assets';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'delivery_date' => 'date',
            'value' => 'decimal:2',
            'has_office' => 'boolean',
            'has_winrar' => 'boolean',
            'has_reader' => 'boolean',
            'has_server' => 'boolean',
            'has_printer' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function peripherals(): HasMany
    {
        return $this->hasMany(Peripheral::class, 'computer_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'asset_id')
            ->where('asset_type', 'inventory')
            ->latest('date_assigned');
    }

    public function maintenanceLogs(): HasMany
    {
        return $this->hasMany(MaintenanceLog::class, 'asset_id')
            ->where('asset_type', 'inventory')
            ->latest('date');
    }

    public function files(): MorphMany
    {
        return $this->morphMany(AssetFile::class, 'asset', 'asset_type', 'asset_id');
    }

    public function getAssignedToAttribute(): ?string
    {
        return $this->employee?->full_name ?: $this->assigned_user;
    }
}
