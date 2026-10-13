<?php

namespace Formwork\Tests\Unit\Controllers\Fixtures;

use Formwork\Controllers\PageController;
use Formwork\Services\Container;

/**
 * Container replacing the page controller used by `forward()` with a lightweight double
 */
final class ForwardingContainer extends Container
{
    public function __construct(private readonly bool $replacePageController = true) {}

    public function build(string $class, array $parameters = []): object
    {
        if ($class === PageController::class && $this->replacePageController) {
            return new ErrorPageController();
        }

        return parent::build($class, $parameters);
    }
}
