<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

final class OptionalStringService
{
    public function __construct(public string $value = 'default') {}
}
