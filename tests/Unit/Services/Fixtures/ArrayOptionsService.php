<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

final class ArrayOptionsService
{
    /**
     * @param array<string, mixed> $options
     */
    public function __construct(public array $options = []) {}
}
