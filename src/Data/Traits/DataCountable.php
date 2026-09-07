<?php

namespace Formwork\Data\Traits;

use Countable;

/**
 * @phpstan-require-implements Countable
 *
 * @template TData of array<int|string, mixed> = array<int|string, mixed>
 */
trait DataCountable
{
    /**
     * @var TData
     */
    protected array $data = [];

    /**
     * Get the number of data items
     */
    public function count(): int
    {
        return count($this->data);
    }
}
