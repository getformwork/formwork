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
     * Return whether collection is empty
     */
    public function isEmpty(): bool
    {
        return count($this->items) === 0;
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
     * Return the collection item at the specified index
     *
     *  @return mixed|null
     */
    public function nth(int $index)
    {
        return $this->items[$index] ?? null;
    }

    /**
     * Return the collection item at the specified index. A negative index starts from the last item
     *
     * @return mixed|null
     */
    public function at(int $index)
    {
        return $this->nth($index >= 0 ? $index : $this->count() + $index);
    }

    /**
     * Return a random item or a given default value if the collection is empty
     */
    public function random($default = null)
    {
        return Arr::random($this->items, $default);
    }

    /**
     * Get the index of the given item. Return `null` if the item is not present
     */
    public function indexOf($item): ?int
    {
        return array_search($item, array_values($this->items), true);
    }

    /**
     * Return whether the collection contains the given item
     */
    public function contains($item): bool
    {
        return $this->indexOf($item) !== null;
    }

    /**
     * Append (add to the end) the given item. The method returns this collection instance
     */
    public function push($item): static
    {
        $this->items[] = $item;
        return $this;
    }

    /**
     * Prepend (add to the beginning) the given item. The method returns this collection instance
     */
    public function prepend($item): static
    {
        array_unshift($this->items, $item);
        return $this;
    }

    /**
     * Append the given item. The method returns this collection instance. Alias of `Collection::push()`
     */
    public function append($item): static
    {
        return $this->push($item);
    }

    /**
     * Remove and return the last collection item. Return `null` if the collection is empty
     */
    public function pop()
    {
        return array_pop($this->items);
    }

    /**
     * Remove and return the first collection item. Return `null` if the collection is empty
     */
    public function shift()
    {
        return array_shift($this->items);
    }

    /**
     * Remove the given item. The method returns this collection instance
     */
    public function pull($item): static
    {
        $index = $this->indexOf($item);

        if ($index !== null) {
            unset($this->items[$index]);
        }

        return $this;
    }

    /**
     * Merge the items of another collection. The method returns this collection instance
     */
    public function merge(Collection $collection): static
    {
        $this->items = array_merge($this->items, $collection->items);
        return $this;
    }

    /**
     * Remove the items of another collection. The method returns this collection instance
     */
    public function diff(Collection $collection): static
    {
        $this->items = array_diff($this->items, $collection->items);
        return $this;
    }

    /**
     * Clone the collection instance
     */
    public function clone(): static
    {
        return clone $this;
    }

    /**
     * Reverse the collection items. The method returns a new collection instance
     */
    public function reverse(): static
    {
        $collection = $this->clone();
        $collection->items = array_reverse($collection->items);
        return $collection;
    }

    /**
     * Shuffle the collection items. The method returns a new collection instance
     */
    public function shuffle(): static
    {
        $collection = $this->clone();
        $collection->items = Arr::shuffle($collection->items);
        return $collection;
    }

    /**
     * Slice the collection items starting at a given index.
     * The method returns a new collection instance
     */
    public function slice(int $index, int $length): static
    {
        $collection = $this->clone();
        $collection->items = array_slice($collection->items, $index, $length);
        return $collection;
    }

    /**
     * Return the specified number of collection items starting from the beginning.
     * The method returns a new collection instance
     */
    public function limit(int $length): static
    {
        return $this->slice(0, $length);
    }

    /**
     * Apply a callback to the collection items.
     * The method returns a new collection instance
     */
    public function map(callable $callback): static
    {
        $collection = $this->clone();
        $collection->items = array_map($callback, $collection->items);
        return $collection;
    }

    /**
     * Filter the collection items using a callback.
     * Only the elements on which the callback return true are retained.
     * The method returns a new collection instance
     */
    public function filter(callable $callback): static
    {
        $collection = $this->clone();
        $collection->items = array_values(array_filter($collection->items, $callback));
        return $collection;
    }

    /**
     * Sort collection items using the given options
     * The method returns a new collection instance
     *
     * @see Arr::sort() for the available options
     */
    public function sort(array $options = []): static
    {
        $collection = $this->clone();
        $collection->items = Arr::sort($collection->items, $options);
        return $collection;
    }
}
