<?php

namespace Formwork\Data;

/**
 * @template T
 */
final class CollectionDataProxy
{
    /**
     * @param AbstractCollection<T> $collection
     */
    public function __construct(
        private AbstractCollection $collection,
    ) {}

    /**
     * @return Collection<mixed>
     */
    public function __get(string $name): Collection
    {
        $result = [];

        foreach ($this->collection as $key => $item) {
            $result[$key] = $item->{$name};
        }

        return Collection::from($result, mutable: $this->collection->isMutable());
    }

    public function __set(string $name, mixed $value): void
    {
        foreach ($this->collection as $item) {
            $item->{$name} = $value;
        }
    }

    /**
     * @param list<mixed> $arguments
     *
     * @return Collection<mixed>
     */
    public function __call(string $name, array $arguments): Collection
    {
        $result = [];

        foreach ($this->collection as $key => $item) {
            $result[$key] = $item->{$name}(...$arguments);
        }

        return Collection::from($result, mutable: $this->collection->isMutable());
    }
}
