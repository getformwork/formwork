<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

final class DependentOnContract
{
    public function __construct(public DependencyContract $dependency) {}
}
