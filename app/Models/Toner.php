<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Toner extends Model
{
    protected $table = 'toner';

    protected $guarded = [];

    protected $casts = [
        'low_stock_threshold' => 'integer',
    ];

    public function getNotesAttribute(): ?string
    {
        return $this->comments ?: $this->comentarios;
    }
}
