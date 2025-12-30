<?php

namespace Formwork\Tests\Data\Fixtures;

use Formwork\Data\Traits\DataIterator;
use Iterator;

class DataIteratorFixture implements Iterator
{
    use DataIterator;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }
}
