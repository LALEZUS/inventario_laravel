<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountCredential extends Model
{
    protected $table = 'account_management';
    protected $guarded = [];
    protected $hidden = ['password'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
