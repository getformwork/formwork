<?php

namespace Formwork\Router;

use Formwork\Data\Traits\DataGetter;
use Formwork\Data\Contracts\Arrayable;
use Formwork\Data\Traits\DataArrayable;

class RouteParams implements Arrayable
{
    use DataArrayable;
    use DataGetter;

    public function __construct(array $data)
    {
        $this->data = $data;
    }
}
