<?php

namespace Formwork\Admin\Controllers;

use Formwork\Formwork;
use Formwork\Response\JSONResponse;

class CacheController extends AbstractController
{
    /**
     * Cache@clear action
     */
    public function clear(): JSONResponse
    {
        $this->ensurePermission('cache.clear');
        if (Formwork::instance()->config()->get('cache.enabled')) {
            Formwork::instance()->cache()->clear();
        }
        return JSONResponse::success($this->admin()->translate('admin.cache.cleared'));
    }
}
