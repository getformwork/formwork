<?php

namespace Formwork\Tests\Unit\Utils\Fixtures;

use Formwork\Data\Traits\DataIterator;
use Iterator;

/**
 * @implements Iterator<array-key, ArrayableFixture|string>
 */
final class TraversableFixture implements Iterator
{
    use DataIterator;

    /**
     * @param array<array-key, ArrayableFixture|string> $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }
}
