<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

final class OptionalDependencyServiceFixture
{
    public function __construct(public ?SharedDependencyFixture $dependency = null) {}
}
