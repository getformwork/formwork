<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

final class OptionalIntegerService
{
    public function __construct(public int $value = 7) {}
}
