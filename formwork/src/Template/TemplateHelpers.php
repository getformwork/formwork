<?php

namespace Formwork\Template;

use Formwork\Parsers\PHP;
use InvalidArgumentException;

class TemplateHelpers
{
    /**
     * Template helpers
     *
     * @var array
     */
    protected static $helpers = [];

    /**
     * Return whether a template helper is defined
     */
    public static function has(string $name): bool
    {
        static::initialize();
        return isset(static::$helpers[$name]);
    }

    /**
     * Add a template helper
     */
    public static function add(string $name, callable $helper): void
    {
        if (static::has($name)) {
            throw new InvalidArgumentException('Template helper ' . $name . ' is already defined');
        }
        if (method_exists(Template::class, $name)) {
            throw new InvalidArgumentException('Template helpers must not conflict with ' . Template::class . ' methods. Invalid name ' . $name);
        }
        static::$helpers[$name] = $helper;
    }

    /**
     * Get a template helper
     */
    public static function get(string $name): callable
    {
        if (!static::has($name)) {
            throw new InvalidArgumentException('Template helper ' . $name . ' is not defined');
        }
        return static::$helpers[$name];
    }

    /**
     * Remove a template helper
     */
    public static function remove(string $name): void
    {
        unset(static::$helpers[$name]);
    }

    /**
     * Initialize template helpers
     */
    protected static function initialize(): void
    {
        if (empty(static::$helpers)) {
            static::$helpers = PHP::parseFile(FORMWORK_PATH . 'helpers.php');
        }
    }
}
