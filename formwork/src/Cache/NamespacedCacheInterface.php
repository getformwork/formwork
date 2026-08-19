<?php

namespace Formwork\Cache;

use Psr\SimpleCache\CacheInterface;

interface NamespacedCacheInterface extends CacheInterface
{
    /**
     * Return the cache namespace
     */
    public function namespace(): ?string;
}
