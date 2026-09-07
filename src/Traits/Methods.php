<?php

namespace Formwork\Traits;

use BadMethodCallException;
use Closure;

trait Methods
{
    /**
     * Array of methods
     *
     * @var array<string, Closure>
     */
    protected array $methods = [];

    /**
     * @param list<mixed> $arguments
     */
    public function __call(string $name, array $arguments): mixed
    {
        if ($this->hasMethod($name)) {
            return $this->callMethod($name, $arguments);
        }
        throw new BadMethodCallException(sprintf('Call to undefined method %s::%s()', static::class, $name));
    }

    /**
     * Return the defined methods
     *
     * @return array<string, Closure>
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Return whether a method is defined in the `$method` property
     */
    public function hasMethod(string $method): bool
    {
        return isset($this->methods[$method]);
    }

    /**
     * Call a method defined in the `$method` property
     *
     * @param list<mixed> $arguments
     */
    protected function callMethod(string $method, array $arguments)
    {
        return $this->methods[$method](...$arguments);
    }
}
