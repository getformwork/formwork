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
    protected static $helpers = array(
        'escape'     => array(Str::class, 'escape'),
        'removeHTML' => array(Str::class, 'removeHTML')
    );

    /**
     * Return whether a template helper is defined
     *
     * @param string $name
     *
     * @return bool
     */
    public static function has($name)
    {
        return isset(static::$helpers[$name]);
    }

    /**
     * Add a template helper
     *
     * @param string   $name
     * @param callable $helper
     */
    public static function add($name, $helper)
    {
        if (static::has($name)) {
            throw new RuntimeException('Template helper ' . $name . ' is already defined');
        }
        if (method_exists(Template::class, $name)) {
            throw new LogicException('Template helpers must not conflict with ' . Template::class . ' methods. Invalid name ' . $name);
        }
        if (!is_callable($helper)) {
            throw new LogicException('Template helper ' . $name . ' must be callable');
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
    public static function get($name)
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
    public static function remove($name)
    {
        unset(static::$helpers[$name]);
    }
}
