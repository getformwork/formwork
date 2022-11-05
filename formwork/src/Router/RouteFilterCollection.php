<?php

namespace Formwork\Router;

use Formwork\Data\AssociativeCollection;

class RouteFilterCollection extends AssociativeCollection
{
    /**
     * Add filter to collection
     */
    public function add(RouteFilter $filter): RouteFilter
    {
        return $this->data[$filter->getName()] = $filter;
    }
}
