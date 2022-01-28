<?php

namespace Formwork\Data;

use Formwork\Utils\Arr;
use Countable;
use Iterator;
use ReturnTypeWillChange;

class Collection implements Countable, Iterator
{
    /**
     * Array containing collection items
     */
    protected array $items = [];

    /**
     * Create a new Collection instance
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * Rewind the iterator to the first element
     */
    public function rewind(): void
    {
        reset($this->items);
    }

    /**
     * Return the current element
     */
    #[ReturnTypeWillChange]
    public function current()
    {
        return current($this->items);
    }

    /**
     * Return the key of the current element
     */
    #[ReturnTypeWillChange]
    public function key()
    {
        return key($this->items);
    }

    /**
     * Move forward to next element
     */
    public function next(): void
    {
        next($this->items);
    }

    /**
     * Check if current position is valid
     */
    public function valid(): bool
    {
        return $this->key() !== null;
    }

    /**
     * Return the number of items
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Return an array containing collection items
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * Return first collection item
     *
     * @return mixed|null
     */
    public function first()
    {
        return $this->items[0] ?? null;
    }

    /**
     * Return last collection item
     *
     * @return mixed|null
     */
    public function last()
    {
        return $this->items[$this->count() - 1] ?? null;
    }

    /**
     * Return a random item or a given default value if the collection is empty
     */
    public function random($default = null)
    {
        return Arr::random($this->items, $default);
    }

    /**
     * Return whether collection is empty
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }
}
