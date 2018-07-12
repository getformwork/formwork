<?php

namespace Formwork\Admin\Controllers;

use Formwork\Admin\Admin;
use Formwork\Admin\Utils\JSONResponse;
use Formwork\Core\Formwork;
use Formwork\Utils\FileSystem;

class Cache extends AbstractController
{
    public function clear()
    {
        Admin::instance()->ensureLogin();
        $path = Formwork::instance()->option('cache.path');
        if (FileSystem::exists($path)) {
            FileSystem::delete($path, true);
        }
        JSONResponse::success($this->label('cache.cleared'))->send();
    }
}
