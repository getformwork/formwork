<?php

namespace Formwork\Router;

use Formwork\Data\AssociativeCollection;

class RouteCollection extends AssociativeCollection
{
    /**
     * Add route to the collection
     */
    public function add(Route $route): Route
    {
        return $this->data[$route->getName()] = $route;
    }
}
