<?php

namespace Formwork\Pages\Templates;

use Formwork\Services\Container;

class TemplateFactory
{
    public function __construct(protected Container $container)
    {

    }

    public function make(string $name)
    {
        return $this->container->build(Template::class, compact('name'));
    }
}
