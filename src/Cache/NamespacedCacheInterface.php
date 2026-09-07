<?php

namespace Formwork\Cache;

use Psr\SimpleCache\CacheInterface;

interface NamespacedCacheInterface extends CacheInterface
{
    /**
     * Return the cache namespace
     *
     * @return non-empty-string
     */
    public function namespace(): string;
}
