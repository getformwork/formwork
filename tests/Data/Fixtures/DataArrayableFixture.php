<?php

namespace Formwork\Tests\Data\Fixtures;

use Formwork\Data\Contracts\Arrayable;
use Formwork\Data\Traits\DataArrayable;

class DataArrayableFixture implements Arrayable
{
    use DataArrayable;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }
}
