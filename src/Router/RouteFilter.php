<?php

namespace Formwork\Router;

use InvalidArgumentException;

class RouteFilter
{
    /**
     * Default filter methods
     *
     * @var list<value-of<Router::REQUEST_METHODS>>
     */
    protected const array DEFAULT_METHODS = ['GET'];

    /**
     * Default filter types
     *
     * @var list<value-of<Router::REQUEST_TYPES>>
     */
    protected const array DEFAULT_TYPES = ['HTTP'];

    /**
     * Filter action
     *
     * @var callable|string
     */
    protected $action;

    /**
     * Filter methods
     *
     * @var list<string>
     */
    protected array $methods = self::DEFAULT_METHODS;

    /**
     * Filter types
     *
     * @var list<string>
     */
    protected array $types = self::DEFAULT_TYPES;

    /**
     * Filter prefix
     */
    protected string $prefix = '';

    /**
     * @param callable|string $action
     */
    public function __construct(
        protected string $name,
        $action,
    ) {
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
        if (!array_is_list($methods)) {
            throw new InvalidArgumentException(sprintf('%s() accepts only unnamed arguments', __METHOD__));
        }
        $this->methods = $methods;
        return $this;
    }

    /**
     * Get filter methods
     *
     * @return list<string>
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
        if (!array_is_list($types)) {
            throw new InvalidArgumentException(sprintf('%s() accepts only unnamed arguments', __METHOD__));
        }
        $this->types = $types;
        return $this;
    }

    /**
     * Get filter types
     *
     * @return list<string>
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
