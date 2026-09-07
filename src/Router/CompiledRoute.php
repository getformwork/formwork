<?php

namespace Formwork\Router;

class CompiledRoute
{
    /**
     * @param list<string> $params
     */
    public function __construct(
        protected string $path,
        protected string $regex,
        protected array $params,
    ) {}

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
