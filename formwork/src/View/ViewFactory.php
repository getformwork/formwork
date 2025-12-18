<?php

namespace Formwork\View;

use Closure;
use Formwork\Cms\App;
use Formwork\Config\Config;

final class ViewFactory
{
    /**
     * @param array<string, Closure> $methods
     */
    public function __construct(
        private array $methods,
        private App $app,
        private Config $config,
    ) {}

    /**
     * Create a new View instance
     *
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
     * Set view methods
     *
     * @param array<string, Closure>|Closure $methods
     */
    public function setMethods(Closure|array $methods): void
    {
        $this->methods = [...$this->methods, ...(array) $methods];
    }

    /**
     * Get default view variables
     *
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return [
            'app' => $this->app,
        ];
    }
}
