<?php

namespace Formwork\Tests\Utils\Fixtures;

use Formwork\Data\Contracts\Arrayable;

final class ArrayableFixture implements Arrayable
{
    public function __construct(private array $data) {}

    public function toArray(): array
    {
        return $this->data;
    }
}
