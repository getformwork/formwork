<?php

namespace Formwork\Cache;

use Formwork\Data\Contracts\ArraySerializable;

class CacheItem implements ArraySerializable
{
    /**
     * Cached value
     */
    protected $value;

    /**
     * Expiration time
     */
    protected int $expirationTime;

    /**
     * Caching time
     */
    protected int $cachedTime;

    public function __construct($value, int $expirationTime, int $cachedTime)
    {
        $this->value = $value;
        $this->expirationTime = $expirationTime;
        $this->cachedTime = $cachedTime;
    }

    /**
     * Return the cached value
     */
    public function value()
    {
        return $this->value;
    }

    /**
     * Return the expiration time
     */
    public function expirationTime(): int
    {
        return $this->expirationTime;
    }

    /**
     * Return the caching time
     */
    public function cachedTime(): int
    {
        return $this->cachedTime;
    }

    /**
     * Return an array with the cached item
     */
    public function toArray(): array
    {
        return [
            'value'          => $this->value,
            'expirationTime' => $this->expirationTime,
            'cachedTime'     => $this->cachedTime
        ];
    }

    /**
     * Create instance from array
     */
    public static function fromArray(array $data): static
    {
        return new static($data['value'], $data['expirationTime'], $data['cachedTime']);
    }
}
