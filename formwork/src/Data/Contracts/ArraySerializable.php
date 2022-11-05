<?php

namespace Formwork\Data\Contracts;

interface ArraySerializable extends Arrayable
{
    /**
     * Create instance from array
     */
    public static function fromArray(array $data): static;
}
