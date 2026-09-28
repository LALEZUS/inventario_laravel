<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['created_at' => 'datetime', 'updated_at' => 'datetime'];
    }

    public function hardwareAssets(): HasMany
    {
        return $this->hasMany(HardwareAsset::class);
    }

    public function cellphones(): HasMany
    {
        return $this->hasMany(Cellphone::class);
    }

    public function peripherals(): HasMany
    {
        return $this->hasMany(Peripheral::class);
    }

    public function printers(): HasMany
    {
        return $this->hasMany(Printer::class);
    }

    public function accountCredentials(): HasMany
    {
        return $this->hasMany(AccountCredential::class);
    }

    public function outlookAccounts(): HasMany
    {
        return $this->hasMany(OutlookAccount::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class)->latest('date_assigned');
    }
}
