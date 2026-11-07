<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

final class OptionalIntegerServiceFixture
{
    public function __construct(public int $value = 7) {}
}
