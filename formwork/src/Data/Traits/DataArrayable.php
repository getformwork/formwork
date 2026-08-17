<?php

namespace Formwork\Data\Traits;

use Formwork\Data\Contracts\Arrayable;

/**
 * @phpstan-require-implements Arrayable
 *
 * @template TData of array<int|string, mixed> = array<string, mixed>
 */
trait DataArrayable
{
    /**
     * @var TData
     */
    protected array $data = [];

    /**
     * @return TData
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
