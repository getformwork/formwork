<?php

namespace Formwork\Schemes;

use Formwork\Data\Contracts\Arrayable;
use Formwork\Data\Traits\DataArrayable;
use Formwork\Data\Traits\DataGetter;

class SchemeOptions implements Arrayable
{
    use DataArrayable;
    use DataGetter;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }
}
