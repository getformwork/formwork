<?php

namespace Formwork\Data\Traits;

use Countable;

/**
 * @phpstan-require-implements Countable
 */
trait DataCountable
{
    protected array $data = [];

    public function count(): int
    {
        return count($this->data);
    }
}
