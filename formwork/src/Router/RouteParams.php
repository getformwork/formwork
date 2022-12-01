<?php

namespace Formwork\Router;

use Formwork\Data\Contracts\Arrayable;
use Formwork\Data\Traits\DataArrayable;
use Formwork\Data\Traits\DataGetter;

class RouteParams implements Arrayable
{
    use DataArrayable;
    use DataGetter;

    public function __construct(array $data)
    {
        $this->data = $data;
    }
}
