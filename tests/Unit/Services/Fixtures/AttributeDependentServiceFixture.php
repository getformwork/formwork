<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

use Formwork\Services\Attributes\Service;

final class AttributeDependentServiceFixture
{
    public function __construct(#[Service('selected')] public SimpleServiceFixture $service) {}
}
