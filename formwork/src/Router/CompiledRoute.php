<?php

namespace Formwork\Router;

class CompiledRoute
{
    /**
     * Route path
     */
    protected string $path;

    /**
     * Compiled route regex
     */
    protected string $regex;

    /**
     * Route params
     *
     * @var list<string>
     */
    protected array $params;

    /**
     * @param list<string> $params
     */
    public function __construct(string $path, string $regex, array $params)
    {
        $this->path = $path;
        $this->regex = $regex;
        $this->params = $params;
    }

    /**
     * Get route path
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * Get compiled route regex
     */
    public function regex(): string
    {
        return $this->regex;
    }

    /**
     * Get route params
     *
     * @return list<string>
     */
    public function params(): array
    {
        return $this->params;
    }
}
