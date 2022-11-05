<?php

namespace Formwork\Data;

use Formwork\Data\Contracts\Arrayable;
use Formwork\Data\Traits\DataArrayable;
use Formwork\Data\Traits\DataMultipleGetter;

class DataGetter implements Arrayable
{
    use DataArrayable;
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
        return empty($this->data);
    }

    /**
     * Create an instance from another getter
     */
    public static function fromGetter(DataGetter $getter): self
    {
        return new static($getter->data);
    }

    public function __debugInfo(): array
    {
        return $this->toArray();
    }
}
