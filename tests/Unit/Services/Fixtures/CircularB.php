<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

final class CircularB
{
    public function __construct(CircularA $dependency) {}
}
