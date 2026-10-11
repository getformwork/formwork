<?php

namespace Formwork\Fields\Translations;

use Formwork\Translations\Translation;
use Formwork\Utils\Arr;
use Formwork\Utils\Str;
use InvalidArgumentException;

trait Translations
{
    protected Translation $translation;

    /**
     * Set translation
     */
    public function setTranslation(Translation $translation): void
    {
        $this->translation = $translation;
    }

    /**
     * Translate value
     */
    protected function translate(mixed $value): mixed
    {
        if (!isset($this->translation)) {
            return $value;
        }

        $language = $this->translation->code();

        if (is_array($value)) {
            if (isset($value[$language])) {
                $value = $value[$language];
            } elseif (count($value) === 1) {
                // Return the only value available as fallback
                $value = reset($value);
            }
        } elseif (!is_string($value)) {
            return $value;
        }

        // Safe interpolation (returns the placeholder if translation is not found)
        $interpolate = fn($value) => is_string($value)
            ? Str::interpolate($value, function (string $key) {
                try {
                    return $this->translation->translate($key);
                } catch (InvalidArgumentException) {
                    return '{{' . $key . '}}';
                }
            })
            : $value;

        if (is_array($value)) {
            return Arr::map($value, $interpolate);
        }

        return $interpolate($value);
    }
}
