<?php

return [
    'date' => static function (int $timestamp): string {
        return \Formwork\Admin\Utils\DateFormats::formatTimestamp(
            $timestamp,
            \Formwork\Core\Formwork::instance()->option('date.format')
        );
    },
    'datetime' => static function (int $timestamp): string {
        return \Formwork\Admin\Utils\DateFormats::formatTimestamp(
            $timestamp,
            \Formwork\Core\Formwork::instance()->option('date.format') . ' ' . \Formwork\Core\Formwork::instance()->option('date.hour_format')
        );
    }
];
