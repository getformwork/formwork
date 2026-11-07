<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

final class CircularBFixture
{
    public function __construct(CircularAFixture $dependency) {}
}
