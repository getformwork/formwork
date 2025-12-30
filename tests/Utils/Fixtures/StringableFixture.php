<?php

namespace Formwork\Tests\Utils\Fixtures;

use Stringable;

final class StringableFixture implements Stringable
{
    public function __construct(private string $value) {}

    public function __toString(): string
    {
        return $this->value;
    }
}
