<?php

namespace Formwork\Traits;

use LogicException;

trait StaticClass
{
    final public function __construct()
    {
        throw new LogicException(sprintf('Cannot construct %s, the class is static', static::class));
    }
}
