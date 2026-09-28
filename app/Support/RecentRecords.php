<?php

namespace App\Support;

use Closure;
use Illuminate\Http\Request;

final class RecentRecords
{
    public const CREATED = 'created';
    public const UPDATED = 'updated';

    public static function mode(Request $request): ?string
    {
        $mode = (string) $request->query('recent');

        return in_array($mode, [self::CREATED, self::UPDATED], true) ? $mode : null;
    }

    public static function apply(
        $query,
        Request $request,
        Closure $defaultOrder,
        string $createdColumn = 'created_at',
        string $updatedColumn = 'updated_at',
    ) {
        return match (self::mode($request)) {
            self::CREATED => $query->orderByDesc($createdColumn)->orderByDesc('id'),
            self::UPDATED => $query->orderByDesc($updatedColumn)->orderByDesc('id'),
            default => $defaultOrder($query),
        };
    }
}
