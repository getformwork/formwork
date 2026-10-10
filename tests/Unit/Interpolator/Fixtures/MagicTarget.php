<?php

namespace Formwork\Tests\Unit\Interpolator\Fixtures;

use BadMethodCallException;

class MagicTarget
{
    public string $realProperty = 'real';

    /**
     * @param list<mixed> $arguments
     */
    public function __call(string $name, array $arguments): string
    {
        if ($name === 'dynamic') {
            return 'called:' . implode(',', $arguments);
        }
        throw new BadMethodCallException("Undefined method {$name}");
    }

    public function __get(string $name): string
    {
        return "magic:{$name}";
    }
}
