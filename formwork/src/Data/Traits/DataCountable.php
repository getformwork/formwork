<?php

namespace Formwork\Data\Traits;

trait DataCountable // implements \Countable
{
    protected array $data = [];

    public function count(): int
    {
        return count($this->data);
    }
}
