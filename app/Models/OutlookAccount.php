<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutlookAccount extends Model
{
    protected $table = 'correos_outlook';
    protected $guarded = [];
    protected $hidden = ['contraseña'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    protected function casts(): array
    {
        return ['ssl_entrada' => 'boolean'];
    }
}
