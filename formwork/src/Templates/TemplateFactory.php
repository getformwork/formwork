<?php

namespace Formwork\Templates;

use Formwork\Cms\App;
use Formwork\Config\Config;
use Formwork\Schemes\Schemes;
use Formwork\Security\CsrfToken;
use Formwork\Services\Container;

final class TemplateFactory
{
    public function __construct(
        private Container $container,
        private App $app,
        private Config $config,
        private Schemes $schemes,
    ) {}

    /**
     * Create a new Template instance
     */
    public function make(string $name): Template
    {
        $path = $this->config->get('system.templates.path');

        return $this->container->build(Template::class, [
            'name'    => $name,
            'path'    => $path,
            'methods' => [],
            'vars'    => [
                'router'    => $this->app->router(),
                'site'      => $this->app->site(),
                'csrfToken' => $this->app->getService(CsrfToken::class),
            ],
            'scheme' => $this->schemes->get("pages.{$name}"),
        ]);
    }
}
