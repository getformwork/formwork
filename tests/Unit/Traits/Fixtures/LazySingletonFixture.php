<?php

namespace Formwork\Tests\Unit\Traits\Fixtures;

use Formwork\Traits\SingletonClass;

/**
 * Singleton created only through `instance()`
 */
final class LazySingletonFixture
{
    use SingletonClass;

    public int $counter = 0;
}
