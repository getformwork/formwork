<?php

namespace Formwork\Services\Loaders;

use Formwork\Cache\AbstractCache;
use Formwork\Cache\ArrayCache;
use Formwork\Cache\CacheManager;
use Formwork\Cache\FilesCache;
use Formwork\Config\Config;
use Formwork\Services\Container;
use Formwork\Services\ServiceLoaderInterface;
use Formwork\Utils\FileSystem;
use InvalidArgumentException;

final class CacheServiceLoader implements ServiceLoaderInterface
{
    public function __construct(
        private Config $config,
    ) {}

    public function load(Container $container): CacheManager
    {
        $cacheManager = new CacheManager();

        $paths = [];

        foreach ($this->config->getArray('system.cache', []) as $name => $options) {
            $name = (string) $name;

            if (!is_array($options)) {
                throw new InvalidArgumentException(sprintf('Invalid options for cache "%s": expected array, %s given', $name, get_debug_type($options)));
            }

            $cache = $this->createCache($container, $name, $options);

            // Caches cannot share the same path, as clearing one would clear the others
            if ($cache instanceof FilesCache) {
                $path = rtrim(FileSystem::normalizePath($cache->path()), DIRECTORY_SEPARATOR);
                if (isset($paths[$path])) {
                    throw new InvalidArgumentException(sprintf('Cache "%s" uses the same path of cache "%s"', $name, $paths[$path]));
                }
                $paths[$path] = $name;
            }

            $cacheManager->add($name, $cache);
        }

        return $cacheManager;
    }

    /**
     * Create a cache instance from its options
     *
     * @param array<mixed> $options
     */
    private function createCache(Container $container, string $name, array $options): AbstractCache
    {
        foreach (['enabled', 'handler', 'ttl'] as $option) {
            if (!array_key_exists($option, $options)) {
                throw new InvalidArgumentException(sprintf('Cache "%s" requires a "%s" option', $name, $option));
            }
        }

        // Whether a cache is enabled is checked by the code using it
        if (!is_bool($options['enabled'])) {
            throw new InvalidArgumentException(sprintf('Invalid "enabled" option for cache "%s": expected boolean, %s given', $name, get_debug_type($options['enabled'])));
        }

        $ttl = $options['ttl'];

        if (!is_int($ttl) && $ttl !== null) {
            throw new InvalidArgumentException(sprintf('Invalid "ttl" option for cache "%s": expected integer or null, %s given', $name, get_debug_type($ttl)));
        }

        $handler = $options['handler'];

        if (!is_string($handler)) {
            throw new InvalidArgumentException(sprintf('Invalid "handler" option for cache "%s": expected string, %s given', $name, get_debug_type($handler)));
        }

        $class = $this->getHandlerClass($handler);

        $parameters = ['defaultTtl' => $ttl];

        if ($class === FilesCache::class) {
            if (!array_key_exists('path', $options)) {
                throw new InvalidArgumentException(sprintf('Cache "%s" requires a "path" option', $name));
            }

            $path = $options['path'];

            if (!is_string($path) || $path === '') {
                throw new InvalidArgumentException(sprintf('Invalid "path" option for cache "%s": expected non-empty string, %s given', $name, get_debug_type($path)));
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
