<?php

namespace Formwork\Admin\Fields;

use Formwork\Core\Formwork;
use Formwork\Data\DataGetter;
use DateTime;

class Validator
{
    protected static $ignore = array('column', 'header', 'row', 'rows');

    public static function validate(Fields $fields, DataGetter $data)
    {
        foreach ($fields as $field) {
            if ($field->has('fields')) {
                $field->get('fields')->validate($data);
            }
            if (in_array($field->type(), static::$ignore)) {
                continue;
            }
            $method = 'validate' . ucfirst(strtolower($field->type()));
            if (method_exists(__CLASS__, $method)) {
                $value = static::$method($data->get($field->name()), $field);
            } else {
                $value = $data->get($field->name(), $field->default());
            }
            $field->set('value', $value);
        }
    }

    public static function validateCheckbox($value)
    {
        return !empty($value);
    }

    public static function validateTogglegroup($value)
    {
        if ($value === '0' || $value === 'false' || $value === '') {
            return false;
        }
        if ($value === '1' || $value === 'true') {
            return true;
        }
        return static::parse($value);
    }

    public static function validateDate($value)
    {
        if (!empty($value)) {
            $format = Formwork::instance()->option('date.format');
            $date = date_create_from_format($format, $value);
            if ($date instanceof DateTime) {
                return date_format($date, 'Y-m-d');
            }
        }
        return $value;
    }

    public static function validateNumber($value, &$field)
    {
        $number = static::parse($value);
        if (!is_null($value)) {
            if ($field->has('min')) {
                $number = max($number, (int) $field->get('min'));
            }
            if ($field->has('max')) {
                $number = min($number, (int) $field->get('max'));
            }
        }
        return $number;
    }

    public static function validateSelect($value)
    {
        return static::parse($value);
    }

    public static function validateTags($value, &$field)
    {
        $tags = is_array($value) ? $value : explode(', ', $value);
        if ($field->has('pattern')) {
            $pattern = $field->get('pattern');
            $tags = array_filter($tags, function ($item) use ($pattern) {
                return static::regex($item, $pattern);
            });
        }
        return $tags;
    }

    private static function parse($value)
    {
        if (is_numeric($value)) {
            if ($value == (int) $value) {
                return (int) $value;
            }
            if ($value == (float) $value) {
                return (float) $value;
            }
        }
        return $value;
    }

    private static function regex($value, $regex)
    {
        return (bool) @preg_match('/' . $regex . '/', $value);
    }
}
