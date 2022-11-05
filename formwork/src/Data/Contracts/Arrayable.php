<?php

namespace Formwork\Data\Contracts;

interface Arrayable
{
    /**
     * Return an array containing data
     */
    public function toArray(): array;
}
