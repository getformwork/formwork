<?php

namespace Formwork\Http;

use Countable;
use Formwork\Data\Contracts\Arrayable;
use Formwork\Data\Traits\DataArrayable;
use Formwork\Data\Traits\DataCountableIterator;
use Formwork\Data\Traits\DataMultipleGetter;
use Iterator;

class RequestData implements Arrayable, Countable, Iterator
{
    use DataArrayable;
    use DataCountableIterator;
    use DataMultipleGetter;

    /**
     * Create a new instance
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
