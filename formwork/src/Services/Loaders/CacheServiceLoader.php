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

        $parameters = ['defaultTtl' => $this->getTtl($name)];

        if ($class === FilesCache::class) {
            $path = $this->getPath($name);

            if ($path === '') {
                throw new InvalidArgumentException(sprintf('Invalid "path" option for cache "%s": expected non-empty string', $name));
            }

            $parameters['path'] = $path;
        }

        return $container->build($class, $parameters);
    }

    /**
     * Get the path of the cache with the given name
     */
    private function getPath(string $name): string
    {
        // The deprecated option takes precedence, as the new one always has a default value
        if ($name === 'pages' && $this->config->has('system.cache.path')) {
            /** @todo Remove in Formwork 3.0 */
            trigger_error('The "system.cache.path" configuration option is deprecated since Formwork 2.4.0. Use "system.cache.stores.pages.path" instead.', E_USER_DEPRECATED);

            return $this->config->getString('system.cache.path');
        }

        return $this->config->getString("system.cache.stores.{$name}.path");
    }

    /**
     * Get the default time-to-live of the cache with the given name
     */
    private function getTtl(string $name): ?int
    {
        // The deprecated option takes precedence, as the new one always has a default value
        if ($name === 'pages' && $this->config->has('system.cache.time')) {
            /** @todo Remove in Formwork 3.0 */
            trigger_error('The "system.cache.time" configuration option is deprecated since Formwork 2.4.0. Use "system.cache.stores.pages.ttl" instead.', E_USER_DEPRECATED);

            return $this->config->getInt('system.cache.time');
        }

        $key = "system.cache.stores.{$name}.ttl";

        return $this->config->get($key) === null ? null : $this->config->getInt($key);
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
