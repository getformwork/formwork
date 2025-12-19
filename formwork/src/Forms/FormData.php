<?php

namespace Formwork\Forms;

use Countable;
use Formwork\Data\Contracts\Arrayable;
use Formwork\Data\Traits\DataArrayable;
use Formwork\Data\Traits\DataCountableIterator;
use Formwork\Data\Traits\DataMultipleGetter;
use Iterator;

/**
 * @implements Iterator<array-key, mixed>
 */
class FormData implements Arrayable, Countable, Iterator
{
    use DataArrayable;
    use DataCountableIterator;
    use DataMultipleGetter;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Return whether data is present
     */
    public function isEmpty(): bool
    {
        return count($this) === 0;
    }
}
