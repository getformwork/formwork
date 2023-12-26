<?php

namespace Formwork\Cache;

use Formwork\Data\Contracts\ArraySerializable;

interface CacheItemInterface extends ArraySerializable
{
    public function __construct(mixed $value, int $expirationTime, int $cachedTime);

    /**
     * Return the cached value
     */
    public function value(): mixed;

    /**
     * Return the expiration time
     */
    public function expirationTime(): int;

    /**
     * Return the caching time
     */
    public function cachedTime(): int;
}
