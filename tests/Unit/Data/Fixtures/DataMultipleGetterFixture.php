<?php

namespace Formwork\Tests\Unit\Data\Fixtures;

use Formwork\Data\Traits\DataMultipleGetter;

class DataMultipleGetterFixture
{
    use DataMultipleGetter;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }
}
