<?php

namespace Formwork\Data;

use Countable;
use Formwork\Data\Contracts\Arrayable;
use Formwork\Utils\Arr;
use Formwork\Data\Traits\DataArrayable;
use Formwork\Data\Traits\DataCountableIterator;
use Iterator;

class Collection implements Arrayable, Countable, Iterator
{
    use DataArrayable;
    use DataCountableIterator;

    /**
     * Create a new Collection instance
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Return first collection item
     *
     * @return mixed|null
     */
    public function first()
    {
        return $this->data[0] ?? null;
    }

    /**
     * Return last collection item
     *
     * @return mixed|null
     */
    public function last()
    {
        return $this->data[$this->count() - 1] ?? null;
    }

    /**
     * Return a random item or a given default value if the collection is empty
     */
    public function random($default = null)
    {
        return Arr::random($this->data, $default);
    }

    /**
     * Return whether collection is empty
     */
    public function isEmpty(): bool
    {
        return empty($this->data);
    }
}
