<?php

namespace Formwork\Data\Contracts;

interface ArraySerializable extends Arrayable
{
    /**
     * Create instance from array
     *
     * @param array<mixed> $data
     */
    public static function fromArray(array $data): self;
}
