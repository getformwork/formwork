<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

final class OptionalStringServiceFixture
{
    public function __construct(public string $value = 'default') {}
}
