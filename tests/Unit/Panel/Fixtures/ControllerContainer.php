<?php

namespace Formwork\Tests\Unit\Panel\Fixtures;

use Formwork\Panel\Controllers\ErrorsController;
use Formwork\Services\Container;

/**
 * Container replacing the panel errors controller (used by `forward()`) with a lightweight double
 */
final class ControllerContainer extends Container
{
    /**
     * Whether the real errors controller is built, to test it
     */
    public bool $realErrorsController = false;

    public function build(string $class, array $parameters = []): object
    {
        if ($class === ErrorsController::class && !$this->realErrorsController) {
            return new ForbiddenErrorsController();
        }

        return parent::build($class, $parameters);
    }
}
