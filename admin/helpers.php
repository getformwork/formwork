<?php

return [
    'date' => static function (int $timestamp): string {
        return \Formwork\Admin\Utils\DateFormats::formatTimestamp(
            $timestamp,
            \Formwork\Core\Formwork::instance()->config()->get('date.format')
        );
    },
    'datetime' => static function (int $timestamp): string {
        return \Formwork\Admin\Utils\DateFormats::formatTimestamp(
            $timestamp,
            \Formwork\Core\Formwork::instance()->config()->get('date.format') . ' ' . \Formwork\Core\Formwork::instance()->config()->get('date.hour_format')
        );
    }
];
