<?php

namespace Formwork\View;

use Closure;
use Formwork\App;
use Formwork\Config;

class ViewFactory
{
    /**
     * @param array<string, Closure> $methods
     */
    public function __construct(protected array $methods, protected App $app, protected Config $config)
    {

    }

    /**
     * @param array<string, mixed>   $vars
     * @param array<string, Closure> $methods
     */
    public function make(string $name, array $vars = [], ?string $path = null, array $methods = []): View
    {
        $vars = [...$this->defaults(), ...$vars];
        $path ??= $this->config->get('system.views.paths.system');
        $methods = [...$this->methods, ...$methods];
        return new View($name, $vars, $path, $methods);
    }

    /**
     * @param array<string, Closure>|Closure $methods
     */
    public function setMethods(Closure|array $methods): void
    {
        $this->methods = [...$this->methods, ...(array) $methods];
    }

    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return [
            'app' => $this->app,
        ];
    }
}
