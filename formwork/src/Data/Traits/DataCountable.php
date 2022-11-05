<?php

namespace Formwork\Data\Traits;

trait DataCountable // implements \Countable
{
    protected array $data = [];

    public function count()
    {
        return count($this->data);
    }
}
