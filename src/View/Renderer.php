<?php

namespace Formwork\View;

use Closure;
use Formwork\Traits\StaticClass;

class Renderer
{
    use StaticClass;

    /**
     * Load a script passing variables and binding to given instance and context
     *
     * @param array<string, mixed>       $vars
     * @param 'static'|class-string|null $context
     */
    public static function load(string $filename, array $vars, object $instance, ?string $context = null): mixed
    {
        $closure = static::getClosure($instance, $context);
        return $closure($filename, $vars);
    }

    /**
     * Return rendering closure bound to given instance and context
     *
     * @param 'static'|class-string|null $context
     */
    protected static function getClosure(object $instance, ?string $context = null): Closure
    {
        return Closure::bind(function (string $_filename, array $_vars) {
            extract($_vars);
            return include $_filename;
        }, $instance, $context);
    }
}
