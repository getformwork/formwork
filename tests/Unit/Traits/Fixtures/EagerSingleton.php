<?php

namespace Formwork\Tests\Unit\Traits\Fixtures;

use Formwork\Traits\SingletonClass;

/**
 * Singleton which registers itself from the constructor
 */
final class EagerSingleton
{
    use SingletonClass;

    public function __construct()
    {
        $this->initializeSingleton();
    }
}
