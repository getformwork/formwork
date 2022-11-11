<?php

namespace Formwork\Router;

use Formwork\Data\Traits\DataGetter;

class RouteParams
{
    use DataGetter;

    public function __construct(array $data)
    {
        $this->data = $data;
    }
}
