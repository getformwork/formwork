<?php

namespace Formwork\Cache;

use Countable;
use Psr\SimpleCache\CacheInterface;

interface CountableCache extends CacheInterface, Countable
{
    /**
     * Return the number of cached items
     */
    public function count(): int;
}
