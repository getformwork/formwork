<?php

namespace Formwork\Data;

use Countable;
use Formwork\Data\Contracts\Arrayable;
use Formwork\Data\Traits\DataArrayable;
use Formwork\Data\Traits\DataCountableIterator;
use Formwork\Data\Traits\DataMultipleGetter;
use Formwork\Data\Traits\DataMultipleSetter;
use Formwork\Utils\Arr;
use Formwork\Utils\Constraint;
use Iterator;
use LogicException;

/**
 * @implements Iterator<int|string, T>
 *
 * @template T
 */
abstract class AbstractCollection implements Arrayable, Countable, Iterator
{
    /** @use DataArrayable<array<int|string, T>> */
    use DataArrayable;

    /** @use DataCountableIterator<array<int|string, T>> */
    use DataCountableIterator;

    /** @use DataMultipleGetter<array<string, T>> */
    use DataMultipleGetter {
        has as protected baseHas;
        get as protected baseGet;
    }

    /** @use DataMultipleSetter<array<string, T>> */
    use DataMultipleSetter {
        set as protected baseSet;
        remove as protected baseRemove;
    }

    /**
     * @var array<int|string, T>
     */
    protected array $data = [];

    /**
     * Whether the collection is associative
     */
    protected bool $associative = false;

    /**
     * The type of collection data
     */
    protected ?string $dataType = null;

    /**
     * Whether the collection is mutable
     */
    protected bool $mutable = false;

    /**
     * @param array<int|string, T> $data
     *
     * @throws LogicException If collection associativeness mismatches data or if typed collection is created from data of different types
     */
    public function __construct(array $data = [])
    {
        if ($data !== [] && ($selfAssoc = $this->isAssociative()) !== ($dataAssoc = Arr::isAssociative($data))) {
            throw new LogicException(sprintf(
                '%s collections cannot be created from %s data',
                $selfAssoc ? 'Associative' : 'Non-associative',
                $dataAssoc ? 'associative' : 'non-associative'
            ));
        }

        if ($this->dataType !== null && !Arr::every($data, fn($value) => Constraint::isOfType($value, $this->dataType, unionTypes: true))) {
            throw new LogicException('Typed collections cannot be created from data of different types');
        }

        $this->data = $data;
    }

    /**
     * Return whether the collection is associative
     */
    public function isAssociative(): bool
    {
        return $this->associative;
    }

    /**
     * Return whether the collection is mutable
     */
    public function isMutable(): bool
    {
        return $this->mutable;
    }

    /**
     * Return whether the collection is typed
     */
    public function isTyped(): bool
    {
        return $this->dataType !== null;
    }

    /**
     * Get the data type
     */
    public function dataType(): ?string
    {
        return $this->dataType;
    }

    /**
     * Return whether collection is empty
     */
    public function isEmpty(): bool
    {
        return $this->data === [];
    }

    /**
     * Return the collection item at the specified index
     *
     * @return T|null
     */
    public function nth(int $index): mixed
    {
        return Arr::nth($this->data, $index);
    }

    /**
     * Return the collection item at the specified index
     *
     * A negative index starts from the last item
     *
     * @return T|null
     */
    public function at(int $index): mixed
    {
        return Arr::at($this->data, $index);
    }

    /**
     * Return first collection item
     *
     * @return T|null
     */
    public function first(): mixed
    {
        return $this->at(0);
    }

    /**
     * Return last collection item
     *
     * @return T|null
     */
    public function last(): mixed
    {
        return $this->at(-1);
    }

    /**
     * Return a random item or a given default value if the collection is empty
     *
     * @param T|null $default
     *
     * @return T|null
     */
    public function random(mixed $default = null): mixed
    {
        return Arr::random($this->data, $default);
    }

    /**
     * Get the index of the given value
     *
     * Return `null` if the item is not present
     */
    public function indexOf(mixed $value): ?int
    {
        return Arr::indexOf($this->data, $value);
    }

