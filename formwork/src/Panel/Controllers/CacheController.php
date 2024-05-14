<?php

namespace Formwork\Panel\Controllers;

use Formwork\Cache\AbstractCache;
use Formwork\Http\JsonResponse;
use Formwork\Router\RouteParams;
use Formwork\Utils\FileSystem;

class CacheController extends AbstractController
{
    /**
     * Cache@clear action
     */
    public function clear(RouteParams $routeParams, AbstractCache $cache): JsonResponse
    {
        $this->ensurePermission('cache.clear');

        switch ($type = $routeParams->get('type', 'default')) {
            case 'default':
                $this->clearPagesCache($cache);
                if ($this->config->get('system.images.clearCacheByDefault')) {
                    $this->clearImagesCache();
                }
                return JsonResponse::success($this->translate('panel.cache.cleared'), data: compact('type'));
            case 'pages':
                $this->clearPagesCache($cache);
                return JsonResponse::success($this->translate('panel.cache.cleared.pages'), data: compact('type'));
            case 'images':
                $this->clearImagesCache();
                return JsonResponse::success($this->translate('panel.cache.cleared.images'), data: compact('type'));
            default:
                return JsonResponse::error($this->translate('panel.cache.error'));
        }
    }

    private function clearPagesCache(AbstractCache $cache): void
    {
        if ($this->config->get('system.cache.enabled')) {
            $cache->clear();
        }
    }

    private function clearImagesCache(): void
    {
        $path = $this->config->get('system.images.processPath');
        FileSystem::delete($path, recursive: true);
        FileSystem::createDirectory($path, recursive: true);
    }
}
