<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

final class CircularAFixture
{
    public function __construct(CircularBFixture $dependency) {}
}
