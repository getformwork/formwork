<?php

namespace Formwork\Pages\Templates;

use Closure;
use Formwork\Services\Container;

class TemplateFactory
{
    /**
     * @param array<string, Closure> $methods
     */
    public function __construct(protected array $methods, protected Container $container)
    {
    }

    public function make(string $name): Template
    {
        $methods = $this->methods;
        return $this->container->build(Template::class, compact('name', 'methods'));
    }
}
