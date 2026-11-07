<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

use Formwork\Services\Attributes\Service;

final class OptionalAttributeDependentServiceFixture
{
    public function __construct(#[Service('selected')] public ?SimpleServiceFixture $service = null) {}
}
