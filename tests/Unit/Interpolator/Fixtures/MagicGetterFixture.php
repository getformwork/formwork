<?php

namespace Formwork\Tests\Unit\Interpolator\Fixtures;

class MagicGetterFixture
{
    public function __get(string $name): string
    {
        return "magic:{$name}";
    }
}
