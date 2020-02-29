<?php

namespace Formwork\Template;

use Closure;

class Renderer
{
    /**
     * Load a script passing variables and binding to given instance and context
     */
    public static function load(string $filename, array $vars, $instance, ?string $context = null)
    {
        $closure = static::getClosure($instance, $context);
        return $closure($filename, $vars);
    }

    /**
     * Return rendering closure bound to given instance and context
     *
     * @return Closure
     */
    protected static function getClosure($instance, ?string $context = null)
    {
        return Closure::bind(function ($_filename, array $_vars) {
            extract($_vars);
            return include $_filename;
        }, $instance, $context);
    }
}
