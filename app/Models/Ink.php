<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Ink extends Model
{
    protected $guarded = [];

    protected $casts = [
        'low_stock_threshold' => 'integer',
    ];

    public function dateValue(string $field): ?string
    {
        $value = $this->getRawOriginal($field);

        return filled($value) && $value !== '0000-00-00' ? $value : null;
    }

    public function colorKey(): string
    {
        $color = Str::lower(Str::ascii(trim((string) $this->color)));

        return match (true) {
            str_starts_with($color, 'bk'), str_contains($color, 'negro'), str_contains($color, 'black') => 'black',
            str_starts_with($color, 'c '), str_starts_with($color, 'c-'), str_contains($color, 'cyan'), str_contains($color, 'cian') => 'cyan',
            str_starts_with($color, 'm '), str_starts_with($color, 'm-'), str_contains($color, 'magenta') => 'magenta',
            str_starts_with($color, 'y '), str_starts_with($color, 'y-'), str_contains($color, 'yellow'), str_contains($color, 'amarillo') => 'yellow',
            default => 'other',
        };
    }
}
