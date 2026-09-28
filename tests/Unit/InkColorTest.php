<?php

namespace Tests\Unit;

use App\Models\Ink;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class InkColorTest extends TestCase
{
    #[DataProvider('standardColors')]
    public function test_it_normalizes_standard_ink_colors(string $value, string $expected): void
    {
        $this->assertSame($expected, (new Ink(['color' => $value]))->colorKey());
    }

    public static function standardColors(): array
    {
        return [
            'black code' => ['BK - Negro', 'black'],
            'cyan code' => ['C - Cyan', 'cyan'],
            'magenta code' => ['M - Magenta', 'magenta'],
            'yellow code' => ['Y - Yellow', 'yellow'],
            'spanish yellow' => ['Amarillo', 'yellow'],
            'unknown color' => ['Light gray', 'other'],
        ];
    }
}
