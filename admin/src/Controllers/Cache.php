<?php

namespace Formwork\Admin\Controllers;

use Formwork\Admin\Utils\JSONResponse;
use Formwork\Utils\FileSystem;

class Cache extends AbstractController
{
    /**
     * Cache@clear action
     */
    public function clear()
    {
        $this->ensurePermission('cache.clear');
        $path = $this->option('cache.path');
        if (FileSystem::exists($path)) {
            FileSystem::delete($path, true);
        }
        JSONResponse::success($this->label('cache.cleared'))->send();
    }
}
