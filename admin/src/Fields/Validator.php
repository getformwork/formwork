<?php

namespace Formwork\Admin\Fields;

use Formwork\Core\Formwork;
use Formwork\Data\Collection;
use Formwork\Data\DataGetter;
use DateTime;

class Validator
{
    /**
     * Field types ignored by the validator
     *
     * @var array
     */
    public const IGNORED_FIELDS = ['column', 'header', 'row', 'rows'];

    /**
     * Values considered true when parsed as boolean
     *
     * @var array
     */
    public const TRUTHY_VALUES = [true, 1, 'true', '1', 'on', 'yes'];

    /**
     * Values considered false when parsed as boolean
     *
     * @var array
     */
    public const FALSY_VALUES = [false, 0, 'false', '0', 'off', 'no'];

    /**
     * Values considered null when parsed as such
     *
     * @var array
     */
    public const EMPTY_VALUES = [null, '', []];

    /**
     * Validate all Fields against given data
     */
    public static function validate(Fields $fields, DataGetter $data): void
    {
        foreach ($fields as $field) {
            if ($field->has('fields')) {
                $field->get('fields')->validate($data);
            }

            if (in_array($field->type(), self::IGNORED_FIELDS, true)) {
                continue;
            }

            $value = $data->get($field->name());

            if ($field->isRequired() && in_array($value, self::EMPTY_VALUES, true)) {
                throw new ValidationException('Required field "' . $field->name() . '" of type "' . $field->type() . '" cannot be empty');
            }

            $method = 'validate' . ucfirst(strtolower($field->type()));

            if (method_exists(static::class, $method)) {
                $value = static::$method($value, $field);
            }

            $field->set('value', $value);
        }
    }

    /**
     * Validate "checkbox" fields
     */
    public static function validateCheckbox($value, Field $field): bool
    {
        if (in_array($value, self::TRUTHY_VALUES, true)) {
            return true;
        }

        if (in_array($value, self::FALSY_VALUES, true)) {
            return false;
        }

        if ($value === null) {
            return false;
        }

        throw new ValidationException('Invalid value for field "' . $field->name() . '" of type "' . $field->type() . '"');
    }

    /**
     * Validate "togglegroup" fields
     */
    public static function validateTogglegroup($value, Field $field)
    {
        if (in_array($value, self::TRUTHY_VALUES, true)) {
            return true;
        }

        if (in_array($value, self::FALSY_VALUES, true)) {
            return false;
        }

        return static::parse($value);
    }

    /**
     * Validate "date" fields
     */
    public static function validateDate($value, Field $field): ?string
    {
        if (in_array($value, self::EMPTY_VALUES, true)) {
            return null;
        }

        $format = Formwork::instance()->option('date.format');
        $date = date_create_from_format($format, $value) ?: date_create($value);

        if ($date instanceof DateTime) {
            return date_format($date, 'Y-m-d');
        }

        throw new ValidationException('Invalid date format for field "' . $field->name() . '" of type "' . $field->type() . '"');
    }

    /**
     * Validate "number" fields
     *
     * @return float|int
     */
    public static function validateNumber($value, Field $field)
    {
        $value = static::parse($value);

        if (!is_numeric($value)) {
            throw new ValidationException('Invalid value for field "' . $field->name() . '" of type "' . $field->type() . '"');
        }

        if ($field->has('min') && $value < $field->get('min')) {
            throw new ValidationException('The value of field "' . $field->name() . '" of type "' . $field->type() . '" must be greater than or equal to ' . $field->get('min'));
        }

        if ($field->has('max') && $value > $field->get('max')) {
            throw new ValidationException('The value of field "' . $field->name() . '" of type "' . $field->type() . '" must be less than or equal to ' . $field->get('max'));
        }

        if ($field->has('step') && ($value - $field->get('min', 0)) % $field->get('step') !== 0) {
            throw new ValidationException('The value of field "' . $field->name() . '" of type "' . $field->type() . '" does not conform to the step value ' . $field->get('step'));
        }

        return $value;
    }

