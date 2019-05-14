<?php

namespace Formwork\Template;

use Closure;

class Renderer
{
    /**
     * Renderer instance
     *
     * @var Renderer
     */
    protected static $instance;

    /**
     * Load a script passing variables and binding to given instance and context
     *
     * @param string      $filename
     * @param array       $vars
     * @param object      $instance
     * @param string|null $context
     *
     * @return mixed
     */
    public static function load($filename, array $vars, $instance, $context = null)
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }
        $closure = static::$instance->getClosure($instance, $context);
        return $closure($filename, $vars);
    }

    /**
     * Return rendering closure bound to given instance and context
     *
     * @param object      $instance
     * @param string|null $context
     *
     * @return Closure
     */
    protected function getClosure($instance, $context = null)
    {
        return Closure::bind(function ($_filename, array $_vars) {
            extract($_vars);
            return include $_filename;
        }, $instance, $context);
    }
}
