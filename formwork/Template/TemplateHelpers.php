<?php

namespace Formwork\Template;

use Formwork\Utils\Str;
use LogicException;
use RuntimeException;

class TemplateHelpers
{
    /**
     * Template helpers
     *
     * @var array
     */
    protected static $helpers = [
        'escape'     => [Str::class, 'escape'],
        'removeHTML' => [Str::class, 'removeHTML']
    ];

    /**
     * Return whether a template helper is defined
     *
     * @param string $name
     *
     * @return bool
     */
    public static function has(string $name)
    {
        return isset(static::$helpers[$name]);
    }

    /**
     * Add a template helper
     *
     * @param string   $name
     * @param callable $helper
     */
    public static function add(string $name, callable $helper)
    {
        if (static::has($name)) {
            throw new RuntimeException('Template helper ' . $name . ' is already defined');
        }
        if (method_exists(Template::class, $name)) {
            throw new LogicException('Template helpers must not conflict with ' . Template::class . ' methods. Invalid name ' . $name);
        }
        static::$helpers[$name] = $helper;
    }

    /**
     * Get a template helper
     *
     * @param string $name
     *
     * @return callable
     */
    public static function get(string $name)
    {
        if (!static::has($name)) {
            throw new RuntimeException('Template helper ' . $name . ' is not defined');
        }
        return static::$helpers[$name];
    }

    /**
     * Remove a template helper
     *
     * @param string $name
     */
    public static function remove(string $name)
    {
        unset(static::$helpers[$name]);
    }
}
