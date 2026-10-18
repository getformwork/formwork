<?php

namespace Formwork\Tests\Unit\Utils\Fixtures;

use Formwork\Data\Contracts\Arrayable;

final class ArrayableFixture implements Arrayable
{
    /**
     * @param array<array-key, string> $data
     */
    public function __construct(private array $data) {}

    public function toArray(): array
    {
        return $this->data;
    }
}
