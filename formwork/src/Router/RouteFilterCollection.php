<?php

namespace Formwork\Router;

use Formwork\Data\AbstractCollection;

class RouteFilterCollection extends AbstractCollection
{
    protected bool $associative = true;

    protected ?string $dataType = RouteFilter::class;

    protected bool $mutable = true;

    /**
     * Add filter to collection
     *
     * @param RouteFilter $filter
     */
    public function add($filter): void
    {
        $this->set($filter->getName(), $filter);
    }
}
