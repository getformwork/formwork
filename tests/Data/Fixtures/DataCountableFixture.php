<?php

namespace Formwork\Tests\Data\Fixtures;

use Countable;
use Formwork\Data\Traits\DataCountable;

class DataCountableFixture implements Countable
{
    use DataCountable;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }
}
