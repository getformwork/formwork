<?php

namespace Formwork\Fields\Translations;

use Formwork\Translations\Translation;
use Formwork\Utils\Arr;
use Formwork\Utils\Str;

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
            }
        } elseif (!is_string($value)) {
            return $value;
        }

        $interpolate = fn($value) => is_string($value) ? Str::interpolate($value, fn($key) => $this->translation->translate($key)) : $value;

        if (is_array($value)) {
            return Arr::map($value, $interpolate);
        }

        return $interpolate($value);
    }
}
