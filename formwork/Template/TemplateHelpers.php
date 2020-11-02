<?php

namespace Formwork\Template;

use Formwork\Utils\HTML;
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
        'attr'       => [HTML::class, 'attributes'],
        'escape'     => [Str::class, 'escape'],
        'removeHTML' => [Str::class, 'removeHTML'],
        'slug'       => [Str::class, 'slug']
    ];

    /**
     * Return whether a template helper is defined
     */
    public static function has(string $name): bool
    {
        return isset(static::$helpers[$name]);
    }

    /**
     * Add a template helper
     */
    public static function add(string $name, callable $helper): void
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
     */
    public static function get(string $name): callable
    {
        if (!static::has($name)) {
            throw new RuntimeException('Template helper ' . $name . ' is not defined');
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
}
