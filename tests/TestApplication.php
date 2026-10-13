<?php

namespace Formwork\Tests;

use Formwork\Cms\App;

final class TestApplication
{
    /**
     * Load the application of the tests, created by the bootstrap over a temporary installation
     */
    public static function boot(): void
    {
        App::instance()->load();
    }
}
