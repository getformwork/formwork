<?php

namespace Formwork\Panel\Controllers;

use Formwork\Cache\AbstractCache;
use Formwork\Http\JsonResponse;
use Formwork\Http\Response;
use Formwork\Router\RouteParams;
use Formwork\Services\Attributes\Service;
use Formwork\Services\Container;
use Formwork\Services\Loaders\ConfigServiceLoader;
use Formwork\Utils\Arr;
use Formwork\Utils\FileSystem;
use Psr\Log\LoggerInterface;

final class CacheController extends AbstractController
{
    public function __construct(
        private Container $container,
        #[Service('cache.pages')]
        private AbstractCache $pagesCache,
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
                $this->clearCaches([
                    'pages'  => true,
                    'images' => $this->config->getBool('system.images.clearCacheByDefault'),
                ]);
                return JsonResponse::success($this->translate('panel.cache.cleared'), data: compact('type'));
            case 'all':
                $this->clearCaches(['pages' => true, 'images' => true, 'config' => true]);
                return JsonResponse::success($this->translate('panel.cache.cleared.all'), data: compact('type'));
            case 'pages':
                $this->clearCaches(['pages' => true]);
                return JsonResponse::success($this->translate('panel.cache.cleared.pages'), data: compact('type'));
            case 'images':
                $this->clearCaches(['images' => true]);
                return JsonResponse::success($this->translate('panel.cache.cleared.images'), data: compact('type'));
            case 'config':
                $this->clearCaches(['config' => true]);
                return JsonResponse::success($this->translate('panel.cache.cleared.config'), data: compact('type'));
            default:
                return JsonResponse::error($this->translate('panel.cache.error'));
        }
    }

    /**
     * Clear specified caches
     *
     * @param array<string, bool> $types
     */
    private function clearCaches(array $types): void
    {
        $types = Arr::filter($types, fn($clear) => $clear);

        foreach (array_keys($types) as $type) {
            switch ($type) {
                case 'pages':
                    $this->clearPagesCache();
                    break;
                case 'images':
                    $this->clearImagesCache();
                    break;
                case 'config':
                    $this->clearConfigCache();
                    break;
            }
        }

        $this->logger?->notice('Cache cleared ({types}) by user {user}', ['types' => implode(', ', array_keys($types)), 'user' => $this->panel->user()->username()]);
    }

    /**
     * Clear pages cache
     */
    private function clearPagesCache(): void
    {
        $this->pagesCache->clear();
        if ($this->site->contentPath() !== null) {
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
        ConfigServiceLoader::clearCache();
    }
}
