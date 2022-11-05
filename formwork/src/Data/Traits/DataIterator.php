<?php

namespace Formwork\Data\Traits;

trait DataIterator // implements \Iterator
{
    protected array $data = [];

    public function rewind(): void
    {
        reset($this->data);
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        return current($this->data);
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
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
