<?php

namespace Formwork\Traits;

use LogicException;

trait SingletonClass
{
    /**
     * Singleton instance
     */
    protected static self $instance;

    /**
     * Return self instance
     *
     * @return static
     */
    public static function instance(): self
    {
        if (isset(static::$instance)) {
            return static::$instance;
        }
        return static::$instance = new static();
    }

    /**
     * Initialize singleton instance (to be used in the constructor)
     */
    protected function initializeSingleton(): void
    {
        if (isset(static::$instance)) {
            throw new LogicException(sprintf('Cannot create %s, the class is a singleton and cannot be instiantated again', static::class));
        }
        static::$instance = $this;
    }

    public function __clone()
    {
        throw new LogicException(sprintf('Cannot clone %s, the class is a singleton', static::class));
    }
}
