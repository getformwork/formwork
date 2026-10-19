<?php

namespace Formwork\Tests\Unit\Data\Fixtures;

use Formwork\Data\Traits\DataGetter;

class DataGetterFixture
{
    use DataGetter;

    /**
     * @param array<string, string> $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }
}
