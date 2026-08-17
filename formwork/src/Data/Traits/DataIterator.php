<?php

namespace Formwork\Data\Traits;

use Iterator;

/**
 * @phpstan-require-implements Iterator
 *
 * @template TData of array<int|string, mixed> = array<int|string, mixed>
 */
trait DataIterator
{
    /**
     * @var TData
     */
    protected array $data = [];

    /**
     * @internal
     */
    public function rewind(): void
    {
        reset($this->data);
    }

    /**
     * @internal
     */
    public function current(): mixed
    {
        /** @var mixed */
        return current($this->data);
    }

    /**
     * @return int|string|null
     *
     * @internal
     */
    public function key(): mixed
    {
        return key($this->data);
    }

    /**
     * @internal
     */
    public function next(): void
    {
        next($this->data);
    }

    /**
     * @internal
     */
    public function valid(): bool
    {
        return $this->key() !== null;
    }
}
