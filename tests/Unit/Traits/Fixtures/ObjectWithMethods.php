<?php

namespace Formwork\Tests\Unit\Traits\Fixtures;

use Closure;
use Formwork\Traits\Methods;

final class ObjectWithMethods
{
    use Methods;

    /**
     * @param array<string, Closure> $methods
     */
    public function __construct(array $methods = [])
    {
        $this->methods = $methods;
    }

    /**
     * @param list<mixed> $arguments
     */
    public function invoke(string $method, array $arguments = []): mixed
    {
        return $this->callMethod($method, $arguments);
    }
}
