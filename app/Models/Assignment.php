<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignment extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['date_assigned' => 'date', 'date_returned' => 'date'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function computer(): BelongsTo
    {
        return $this->belongsTo(HardwareAsset::class, 'asset_id');
    }

    public function cellphone(): BelongsTo
    {
        return $this->belongsTo(Cellphone::class, 'asset_id');
    }

    public function assetLabel(): string
    {
        return match ($this->asset_type) {
            'inventory' => $this->computer?->name ?? 'Equipo de computo',
            'cellphone' => $this->cellphone?->model ?? 'Celular',
            default => ucfirst($this->asset_type),
        };
    }

    public function responsivas(): HasMany
    {
        return $this->hasMany(AssetFile::class);
    }
}
