<?php

namespace Formwork\Tests\Unit\Utils\Fixtures;

use Formwork\Data\Traits\DataIterator;
use Iterator;

final class TraversableFixture implements Iterator
{
    use DataIterator;

    public function __construct(array $data)
    {
        $this->data = $data;
    }
}
