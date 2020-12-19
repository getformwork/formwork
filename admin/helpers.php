<?php

return [
    'date' => static function (int $timestamp, string $format = null): string {
        return \Formwork\Utils\Date::formatTimestamp(
            $timestamp,
            $format ?? \Formwork\Core\Formwork::instance()->config()->get('date.format'),
            \Formwork\Admin\Admin::instance()->translation()->code()
        );
    },
    'datetime' => static function (int $timestamp): string {
        return \Formwork\Utils\Date::formatTimestamp(
            $timestamp,
            \Formwork\Core\Formwork::instance()->config()->get('date.format') . ' ' . \Formwork\Core\Formwork::instance()->config()->get('date.hour_format'),
            \Formwork\Admin\Admin::instance()->translation()->code()
        );
    },
    'translate' => static function (string $key, ...$arguments) {
        return \Formwork\Admin\Admin::instance()->translation()->translate($key, ...$arguments);
    }
];
