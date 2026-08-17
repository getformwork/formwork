<?php

namespace Formwork\Data;

use Formwork\Utils\Arr;
use LogicException;

/**
 * @template T
 *
 * @extends AbstractCollection<T>
 */
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
            throw new LogicException('Cannot convert an already immutable collection to immutable');
        }
        $collection = $this->clone();
        $collection->mutable = false;
        return $collection;
    }

    /**
     * Create a collection with the given options
     *
     * @param array<mixed> $data
     * @param string|null  $dataType    Type constraint for collection items
     * @param bool         $associative Whether the collection should be associative
     * @param bool         $mutable     Whether the collection should be mutable
     */
    public static function create(array $data = [], ?string $dataType = null, bool $associative = false, bool $mutable = false): static
    {
        $collection = new self();

        $collection->associative = $associative;
        $collection->dataType = $dataType;
        $collection->mutable = $mutable;

        $collection->__construct($data);

        return $collection;
    }

    /**
     * Create a collection of the given type
     *
     * @param string       $dataType    Type constraint for collection items
     * @param array<mixed> $data
     * @param bool         $associative Whether the collection should be associative
     * @param bool         $mutable     Whether the collection should be mutable
     */
    public static function of(string $dataType, array $data = [], bool $associative = false, bool $mutable = false): static
    {
        return self::create($data, $dataType, $associative, $mutable);
    }

    /**
     * Convert an arrayable object to a collection trying to guess its data type
     *
     * @param mixed     $object  The object to convert to a collection
     * @param bool|null $typed   Whether to enforce typing (null for auto-detection)
     * @param bool      $mutable Whether the collection should be mutable
     *
     * @throws LogicException If creating a typed collection with data of different types
     */
    public static function from(mixed $object, ?bool $typed = null, bool $mutable = false): static
    {
        $data = Arr::from($object);
        $dataType = null;

        if ($typed !== false) {
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

        return self::create($data, $dataType, Arr::isAssociative($data), $mutable);
    }
}
