<?php

namespace Formwork\Tests\Unit\Panel\Fixtures;

use Formwork\Panel\Controllers\ErrorsController;
use Formwork\Services\Container;

/**
 * Container replacing the panel errors controller (used by `forward()`) with a lightweight double
 */
final class ControllerContainerFixture extends Container
{
    public function build(string $class, array $parameters = []): object
    {
        if ($class === ErrorsController::class) {
            return new ForbiddenErrorsControllerFixture();
        }

        return parent::build($class, $parameters);
    }
}