    /**
     * Get the key of the given value
     *
     * Return `null` if the item is not present
     *
     * @throws LogicException If the collection is not associative
     */
    public function keyOf(mixed $value): int|string|null
    {
        if (!$this->isAssociative()) {
            throw new LogicException('Only associative collections support keys');
        }

        return Arr::keyOf($this->data, $value);
    }

    /**
     * Get the keys of all items
     *
     * @throws LogicException If the collection is not associative
     *
     * @return list<int|string>
     */
    public function keys(): array
    {
        if (!$this->isAssociative()) {
            throw new LogicException('Only associative collections support keys');
        }

        return array_keys($this->data);
    }

    /**
     * Get the values of all items
     *
     * @return list<T>
     */
    public function values(): array
    {
        return array_values($this->data);
    }

    /**
     * Return whether the collection contains the given value
     */
    public function contains(mixed $value): bool
    {
        return $this->indexOf($value) !== null;
    }

    /**
     * Return whether the given callback returns `true` for every item in the collection
     */
    public function every(callable $callback): bool
    {
        return Arr::every($this->data, $callback);
    }

    /**
     * Return whether the given callback returns `true` for some item in the collection
     */
    public function some(callable $callback): bool
    {
        return Arr::some($this->data, $callback);
    }

    /**
     * Find the first item in the collection for which the given callback returns `true`
     *
     * @return T|null
     */
    public function find(callable $callback): mixed
    {
        return Arr::find($this->data, $callback);
    }

    /**
     * Clone the collection instance
     */
    public function clone(): static
    {
        return clone $this;
    }

    /**
     * Clone the collection and its items
     */
    public function deepClone(): static
    {
        $clone = $this->clone();
        $clone->data = Arr::map($clone->data, fn($value) => is_object($value) ? clone $value : $value);
        return $clone;
    }

    /**
     * Return a copy of the collection with its items reversed
     */
    public function reverse(): static
    {
        $collection = $this->clone();
        $collection->data = array_reverse($collection->data);
        return $collection;
    }

    /**
     * Return a copy of the collection with its items shuffled
     */
    public function shuffle(): static
    {
        $collection = $this->clone();
        $collection->data = Arr::shuffle($collection->data);
        return $collection;
    }

    /**
     * Return a copy of the collection with duplicates removed
     */
    public function unique(): static
    {
        $collection = $this->clone();
        $collection->data = array_unique($collection->data);
        return $collection;
    }

    /**
     * Return a copy of the collection with duplicates only
     */
    public function duplicates(): static
    {
        $collection = $this->clone();
        $collection->data = Arr::duplicates($collection->data);
        return $collection;
    }

    /**
     * Return a copy of the collection with its items from a given index
     */
    public function slice(int $index, ?int $length = null): static
    {
        $collection = $this->clone();
        $collection->data = array_slice($collection->data, $index, $length);
        return $collection;
    }

    /**
     * Return a copy of the collection with only the given number of items
     * counting from the beginning
     */
    public function limit(int $length): static
    {
        return $this->slice(0, $length);
    }

    /**
     * Run a callback for each item in the collection. If the callback returns `false`, iteration is stopped
     *
     * @since 2.2.0
     */
    public function each(callable $callback): static
    {
        foreach ($this->data as $key => $value) {
            if ($callback($value, $key) === false) {
                break;
            }
        }
        return $this;
    }

    /**
     * Apply a callback to the collection items
     */
    public function map(callable $callback): static
    {
        $collection = $this->clone();
        $collection->data = Arr::map($collection->data, $callback);
        return $collection;
    }

    /**
     * Filter the collection items using a callback
     *
     * Only the items on which the callback returns `true` are retained
     */
    public function filter(callable $callback): static
    {
        $collection = $this->clone();
        $collection->data = Arr::filter($collection->data, $callback);
        return $collection;
    }

    /**
     * Reject the collection items using a callback
     *
     * Only the items on which the callback returns `false` are retained
     *
     * This is the opposite of `Collection::filter()`
     */
    public function reject(callable $callback): static
    {
        $collection = $this->clone();
        $collection->data = Arr::reject($collection->data, $callback);
        return $collection;
    }

