<?php

namespace Formwork\Traits;

use LogicException;

trait SingletonClass
{
    /**
     * Singleton instances
     *
     * @var array<class-string, static>
     */
    // Static properties in traits are shared across class hierarchy,
    // so keying by class name ensures each class has its own instance
    protected static array $instances = [];

    public function __clone()
    {
        throw new LogicException(sprintf('Cannot clone %s, the class is a singleton', static::class));
    }

    /**
     * Return self instance
     */
    public static function instance(): static
    {
        return static::$instances[static::class] ??= new static();
    }

    /**
     * Initialize singleton instance (to be used in the constructor)
     */
    protected function initializeSingleton(): void
    {
        if (isset(static::$instances[static::class])) {
            throw new LogicException(sprintf('Cannot create %s, the class is a singleton and cannot be instiantated again', static::class));
        }
        static::$instances[static::class] = $this;
    }
}
