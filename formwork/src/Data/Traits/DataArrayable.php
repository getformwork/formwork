<?php

namespace Formwork\Data\Traits;

use Formwork\Data\Contracts\Arrayable;

/**
 * @phpstan-require-implements Arrayable
 */
trait DataArrayable
{
    /**
     * @var array<mixed>
     */
    protected array $data = [];

    public function toArray(): array
    {
        return $this->data;
    }
}
