<?php

namespace Formwork\Http;

use Countable;
use Formwork\Data\Contracts\Arrayable;
use Formwork\Data\Traits\DataArrayable;
use Formwork\Data\Traits\DataCountableIterator;
use Formwork\Data\Traits\DataMultipleGetter;
use Formwork\Data\Traits\DataMultipleSetter;
use Iterator;

/**
 * @implements Iterator<array-key, mixed>
 */
class ResponseHeaders implements Arrayable, Countable, Iterator
{
    use DataArrayable;

    /** @use DataCountableIterator<array<string, string>> */
    use DataCountableIterator;

    /** @use DataMultipleGetter<array<string, string>> */
    use DataMultipleGetter {
        has as protected baseHas;
        get as protected baseGet;
    }

    /** @use DataMultipleSetter<array<string, string>> */
    use DataMultipleSetter {
        set as protected baseSet;
        remove as protected baseRemove;
    }

    /**
     * @param array<string, string> $data
     */
    public function __construct(array $data)
    {
        $this->initialize($data);
    }

    /**
     * Return whether data is present
     */
    public function isEmpty(): bool
    {
        return count($this) === 0;
    }

    public function has(string $key): bool
    {
        return $this->baseHas(Header::fixHeaderName($key));
    }

    /**
     * @template TKey of string
     *
     * @param TKey $key
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->baseGet(Header::fixHeaderName($key), $default);
    }

    public function set(string $key, mixed $value): void
    {
        $this->baseSet(Header::fixHeaderName($key), $value);
        ksort($this->data);
    }

    public function remove(string $key): void
    {
        $this->baseRemove(Header::fixHeaderName($key));
    }

    /**
     * Initialize headers
     *
     * @param array<string, string> $headers
     */
    protected function initialize(array $headers): void
    {
        $this->data = Header::fixHeaderNames($headers);
        ksort($this->data);
    }
}
