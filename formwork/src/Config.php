<?php

namespace Formwork;

use Formwork\Data\Contracts\Arrayable;
use Formwork\Data\Traits\DataArrayable;
use Formwork\Data\Traits\DataGetter;

class Config implements Arrayable
{
    use DataArrayable;
    use DataGetter;

    public function __construct(array $data)
    {
        $this->data = $data;
    }
}
