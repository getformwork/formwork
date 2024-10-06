<?php

namespace Formwork\Templates;

use Formwork\Schemes\Schemes;
use Formwork\Services\Container;

class TemplateFactory
{
    public function __construct(protected Container $container, protected Schemes $schemes)
    {
    }

    public function make(string $name): Template
    {
        return $this->container->build(Template::class, [
            'name'   => $name,
            'scheme' => $this->schemes->get('pages.' . $name),
        ]);
    }
}
