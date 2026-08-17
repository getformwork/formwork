<?php

namespace Formwork\Pages;

use Formwork\Cms\Site;
use Formwork\Data\Pagination as BasePagination;
use Formwork\Pages\Traits\PaginationUri;
use Formwork\Router\Router;

/**
 * @extends BasePagination<Page|Site>
 */
class Pagination extends BasePagination
{
    use PaginationUri;

    public function __construct(
        PageCollection $pageCollection,
        int $length,
        protected Site $site,
        protected Router $router,
    ) {
        parent::__construct($pageCollection, $length);
    }
}
