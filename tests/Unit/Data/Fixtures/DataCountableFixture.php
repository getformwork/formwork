<?php

namespace Formwork\Tests\Unit\Data\Fixtures;

use Countable;
use Formwork\Data\Traits\DataCountable;

class DataCountableFixture implements Countable
{
    use DataCountable;

    /**
     * @param array<string, string> $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }
}
