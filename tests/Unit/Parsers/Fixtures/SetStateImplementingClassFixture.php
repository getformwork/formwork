<?php

namespace Formwork\Tests\Unit\Parsers\Fixtures;

class SetStateImplementingClassFixture
{
    public function __construct(private string $key) {}

    /**
     * @param array{key?: string} $properties
     */
    public static function __set_state(array $properties): self
    {
        return new self($properties['key'] ?? '');
    }
}
