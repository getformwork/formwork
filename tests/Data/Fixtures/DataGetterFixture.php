<?php

namespace Formwork\Tests\Data\Fixtures;

use Formwork\Data\Traits\DataGetter;

class DataGetterFixture
{
    use DataGetter;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }
}
