<?php

namespace Formwork\Services\Loaders;

use Formwork\Cache\AbstractCache;
use Formwork\Cache\ArrayCache;
use Formwork\Cache\CacheManager;
use Formwork\Cache\FilesCache;
use Formwork\Config\Config;
use Formwork\Services\Container;
use Formwork\Services\ServiceLoaderInterface;
use InvalidArgumentException;

final class CacheServiceLoader implements ServiceLoaderInterface
{
    public function __construct(
        private Config $config,
    ) {}

    public function load(Container $container): CacheManager
    {
        $cacheManager = new CacheManager();

        foreach (array_keys($this->config->getArray('system.cache.stores', [])) as $name) {
            $name = (string) $name;

            $cacheManager->add($name, $this->createCache($container, $name));
        }

        return $cacheManager;
    }

    /**
     * Create a cache instance from the config of the cache with the given name
     */
    private function createCache(Container $container, string $name): AbstractCache
    {
        $key = "system.cache.stores.{$name}";

        $class = $this->getHandlerClass($this->config->getString("{$key}.handler"));

        $parameters = [
            'defaultTtl' => $this->config->get("{$key}.ttl") === null ? null : $this->config->getInt("{$key}.ttl"),
        ];

        if ($class === FilesCache::class) {
            $path = $this->config->getString("{$key}.path");

            if ($path === '') {
                throw new InvalidArgumentException(sprintf('Invalid "path" option for cache "%s": expected non-empty string', $name));
            }

            $parameters['path'] = $path;
        }

        return $container->build($class, $parameters);
    }

    /**
     * @return class-string<AbstractCache>
     */
    private function getHandlerClass(string $handler): string
    {
        return match ($handler) {
            'files' => FilesCache::class,
            'array' => ArrayCache::class,
            default => throw new InvalidArgumentException(sprintf('Cache handler "%s" is not supported', $handler)),
        };
    }
}
