<?php

namespace Formwork\Tests\Unit\Traits\Fixtures;

use Formwork\Traits\StaticClass;

final class StaticUtility
{
    use StaticClass;

    public static function double(int $value): int
    {
        return $value * 2;
    }
}
