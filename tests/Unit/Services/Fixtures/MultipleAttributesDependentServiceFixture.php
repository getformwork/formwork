<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

use Formwork\Services\Attributes\Service;

final class MultipleAttributesDependentServiceFixture
{
    // @phpstan-ignore attribute.nonRepeatable
    public function __construct(#[Service('first')] #[Service('second')] public SimpleServiceFixture $service) {}
}
