<?php

namespace Formwork\Router;

use InvalidArgumentException;

class Route
{
    /**
     * Default route methods
     */
    protected const DEFAULT_METHODS = ['GET'];

    /**
     * Default route types
     */
    protected const DEFAULT_TYPES = ['HTTP'];

    /**
     * Route action
     *
     * @var callable|string
     */
    protected $action;

    /**
     * Route methods
     *
     * @var list<string>
     */
    protected array $methods = self::DEFAULT_METHODS;

    /**
     * Route types
     *
     * @var list<string>
     */
    protected array $types = self::DEFAULT_TYPES;

    /**
     * Route prefix
     */
    protected string $prefix = '';

    public function __construct(protected string $name, protected string $path)
    {
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
        if (!array_is_list($methods)) {
            throw new InvalidArgumentException(sprintf('%s() accepts only unnamed arguments', __METHOD__));
        }
        $this->methods = $methods;
        return $this;
    }

    /**
     * Get route methods
     *
     * @return list<string>
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
        if (!array_is_list($types)) {
            throw new InvalidArgumentException(sprintf('%s() accepts only unnamed arguments', __METHOD__));
        }
        $this->types = $types;
        return $this;
    }

    /**
     * Get route types
     *
     * @return list<string>
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
