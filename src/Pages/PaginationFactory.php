<?php

namespace Formwork\Pages;

use Formwork\Cms\App;
use Formwork\Router\Router;

final class PaginationFactory
{
    public function __construct(
        private App $app,
        private Router $router,
    ) {}

    /**
     * Create a new Pagination instance
     */
    public function make(PageCollection $pageCollection, int $length): Pagination
    {
        return new Pagination($pageCollection, $length, $this->app->site(), $this->router);
    }
}
