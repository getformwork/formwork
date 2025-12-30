<?php

namespace Formwork\Tests\Parsers\Fixtures;

class SetStateImplementingClassFixture
{
    public function __construct(private string $key) {}

    public static function __set_state(array $properties): self
    {
        return new self($properties['key'] ?? '');
    }
}
