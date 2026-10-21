<?php

namespace Formwork\Panel\Controllers;

use Formwork\Cache\CacheManager;
use Formwork\Http\JsonResponse;
use Formwork\Http\Response;
use Formwork\Router\RouteParams;
use Formwork\Services\Container;
use Formwork\Services\Loaders\ConfigServiceLoader;
use Formwork\Utils\FileSystem;
use Psr\Log\LoggerInterface;

final class CacheController extends AbstractController
{
    public function __construct(
        private Container $container,
        private CacheManager $cacheManager,
        private ?LoggerInterface $logger = null,
    ) {
        $this->container->call(parent::__construct(...));
    }

    /**
     * Cache@clear action
     */
    public function clear(RouteParams $routeParams): JsonResponse|Response
    {
        if (!$this->hasPermission('panel.cache.clear')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        switch ($type = $routeParams->get('type', 'default')) {
            case 'default':
                $caches = ['pages'];
                if ($this->config->getBool('system.images.clearCacheByDefault')) {
                    $caches[] = 'images';
                }
                $this->clearCaches($caches);
                return JsonResponse::success($this->translate('panel.cache.cleared'), data: compact('type'));
            case 'all':
                $this->clearCaches([...array_keys($this->cacheManager->getAll()), 'images', 'config']);
                return JsonResponse::success($this->translate('panel.cache.cleared.all'), data: compact('type'));
            case 'pages':
                $this->clearCaches(['pages']);
                return JsonResponse::success($this->translate('panel.cache.cleared.pages'), data: compact('type'));
            case 'images':
                $this->clearCaches(['images']);
                return JsonResponse::success($this->translate('panel.cache.cleared.images'), data: compact('type'));
            case 'config':
                $this->clearCaches(['config']);
                return JsonResponse::success($this->translate('panel.cache.cleared.config'), data: compact('type'));
            default:
                if ($this->cacheManager->has($type)) {
                    $this->clearCaches([$type]);
                    return JsonResponse::success($this->translate('panel.cache.cleared'), data: compact('type'));
                }
                return JsonResponse::error($this->translate('panel.cache.error'));
        }
    }

    /**
     * Clear specified caches
     *
     * @param array<non-empty-string> $types
     */
    private function clearCaches(array $types): void
    {
        foreach ($types as $type) {
            switch ($type) {
                case 'images':
                    $this->clearImagesCache();
                    break;
                case 'config':
                    $this->clearConfigCache();
                    break;
                default:
                    if ($this->cacheManager->has($type)) {
                        $this->clearManagedCache($type);
                    }
                    break;
            }
        }

        $this->logger?->notice('Cache cleared ({types}) by user {user}', ['types' => implode(', ', $types), 'user' => $this->panel->user()->username()]);
    }

    /**
     * Clear a cache of the cache manager
     *
     * @param non-empty-string $name
     */
    private function clearManagedCache(string $name): void
    {
        $this->cacheManager->get($name)->clear();
        if ($name === 'pages' && $this->site->contentPath() !== null) {
            FileSystem::touch($this->site->contentPath());
        }
    }

    /**
     * Clear images cache
     */
    private function clearImagesCache(): void
    {
        $path = $this->config->getString('system.images.processPath');
        FileSystem::delete($path, recursive: true);
        FileSystem::createDirectory($path, recursive: true);
    }

    /**
     * Clear config cache
     */
    private function clearConfigCache(): void
    {
        ConfigServiceLoader::clearCache($this->app->rootPath());
    }
}
