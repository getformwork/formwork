<?php

namespace Formwork\Tests\Unit\Data\Fixtures;

use Formwork\Data\Contracts\Arrayable;
use Formwork\Data\Traits\DataArrayable;

class DataArrayableFixture implements Arrayable
{
    use DataArrayable;

    /**
     * @param array<string, string> $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }
}
