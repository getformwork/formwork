<?php

namespace Formwork\View;

use Closure;
use Formwork\App;
use Formwork\Config;

class ViewFactory
{
    public function __construct(protected array $methods, protected App $app, protected Config $config)
    {

    }

    public function make(string $name, array $vars = [], ?string $path = null, array $methods = []): View
    {
        $vars = [...$this->defaults(), ...$vars];
        $path ??= $this->config->get('system.views.paths.system');
        $methods = [...$this->methods, ...$methods];
        return new View($name, $vars, $path, $methods);
    }

    public function setMethods(Closure|array $methods)
    {
        $this->methods = [...$this->methods, ...(array) $methods];
    }

    public function defaults(): array
    {
        return [
            'app' => $this->app,
        ];
    }
}
