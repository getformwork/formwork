<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

final class OptionalDependencyService
{
    public function __construct(public ?SharedDependency $dependency = null) {}
}
