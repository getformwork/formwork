<?php

namespace Formwork\Admin\Fields;

use Formwork\Admin\Admin;

class Translator
{
    /**
     * Language string interpolation regex
     *
     * @var string
     */
    protected const INTERPOLATION_REGEX = '/^{{([\-._a-z]+)}}$/i';

    /**
     * Fields not to translate
     *
     * @var array
     */
    protected const IGNORED_FIELDS = ['name', 'type', 'import', 'fields'];

    /**
     * Keys of which array value has to be ignored
     *
     * @var array
     */
    protected const IGNORED_ARRAY_KEYS = ['value', 'options'];

    /**
     * Translate a field
     */
    public static function translate(Field $field)
    {
        $language = Admin::instance()->translation()->code();
        foreach ($field->toArray() as $key => $value) {
            if (static::isTranslatable($key, $field)) {
                if (is_array($value)) {
                    if (isset($value[$language])) {
                        $value = $value[$language];
                    } elseif (!in_array($key, self::IGNORED_ARRAY_KEYS, true)) {
                        $value = array_shift($value);
                    }
                } elseif (!is_string($value)) {
                    continue;
                }
                $field->set($key, static::interpolate($value));
            }
        }
    }

    /**
     * Return whether a field key is translatable
     *
     * @return bool
     */
    protected static function isTranslatable(string $key, Field $field)
    {
        if (in_array($key, self::IGNORED_FIELDS, true)) {
            return false;
        }
        $translate = $field->get('translate', true);
        if (is_array($translate)) {
            return in_array($key, $translate, true);
        }
        return $translate;
    }

    /**
     * Interpolate a string
     *
     * @param array|string $value
     *
     * @return array|string
     */
    protected static function interpolate($value)
    {
        if (is_array($value)) {
            return array_map([static::class, 'interpolate'], $value);
        }
        if (is_string($value) && (bool) preg_match(self::INTERPOLATION_REGEX, $value, $matches)) {
            return Admin::instance()->label($matches[1]);
        }
        return $value;
    }
}
