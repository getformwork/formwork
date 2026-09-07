<?php

namespace Formwork\Data\Contracts;

interface Arrayable
{
    /**
     * Return an array containing data
     *
     * @return array<mixed>
     */
    public function toArray(): array;
}
