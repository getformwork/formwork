<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

use Formwork\Services\Attributes\Service;

final class OptionalAttributeDependentService
{
    public function __construct(#[Service('selected')] public ?SimpleService $service = null) {}
}
