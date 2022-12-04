<?php

namespace Formwork\Data;

use Formwork\Utils\Arr;
use LogicException;

final class Collection extends AbstractCollection
{
    /**
     * Convert a collection to mutable
     *
     * @throws LogicException If collection is already mutable
     */
    public function toMutable(): static
    {
        if ($this->isMutable()) {
            throw new LogicException('Cannot convert an already mutable collection to mutable');
        }
        $collection = $this->clone();
        $collection->mutable = true;
        return $collection;
    }

    /**
     * Convert a collection to immutable
     *
     * @throws LogicException If collection is already immutable
     */
    public function toImmutable(): static
    {
        if (!$this->isMutable()) {
            throw new LogicException('Cannot convert an already immmutable collection to immmutable');
        }
        $collection = $this->clone();
        $collection->mutable = false;
        return $collection;
    }

    /**
     * Create a collection with the given options
     */
    public static function create(array $data = [], ?string $dataType = null, bool $associative = false, bool $mutable = false): static
    {
        $collection = new static();

        $collection->associative = $associative;
        $collection->dataType = $dataType;
        $collection->mutable = $mutable;

        $collection->__construct($data);

        return $collection;
    }

    /**
     * Create a collection of the given type
     */
    public static function of(string $dataType, array $data = [], bool $associative = false, bool $mutable = false): static
    {
        return static::create($data, $dataType, $associative, $mutable);
    }

    /**
     * Convert an arrayable object to a collection trying to guess its data type
     */
    public static function from($object, ?bool $typed = null, bool $mutable = false): static
    {
        $data = Arr::from($object);

        if ($typed !== false) {
            $dataType = null;

            foreach ($data as $value) {
                $type = get_debug_type($value);

                // A type was guessed but a different one is found
                if ($dataType !== null && $type !== $dataType) {
                    // Cannot enforce a typed collection when values have different types
                    if ($typed === true) {
                        throw new LogicException('Cannot create a typed collection with data of different types');
                    }

                    $dataType = null;
                    break;
                }

                $dataType = $type;
            }
        }

        return static::create($data, $dataType, Arr::isAssociative($data), $mutable);
    }
}
