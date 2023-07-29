<?php

namespace Formwork\Panel\Controllers;

use Formwork\Cache\AbstractCache;
use Formwork\Http\JsonResponse;

class CacheController extends AbstractController
{
    /**
     * Cache@clear action
     */
    public function clear(AbstractCache $cache): JsonResponse
    {
        $this->ensurePermission('cache.clear');
        if ($this->config->get('system.cache.enabled')) {
            $cache->clear();
        }
        return JsonResponse::success($this->translate('panel.cache.cleared'));
    }
}
