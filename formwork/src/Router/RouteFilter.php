<?php

namespace Formwork\Router;

class RouteFilter
{
    /**
     * Default filter methods
     *
     * @var array
     */
    protected const DEFAULT_METHODS = ['GET'];

    /**
     * Default filter types
     *
     * @var array
     */
    protected const DEFAULT_TYPES = ['HTTP'];

    /**
     * Filter name
     */
    protected string $name;

    /**
     * Filter action
     *
     * @var callable|string
     */
    protected $action;

    /**
     * Filter methods
     */
    protected array $methods = self::DEFAULT_METHODS;

    /**
     * Filter types
     */
    protected array $types = self::DEFAULT_TYPES;

    /**
     * Filter prefix
     */
    protected string $prefix = '';

    /**
     * @param callable|string $action
     */
    public function __construct(string $name, $action)
    {
        $this->name = $name;
        $this->action = $action;
    }

    /**
     * Get filter name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get filter action
     *
     * @return callable|string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set filter methods
     */
    public function methods(string ...$methods): self
    {
        $this->methods = $methods;
        return $this;
    }

    /**
     * Get filter methods
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Set filter types
     */
    public function types(string ...$types): self
    {
        $this->types = $types;
        return $this;
    }

    /**
     * Get filter types
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * Set filter prefix
     */
    public function prefix(string $prefix): self
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * Get filter prefix
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }
}