    /**
     * Return a copy of the collection with its items sorted with the given options
     *
     * Keys are preserved by default in associative collections
     *
     * @param \SORT_ASC|\SORT_DESC                                   $direction     Direction of sorting. Possible values are `SORT_ASC` and `SORT_DESC`.
     * @param \SORT_NATURAL|\SORT_NUMERIC|\SORT_REGULAR|\SORT_STRING $type          Type of sorting. Possible values are `SORT_NATURAL`, `SORT_NUMERIC`, `SORT_REGULAR` and `SORT_STRING`.
     * @param array<mixed>|callable|null                             $sortBy        A callback or second array of values used to sort the first
     * @param                                                        $caseSensitive Whether to perform a case-sensitive sorting
     * @param                                                        $preserveKeys  Whether to preserve array keys after sorting. If `null`, keys are preserved only in associative collections
     */
    public function sort(
        int $direction = SORT_ASC,
        int $type = SORT_NATURAL,
        array|callable|null $sortBy = null,
        bool $caseSensitive = false,
        ?bool $preserveKeys = null,
    ): static {
        $collection = $this->clone();
        $collection->data = Arr::sort(
            $collection->data,
            $direction,
            $type,
            $sortBy,
            $caseSensitive,
            $preserveKeys ?? $this->isAssociative()
        );
        return $collection;
    }

    /**
     * Group collection items using a callback
     *
     * @return array<string, list<T>>
     */
    public function group(callable $callback): array
    {
        return Arr::group($this->data, $callback);
    }

    /**
     * Extract an associative array of values corresponding to the specified key from the collection items
     *
     * Typed collections should implement their own version of this method, optimised for their data type
     *
     * @return array<T>
     */
    public function extract(string $key, mixed $default = null): array
    {
        // @phpstan-ignore argument.templateType
        return Arr::extract($this->data, $key, $default);
    }

    /**
     * Extract a list of values corresponding to the specified key from the collection items
     *
     * This method is similar to `extract()` but does not preserve keys
     *
     * @return list<T>
     */
    public function pluck(string $key, mixed $default = null): array
    {
        return array_values($this->extract($key, $default));
    }

    /**
     * Flatten the collection items up to the specified depth
     */
    public function flatten(int $depth = PHP_INT_MAX): static
    {
        $collection = $this->clone();
        $collection->data = Arr::flatten($collection->data, $depth);
        return $collection;
    }

    /**
     * Filter the collection using the key from each item
     *
     * @throws LogicException If unexpected third argument is passed or if filter is unknown
     */
    public function filterBy(string $key, mixed $value = true, mixed $comparison = null): static
    {
        $values = $this->extract($key);

        $args = func_num_args();

        if (is_callable($value)) {
            if ($args > 2) {
                throw new LogicException(sprintf('Unexpected third argument passed to %s()', __METHOD__));
            }
            $values = Arr::map($values, $value);
            $parameters = [Constraint::isEqualTo(...), true, true];
        }

        if ($args > 2) {
            $parameters = match ($value) {
                'equalTo', '=='              => [Constraint::isEqualTo(...), $comparison, false],
                'notEqualTo', '!='           => [Constraint::isNotEqualTo(...), $comparison, false],
                'strictlyEqualTo', '==='     => [Constraint::isEqualTo(...), $comparison, true],
                'strictlyNotEqualTo', '!=='  => [Constraint::isNotEqualTo(...), $comparison, true],
                'greaterThan', '>'           => [Constraint::isGreaterThan(...), $comparison],
                'greaterThanOrEqualTo', '>=' => [Constraint::isGreaterThanOrEqualTo(...), $comparison],
                'lessThan', '<'              => [Constraint::isLessThan(...), $comparison],
                'lessThanOrEqualTo', '<='    => [Constraint::isLessThanOrEqualTo(...), $comparison],
                default                      => throw new LogicException(sprintf('Unknown filter "%s"', $value)),
            };
        }

        [$filter, $comparison, $strict] = array_replace([Constraint::isEqualTo(...), $value, false], $parameters ?? []);

        // @phpstan-ignore arguments.count
        return $this->filter(fn($v, $k) => $filter($values[$k], $comparison, $strict));
    }

