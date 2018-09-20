<?php

namespace Formwork\Admin\Utils;

use LogicException;

class Language
{
    protected static $language = 'en';

    protected static $data;

    public static function load($language, $data)
    {
        static::$language = $language;
        static::$data = $data;
    }

    public static function language()
    {
        return static::$language;
    }

    public static function get($key, ...$arguments)
    {
        if (!isset(static::$data[$key])) {
            throw new LogicException('Invalid string');
        }
        if (!empty($arguments)) {
            return sprintf(static::$data[$key], ...$arguments);
        }
        return static::$data[$key];
    }
}
