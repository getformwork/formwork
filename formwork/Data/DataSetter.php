<?php

namespace Formwork\Data;

class DataSetter extends DataGetter
{
    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }
}
