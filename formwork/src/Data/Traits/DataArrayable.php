<?php

namespace Formwork\Data\Traits;

trait DataArrayable
{
    /**
     * @var array<mixed>
     */
    protected array $data = [];

    public function toArray(): array
    {
        return $this->data;
    }
}