    /**
     * Sort the collection using the given key from each item
     *
     * @param \SORT_ASC|\SORT_DESC                                   $direction     Direction of sorting. Possible values are `SORT_ASC` and `SORT_DESC`.
     * @param \SORT_NATURAL|\SORT_NUMERIC|\SORT_REGULAR|\SORT_STRING $type          Type of sorting. Possible values are `SORT_NATURAL`, `SORT_NUMERIC`, `SORT_REGULAR` and `SORT_STRING`.
     * @param                                                        $caseSensitive Whether to perform a case-sensitive sorting
     * @param                                                        $preserveKeys  Whether to preserve array keys after sorting
     */
    public function sortBy(
        string $key,
        int $direction = SORT_ASC,
        int $type = SORT_NATURAL,
        bool $caseSensitive = false,
        bool $preserveKeys = true,
    ): static {
        return $this->sort($direction, $type, $this->extract($key), $caseSensitive, $preserveKeys);
    }

    /**
     * Group the collection using the given key from each item
     *
     * @param T|null $default
     *
     * @return array<string, list<T|null>>
     */
    public function groupBy(string $key, mixed $default = null): array
    {
        $values = $this->extract($key, $default);
        return $this->group(fn($v, $k) => $values[$k]);
    }

    /**
     * Return a copy of the collection indexed by the given key from each item
     *
     * @since 2.2.0
     */
    public function keyBy(string $key, mixed $default = null): static
    {
        $collection = $this->clone();
        $collection->data = array_combine($this->extract($key, $default), $collection->data);
        return $collection;
    }

    /**
     * Return a copy of the collection with the given values
     *
     * If a value is already in the collection, it will not be added
     *
     * @param T ...$values
     */
    public function with(mixed ...$values): static
    {
        $collection = $this->clone();

        foreach ($values as $value) {
            if (!$collection->contains($value)) {
                $collection->data[] = $value;
            }
        }

        return $collection;
    }

    /**
     * Return a copy of the collection without the given values
     *
     * @param T ...$values
     */
    public function without(mixed ...$values): static
    {
        $collection = $this->clone();

        foreach ($values as $value) {
            Arr::pull($collection->data, $value);
        }

        return $collection;
    }

    /**
     * Return a special object on which property accesses and method calls
     * are redirected to every item of the collection and the results
     * are collected again
     *
     * @return CollectionDataProxy<T>
     */
    public function everyItem(): CollectionDataProxy
    {
        return new CollectionDataProxy($this);
    }

    /**
     * Return a copy of the collection with the union of the values of the given collections
     *
     * @param self<T> ...$collections
     */
    public function union(self ...$collections): static
    {
        $collection = $this->clone();

        foreach ($collections as $otherCollection) {
            $collection->data += $otherCollection->data;
        }

        return $collection;
    }

    /**
     * Return a copy of the collection with the intersection of the values of the given collections
     *
     * @param self<T> ...$collections
     */
    public function intersection(self ...$collections): static
    {
        $collection = $this->clone();

        foreach ($collections as $otherCollection) {
            $collection->data = array_intersect($collection->data, $otherCollection->data);
        }

        return $collection;
    }

    /**
     * Return a copy of the collection with the values of the given collections removed
     *
     * @param self<T> ...$collections
     */
    public function difference(self ...$collections): static
    {
        $collection = $this->clone();

        foreach ($collections as $otherCollection) {
            $collection->data = array_diff($collection->data, $otherCollection->data);
        }

        return $collection;
    }

