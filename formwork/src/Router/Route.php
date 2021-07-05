<?php

namespace Formwork\Router;

class Route
{
    /**
     * Default route methods
     *
     * @var array
     */
    protected const DEFAULT_METHODS = ['GET'];

    /**
     * Default route types
     *
     * @var array
     */
    protected const DEFAULT_TYPES = ['HTTP'];

    /**
     * Route name
     */
    protected string $name;

    /**
     * Route path
     */
    protected string $path;

    /**
     * Route action
     *
     * @var callable|string
     */
    protected $action;

    /**
     * Route methods
     */
    protected array $methods = self::DEFAULT_METHODS;

    /**
     * Route types
     */
    protected array $types = self::DEFAULT_TYPES;

    /**
     * Route prefix
     */
    protected string $prefix = '';

    public function __construct(string $name, string $path)
    {
        $this->name = $name;
        $this->path = $path;
    }

    /**
     * Get route name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get route path
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Set route action
     *
     * @param callable|string $action
     */
    public function action($action): self
    {
        $this->action = $action;
        return $this;
    }

    /**
     * Get route action
     *
     * @return callable|string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set route methods
     */
    public function methods(string ...$methods): self
    {
        $this->methods = $methods;
        return $this;
    }

    /**
     * Get route methods
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Set route types
     */
    public function types(string ...$types): self
    {
        $this->types = $types;
        return $this;
    }

    /**
     * Get route types
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * Set route prefix
     */
    public function prefix(string $prefix): self
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * Get route prefix
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }
}