    /**
     * Validate "range" fields
     *
     * @return float|int
     */
    public static function validateRange($value, Field $field)
    {
        return static::validateNumber($value, $field);
    }

    /**
     * Validate "select" fields
     */
    public static function validateSelect($value, Field $field)
    {
        return static::parse($value);
    }

    /**
     * Validate "tags" fields
     */
    public static function validateTags($value, Field $field): array
    {
        if (in_array($value, self::EMPTY_VALUES, true)) {
            return [];
        }

        if (is_string($value)) {
            $value = array_map('trim', explode(',', $value));
        }

        if (!is_array($value)) {
            throw new ValidationException('Invalid value for field "' . $field->name() . '" of type "' . $field->type() . '"');
        }

        if ($field->has('pattern')) {
            $value = array_filter($value, static function ($item) use ($field): bool {
                return static::regex($item, $field->get('pattern'));
            });
        }

        return array_values(array_filter($value));
    }

    /**
     * Validate "array" fields
     */
    public static function validateArray($value, Field $field): array
    {
        if (in_array($value, self::EMPTY_VALUES, true)) {
            return [];
        }

        if ($value instanceof Collection || $value instanceof DataGetter) {
            $value = $value->toArray();
        }

        if (!is_array($value)) {
            throw new ValidationException('Invalid value for field "' . $field->name() . '" of type "' . $field->type() . '"');
        }

        if ($field->get('associative')) {
            foreach (array_keys($value) as $key) {
                if (is_int($key)) {
                    unset($value[$key]);
                }
            }
        }

        return array_filter($value);
    }

    /**
     * Validate "text" fields
     */
    public static function validateText($value, Field $field): string
    {
        if (in_array($value, self::EMPTY_VALUES, true)) {
            return '';
        }

        if (!is_string($value) && !is_numeric($value)) {
            throw new ValidationException('Invalid value for field "' . $field->name() . '" of type "' . $field->type() . '"');
        }

        if ($field->has('min') && strlen($value) < $field->get('min')) {
            throw new ValidationException('The minimum allowed length for field "' . $field->name() . '" of type "' . $field->type() . '" is ' . $field->get('min'));
        }

        if ($field->has('max') && strlen($value) > $field->get('max')) {
            throw new ValidationException('The maximum allowed length for field "' . $field->name() . '" of type "' . $field->type() . '" is ' . $field->get('max'));
        }

        if ($field->has('pattern') && !static::regex($value, $field->get('pattern'))) {
            throw new ValidationException('The value of field "' . $field->name() . '" of type "' . $field->type() . '" does not match the required pattern');
        }

        return (string) $value;
    }

    /**
     * Validate "textarea" fields
     */
    public static function validateTextarea($value, Field $field): string
    {
        return static::validateText($value, $field);
    }

    /**
     * Validate "email" fields
     */
    public static function validateEmail($value, Field $field): string
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('The value of field "' . $field->name() . '" of type "' . $field->type() . '" is not a valid e-mail address');
        }

        return static::validateText($value, $field);
    }

    /**
     * Validate "password" fields
     */
    public static function validatePassword($value, Field $field): string
    {
        return static::validateText($value, $field);
    }

    /**
     * Validate "image" fields
     */
    public static function validateImage($value, Field $field): ?string
    {
        if (in_array($value, self::EMPTY_VALUES, true)) {
            return null;
        }

        if (!is_string($value)) {
            throw new ValidationException('Invalid value for field "' . $field->name() . '" of type "' . $field->type() . '"');
        }

        return $value;
    }

    /**
     * Cast a value to its correct type
     */
    protected static function parse($value)
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

    /**
     * Return whether a value matches a regex
     */
    protected static function regex($value, string $regex): bool
    {
        return (bool) @preg_match('/' . $regex . '/', $value);
    }
}
