<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

use Formwork\Services\Attributes\Service;

final class AttributeDependentService
{
    public function __construct(#[Service('selected')] public SimpleService $service) {}
}
