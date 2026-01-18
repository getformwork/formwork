<?php

namespace Formwork\View;

use Closure;
use Formwork\Cms\App;

final class ViewFactory
{
    /**
     * @param array<string, Closure> $methods
     * @param array<string, string>  $resolutionPaths
     */
    public function __construct(
        private array $methods,
        private array $resolutionPaths,
        private App $app,
    ) {}

    /**
     * Create a new View instance
     *
     * @param array<string, mixed>      $vars
     * @param array<string>|string|null $resolutionPaths
     * @param array<string, Closure>    $methods
     */
    public function make(string $name, array $vars = [], array|string|null $resolutionPaths = null, array $methods = []): View
    {
        $vars = [...$this->defaults(), ...$vars];
        $methods = [...$this->methods, ...$methods];
        return new View($name, $vars, $resolutionPaths ?? $this->resolutionPaths, $methods);
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
     * Add a view resolution path
     *
     * @param array<string, string> $resolutionPaths
     *
     * @since 2.3.0
     */
    public function setResolutionPaths(array $resolutionPaths): void
    {
        $this->resolutionPaths = [...$this->resolutionPaths, ...$resolutionPaths];
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
