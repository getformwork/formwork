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
        private CacheManager $cacheManager,
        private string $namespace,
        private ?string $path = null,
        private ?int $defaultTtl = null,
        private ?string $handler = null,
    ) {}

    public function load(Container $container): AbstractCache
    {
        $this->handler ??= $this->config->getString("system.cache.namespaces.{$this->namespace}.handler", 'files');
        $cache = $container->build(
            $this->getHandlerClass($this->handler),
            [
                'path'       => $this->path ?? $this->config->getString('system.cache.path'),
                'namespace'  => $this->namespace,
                'defaultTtl' => $this->defaultTtl ?? $this->config->getInt('system.cache.time'),
            ]
        );
        $this->cacheManager->add($cache);
        return $cache;
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
