<?php

namespace Formwork\Assets;

use Formwork\Assets\Exceptions\AssetResolutionException;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Path;
use Formwork\Utils\Str;
use Formwork\Utils\Uri;

class Assets
{
    /**
     * Resolution paths for namespaced assets
     *
     * @var array<string, array{path: string, uri: string}>
     */
    protected array $resolutionPaths = [];

    /**
     * Asset collection
     */
    private AssetCollection $collection;

    public function __construct()
    {
        $this->collection = new AssetCollection();
    }

    /**
     * Set assets resolution paths
     *
     * @param array<string, array{path: string, uri: string}> $paths
     *
     * @since 2.3.0
     */
    public function setResolutionPaths(array $paths): void
    {
        foreach ($paths as $namespace => ['path' => $path, 'uri' => $uri]) {
            $this->resolutionPaths[$namespace] = [
                'path' => FileSystem::normalizePath($path),
                'uri'  => Uri::normalize(Str::append($uri, '/')),
            ];
        }
    }

    /**
     * Add an asset to the collection
     *
     * @param array<string, mixed> $meta Asset metadata
     */
    public function add(string $key, array $meta = []): void
    {
        if (!$this->collection->has($key)) {
            ['path' => $path, 'uri' => $uri] = $this->resolve($key);
            $this->collection->set($key, new Asset($path, $uri, $meta));
        }
    }

    /**
     * Return whether the collection has an asset with the given key
     *
     * @since 2.2.0
     */
    public function has(string $key): bool
    {
        return $this->collection->has($key);
    }

    /**
     * Get an asset from the collection
     */
    public function get(string $key): Asset
    {
        if (!$this->collection->has($key)) {
            ['path' => $path, 'uri' => $uri] = $this->resolve($key);
            $this->collection->set($key, new Asset($path, $uri));
        }
        /** @var Asset */
        return $this->collection->get($key);
    }

    /**
     * Get stylesheets from the assets collection
     */
    public function stylesheets(): AssetCollection
    {
        return $this->collection->stylesheets();
    }

    /**
     * Get scripts from the assets collection
     */
    public function scripts(): AssetCollection
    {
        return $this->collection->scripts();
    }

    /**
     * Get images from the assets collection
     */
    public function images(): AssetCollection
    {
        return $this->collection->images();
    }

    /**
     * Resolve asset path and URI from key, supporting namespaced syntax
     *
     * @return array{path: string, uri: string}
     *
     * @since 2.3.0
     */
    protected function resolve(string $key): array
    {
        if (Str::startsWith($key, '@')) {
            if (!Str::contains($key, '/')) {
                throw new AssetResolutionException(sprintf('Cannot resolve asset with key "%s": invalid namespaced syntax', $key));
            }

            [$namespace, $relativePath] = explode('/', Str::after($key, '@'), 2);
        } else {
            $namespace = 'template';
            $relativePath = $key;
        }

        if (isset($this->resolutionPaths[$namespace])) {
            ['path' => $path, 'uri' => $uri] = $this->resolutionPaths[$namespace];
            return [
                'path' => FileSystem::joinPaths($path, Path::resolve($relativePath, '/', DIRECTORY_SEPARATOR)),
                'uri'  => Path::join([$uri, Path::resolve($relativePath, '/')]),
            ];
        }

        throw new AssetResolutionException(sprintf('Cannot resolve asset with key "%s": namespace "%s" not defined', $key, $namespace));
    }
}
