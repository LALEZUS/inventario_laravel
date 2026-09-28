<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SoftwareLicense extends Model
{
    protected $table = 'licenses';
    protected $guarded = [];
    protected $hidden = ['key_value', 'password'];

    protected function casts(): array
    {
        return ['expiration_date' => 'date'];
    }
}
