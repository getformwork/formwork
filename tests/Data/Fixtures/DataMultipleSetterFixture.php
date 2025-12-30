<?php

namespace Formwork\Tests\Data\Fixtures;

use Formwork\Data\Traits\DataMultipleSetter;

class DataMultipleSetterFixture
{
    use DataMultipleSetter;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function data(): array
    {
        return $this->data;
    }
}
