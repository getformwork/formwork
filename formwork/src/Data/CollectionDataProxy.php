<?php

namespace Formwork\Data;

class CollectionDataProxy
{
    protected AbstractCollection $collection;

    public function __construct(AbstractCollection $collection)
    {
        $this->collection = $collection;
    }

    public function __get(string $name)
    {
        $result = [];

        foreach ($this->collection as $key => $item) {
            $result[$key] = $item->{$name};
        }

        return Collection::from($result, mutable: $this->collection->isMutable());
    }

    public function __set(string $name, $value): void
    {
        foreach ($this->collection as $item) {
            $item->{$name} = $value;
        }
    }

    public function __call(string $name, array $arguments)
    {
        $result = [];

        foreach ($this->collection as $key => $item) {
            $result[$key] = $item->{$name}(...$arguments);
        }

        return Collection::from($result, mutable: $this->collection->isMutable());
    }
}
