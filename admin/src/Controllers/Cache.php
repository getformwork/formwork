<?php

namespace Formwork\Admin\Controllers;

use Formwork\Admin\Utils\JSONResponse;
use Formwork\Core\Formwork;

class Cache extends AbstractController
{
    /**
     * Cache@clear action
     */
    public function clear()
    {
        $this->ensurePermission('cache.clear');
        Formwork::instance()->cache()->clear();
        JSONResponse::success($this->label('cache.cleared'))->send();
    }
}
