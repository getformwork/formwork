<?php

namespace Formwork\Cache;

class CacheItem implements CacheItemInterface
{
    public function __construct(
        protected mixed $value,
        protected int $expirationTime,
        protected int $cachedTime
    ) {
    }

    /**
     * Return the cached value
     */
    public function value(): mixed
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
            'cachedTime'     => $this->cachedTime,
        ];
    }

    /**
     * Create instance from array
     *
     * @param array{value: mixed, expirationTime: int, cachedTime: int} $data
     */
    public static function fromArray(array $data): static
    {
        return new static($data['value'], $data['expirationTime'], $data['cachedTime']);
    }
}
