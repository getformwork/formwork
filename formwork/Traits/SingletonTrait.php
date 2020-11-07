<?php

namespace Formwork\Traits;

use LogicException;

trait SingletonTrait
{
    /**
     * Singleton instance
     *
     * @var self
     */
    protected static $instance;

    /**
     * Return self instance
     */
    public static function instance(): self
    {
        if (static::$instance !== null) {
            return static::$instance;
        }
        return static::$instance = new static();
    }

    /**
     * Initialize singleton instance (to be used in the constructor)
     */
    protected function initializeSingleton(): void
    {
        if (static::$instance !== null) {
            throw new LogicException('Cannot create ' . static::class . ', the class is a singleton and cannot be instiantated again');
        }
        static::$instance = $this;
    }

    public function __clone()
    {
        throw new LogicException('Cannot clone ' . static::class . ', the class is a singleton');
    }
}