    /**
     * Add the given value to the collection
     *
     * @param T $value
     *
     * @throws LogicException If the collection is not mutable, associative, or if value type is invalid
     */
    public function add(mixed $value): void
    {
        if (!$this->isMutable() || $this->isAssociative()) {
            throw new LogicException('Values can be added only to mutable and non-associative collections');
        }

        if ($this->dataType !== null && !Constraint::isOfType($value, $this->dataType, unionTypes: true)) {
            throw new LogicException(sprintf('Value must be of type %s to be added, %s given', $this->dataType, get_debug_type($value)));
        }

        $this->data[] = $value;
    }

    /**
     * Add multiple values to the collection
     *
     * @param list<T> $values
     */
    public function addMultiple(array $values): void
    {
        foreach ($values as $value) {
            $this->add($value);
        }
    }

    /**
     * Remove all occurrences of the given value from the collection
     *
     * @param T $value
     *
     * @throws LogicException If the collection is not mutable or is associative
     */
    public function pull(mixed $value): void
    {
        if (!$this->isMutable() || $this->isAssociative()) {
            throw new LogicException('Values can be pulled only from mutable and non-associative collections');
        }

        Arr::pull($this->data, $value);
    }

    /**
     * Remove all occurrences of the given values from the collection
     *
     * @param list<T> $values
     */
    public function pullMultiple(array $values): void
    {
        foreach ($values as $value) {
            $this->pull($value);
        }
    }

    /**
     * Move a collection item from the given index to another
     */
    public function moveItem(int $fromIndex, int $toIndex): void
    {
        Arr::moveItem($this->data, $fromIndex, $toIndex);
    }

    /**
     * Return whether the collection has an item with the given key
     *
     * @throws LogicException If the collection is not associative
     */
    public function has(string $key): bool
    {
        if (!$this->isAssociative()) {
            throw new LogicException('Value presence can be checked only in associative collections');
        }
        return $this->baseHas($key);
    }

    /** Get a collection item by the given key
     *
     * A default value is returned if the item is not present
     *
     * @template TKey of string
     * @template TDefault
     *
     * @param TKey     $key
     * @param TDefault $default
     *
     * @throws LogicException If the collection is not associative
     *
     * @return T|TDefault
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->isAssociative()) {
            throw new LogicException('Values can be get only from associative collections');
        }
        return $this->baseGet($key, $default);
    }

    /**
     * Set a collection item
     *
     * @param T $value
     *
     * @throws LogicException If the collection is not associative, not mutable, or if value type is invalid
     */
    public function set(string $key, mixed $value): void
    {
        if (!$this->isAssociative() || !$this->isMutable()) {
            throw new LogicException('Values can be set only to associative and mutable collections');
        }

        if ($this->dataType !== null && !Constraint::isOfType($value, $this->dataType, unionTypes: true)) {
            throw new LogicException(sprintf('Value must be of type %s, %s given', $this->dataType, get_debug_type($value)));
        }

        if ($this->dataType() !== 'array') {
            // Avoid dot notation traversal by setting the key before
            // @phpstan-ignore assign.propertyType
            $this->data[$key] = null;
        }

        $this->baseSet($key, $value);
    }

    /**
     * Remove a collection item by key
     *
     * @throws LogicException If the collection is not associative or not mutable
     */
    public function remove(string $key): void
    {
        if (!$this->isAssociative() || !$this->isMutable()) {
            throw new LogicException('Values can be removed only from associative and mutable collections');
        }
        $this->baseRemove($key);
    }

    /**
     * Merge another collection into the current
     *
     * @param self<T> ...$collection
     *
     * @throws LogicException If the collection is not mutable, if associativeness differs, or if data types differ
     */
    public function merge(self $collection): void
    {
        if (!$this->isMutable()) {
            throw new LogicException('Values can be merged only into mutable collections');
        }

        if ($collection->isAssociative() !== $this->isAssociative()) {
            throw new LogicException('Collections cannot be merged if their associativeness is different');
        }

        if ($collection->dataType() !== $this->dataType()) {
            throw new LogicException('Collections with data of different types cannot be merged');
        }

        $this->data = [...$this->data, ...$collection->data];
    }
}
