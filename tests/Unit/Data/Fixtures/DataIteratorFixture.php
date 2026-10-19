<?php

namespace Formwork\Tests\Unit\Data\Fixtures;

use Formwork\Data\Traits\DataIterator;
use Iterator;

/**
 * @implements Iterator<string, string>
 */
class DataIteratorFixture implements Iterator
{
    use DataIterator;

    /**
     * @param array<string, string> $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }
}
