<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

final class VariadicService
{
    public array $values;

    public function __construct(string ...$values)
    {
        $this->values = $values;
    }
}
