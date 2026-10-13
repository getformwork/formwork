<?php

namespace Formwork\Tests\Unit\Interpolator\Fixtures;

class MagicGetter
{
    public function __get(string $name): string
    {
        return "magic:{$name}";
    }
}
