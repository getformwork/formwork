<?php

namespace Formwork\Router;

use Formwork\Data\AbstractCollection;

class RouteCollection extends AbstractCollection
{
    protected bool $associative = true;

    protected ?string $dataType = Route::class;

    protected bool $mutable = true;

    /**
     * Add route to the collection
     */
    public function add($route): void
    {
        $this->set($route->getName(), $route);
    }
}
