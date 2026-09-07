<?php

namespace Formwork\Services;

use Formwork\Utils\Arr;
use LogicException;

class ServiceDefinition
{
    /**
     * Service parameters
     *
     * @var array<string, mixed>
     */
    protected array $parameters = [];

    /**
     * Optional loader class name
     */
    protected ?string $loader = null;

    /**
     * Whether the service is lazy
     */
    protected bool $lazy = true;

    public function __construct(
        protected string $name,
        protected ?object $object,
        protected Container $container,
    ) {}

    /**
     * Get service name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get service object
     */
    public function getObject(): ?object
    {
        return $this->object;
    }

    /**
     * Set a parameter for the service
     */
    public function parameter(string $name, mixed $value): self
    {
        Arr::set($this->parameters, $name, $value);
        return $this;
    }

    /**
     * Get service parameters
     *
     * @return array<string, mixed>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Set a loader for the service
     */
    public function loader(string $className): self
    {
        if (isset($this->object)) {
            throw new LogicException('Instantiated object cannot have loaders');
        }
        $this->loader = $className;
        return $this;
    }

    /**
     * Get service loader
     */
    public function getLoader(): ?string
    {
        return $this->loader;
    }

    /**
     * Set service alias
     */
    public function alias(string $alias): self
    {
        $this->container->alias($alias, $this->name);
        return $this;
    }

    /**
     * Set service laziness
     */
    public function lazy(bool $lazy): self
    {
        $this->lazy = $lazy;

        if (
            $this->lazy === false
            && $this->container->has($this->name)
            && !$this->container->isResolved($this->name)
        ) {
            $this->container->resolve($this->name);
        }

        return $this;
    }
}
