<?php

namespace Formwork\Tests\Unit\Panel\Fixtures;

use Closure;
use Formwork\Files\FileUriGenerator;
use Formwork\Services\Container;

/**
 * Container of the application providing a given file URI generator, to generate the URIs of files outside of the content of the application
 */
final class UriGeneratorContainer extends Container
{
    public function __construct(
        private readonly Container $container,
        private readonly FileUriGenerator $fileUriGenerator,
    ) {}

    public function build(string $class, array $parameters = []): object
    {
        return $this->container->build($class, $parameters);
    }

    public function call(Closure $closure, array $parameters = []): mixed
    {
        return $this->container->call($closure, $parameters);
    }

    public function get(string $name): object
    {
        return $name === FileUriGenerator::class ? $this->fileUriGenerator : $this->container->get($name);
    }
}
