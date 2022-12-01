<?php

namespace Formwork\Traits;

use BadMethodCallException;

trait Methods
{
    /**
     * Array of methods
     */
    protected array $methods = [];

    public function __call(string $name, array $arguments)
    {
        if ($this->hasMethod($name)) {
            return $this->callMethod($name, $arguments);
        }
        throw new BadMethodCallException(sprintf('Call to undefined method %s::%s()', static::class, $name));
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
     */
    protected function callMethod(string $method, array $arguments)
    {
        return $this->methods[$method](...$arguments);
    }
}
