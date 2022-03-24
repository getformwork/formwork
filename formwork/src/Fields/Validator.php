<?php

namespace Formwork\Fields;

use Formwork\Data\Collection;
use Formwork\Data\DataGetter;
use Formwork\Fields\Exceptions\ValidationException;
use Formwork\Formwork;
use Formwork\Utils\Date;
use Formwork\Utils\Str;
use InvalidArgumentException;

class Validator
{
    /**
     * Field types ignored by the validator
     */
    public const IGNORED_FIELDS = ['column', 'header', 'row', 'rows'];

    /**
     * Values considered true when parsed as boolean
     */
    public const TRUTHY_VALUES = [true, 1, 'true', '1', 'on', 'yes'];

    /**
     * Values considered false when parsed as boolean
     */
    public const FALSY_VALUES = [false, 0, 'false', '0', 'off', 'no'];

    /**
     * Values considered null when parsed as such
     */
    public const EMPTY_VALUES = [null, '', []];

    /**
     * Date format used for date fields
     */
    public const DATE_FORMAT = 'Y-m-d H:i:s';

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
                throw new ValidationException(sprintf('Required field "%s" of type "%s" cannot be empty', $field->name(), $field->type()));
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

        throw new ValidationException(sprintf('Invalid value for field "%s" of type "%s"', $field->name(), $field->type()));
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

        try {
            $format = Formwork::instance()->config()->get('date.format') . ' ' . Formwork::instance()->config()->get('date.time_format');
            return date(self::DATE_FORMAT, Date::toTimestamp($value, $format));
        } catch (InvalidArgumentException $e) {
            throw new ValidationException(sprintf('Invalid value for field "%s" of type "%s":%s', $field->name(), $field->type(), Str::after($e->getMessage(), ':')));
        }
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
            throw new ValidationException(sprintf('Invalid value for field "%s" of type "%s"', $field->name(), $field->type()));
        }

        if ($field->has('min') && $value < $field->get('min')) {
            throw new ValidationException(sprintf('The value of field "%s" of type "%s" must be greater than or equal to %d', $field->name(), $field->type(), $field->get('min')));
        }

        if ($field->has('max') && $value > $field->get('max')) {
            throw new ValidationException(sprintf('The value of field "%s" of type "%s" must be less than or equal to %d', $field->name(), $field->type(), $field->get('max')));
        }

        if ($field->has('step') && ($value - $field->get('min', 0)) % $field->get('step') !== 0) {
            throw new ValidationException(sprintf('The value of field "%s" of type "%s" does not conform to the step value %d', $field->name(), $field->value(), $field->get('step')));
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
            throw new ValidationException(sprintf('Invalid value for field "%s" of type "%s"', $field->name(), $field->type()));
        }

        if ($field->has('pattern')) {
            $value = array_filter($value, static fn ($item): bool => static::regex($item, $field->get('pattern')));
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
            throw new ValidationException(sprintf('Invalid value for field "%s" of type "%s"', $field->name(), $field->type()));
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
            throw new ValidationException(sprintf('Invalid value for field "%s" of type "%s"', $field->name(), $field->type()));
        }

        if ($field->has('min') && strlen($value) < $field->get('min')) {
            throw new ValidationException(sprintf('The minimum allowed length for field "%s" of type "%s" is %d', $field->name(), $field->value(), $field->get('min')));
        }

        if ($field->has('max') && strlen($value) > $field->get('max')) {
            throw new ValidationException(sprintf('The maximum allowed length for field "%s" of type "%s" is %d', $field->name(), $field->value(), $field->get('max')));
        }

        if ($field->has('pattern') && !static::regex($value, $field->get('pattern'))) {
            throw new ValidationException(sprintf('The value of field "%s" of type "%s" does not match the required pattern', $field->name(), $field->value()));
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
            throw new ValidationException(sprintf('The value of field "%s" of type "%s" is not a valid e-mail address', $field->name(), $field->value()));
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
            throw new ValidationException(sprintf('Invalid value for field "%s" of type "%s"', $field->name(), $field->type()));
        }

        return $value;
    }

    /**
     * Validate "duration" fields
     */
    public static function validateDuration($value, Field $field)
    {
        return static::validateNumber($value, $field);
    }

    /**
     * Cast a value to its correct type
     */
    protected static function parse($value)
    {
        if (is_numeric($value)) {
            // This reliably casts numeric values to int or float
            return $value + 0;
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
