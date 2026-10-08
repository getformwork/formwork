<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

final class CircularA
{
    public function __construct(CircularB $dependency) {}
}
