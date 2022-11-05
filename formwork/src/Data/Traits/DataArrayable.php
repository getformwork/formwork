<?php

namespace Formwork\Data\Traits;

trait DataArrayable // implements Formwork\Data\Contracts\Arrayable
{
    protected array $data = [];

    public function toArray(): array
    {
        return $this->data;
    }
}
