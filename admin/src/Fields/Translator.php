<?php

namespace Formwork\Admin\Fields;

use Formwork\Admin\Admin;

class Translator
{
    const INTERPOLATION_REGEX = '/^{{([\-._a-z]+)}}$/i';

    protected static $ignore = array('name', 'type', 'import', 'fields');

    public static function translate(Field $field)
    {
        $language = Admin::instance()->language()->code();
        foreach ($field->toArray() as $key => $value) {
            if (static::isTranslatable($key, $field)) {
                if (is_array($value)) {
                    if (isset($value[$language])) {
                        $value = $value[$language];
                    }
                } elseif (!is_string($value)) {
                    continue;
                }
                $field->set($key, static::interpolate($value));
            }
        }
    }

    protected static function isTranslatable($key, Field $field)
    {
        if (in_array($key, static::$ignore)) {
            return false;
        }
        $translate = $field->get('translate', true);
        if (is_array($translate)) {
            return in_array($key, $translate);
        }
        return $translate;
    }

    protected static function interpolate($value)
    {
        if (is_array($value)) {
            return array_map(array(static::class, 'interpolate'), $value);
        }
        if (is_string($value) && (bool) preg_match(self::INTERPOLATION_REGEX, $value, $matches)) {
            return Admin::instance()->language()->get($matches[1]);
        }
        return $value;
    }
}
