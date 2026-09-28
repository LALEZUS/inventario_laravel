<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceLog extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['date' => 'date', 'next_date' => 'date', 'cost' => 'decimal:2'];
    }

    public function computer(): BelongsTo
    {
        return $this->belongsTo(HardwareAsset::class, 'asset_id');
    }
}
