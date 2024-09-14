<?php

namespace Formwork\Data\Traits;

use Iterator;

/**
 * @phpstan-require-implements Iterator
 */
trait DataIterator
{
    protected array $data = [];

    public function rewind(): void
    {
        reset($this->data);
    }

    public function current(): mixed
    {
        return current($this->data);
    }

    public function key(): mixed
    {
        // @phpstan-ignore-next-line
        return key($this->data);
    }

    public function next(): void
    {
        next($this->data);
    }

    public function valid(): bool
    {
        return $this->key() !== null;
    }
}
