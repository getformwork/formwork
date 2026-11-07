<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

final class VariadicServiceFixture
{
    /**
     * @var list<string>
     */
    public array $values;

    public function __construct(string ...$values)
    {
        $this->values = $values;
    }
}
