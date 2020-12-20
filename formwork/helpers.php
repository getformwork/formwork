<?php

return [
    'attr' => [
        'Formwork\\Utils\\HTML',
        'attributes'
    ],
    'escape' => [
        'Formwork\\Utils\\Str',
        'escape'
    ],
    'escapeAttr' => [
        'Formwork\\Utils\\Str',
        'escapeAttr'
    ],
    'removeHTML' => [
        'Formwork\\Utils\\Str',
        'removeHTML'
    ],
    'slug' => [
        'Formwork\\Utils\\Str',
        'slug'
    ],
    'countWords' => [
        'Formwork\\Utils\\Text',
        'countWords'
    ],
    'truncate' => [
        'Formwork\\Utils\\Text',
        'truncate'
    ],
    'truncateWords' => [
        'Formwork\\Utils\\Text',
        'truncateWords'
    ],
    'readingTime' => [
        'Formwork\\Utils\\Text',
        'readingTime'
    ],
    'markdown' => static function (string $markdown): string {
        $currentPage = \Formwork\Formwork::instance()->site()->currentPage();
        return \Formwork\Parsers\Markdown::parse(
            $markdown,
            ['baseRoute' => $currentPage ? $currentPage->route() : '/']
        );
    },
    'date' => static function (int $timestamp, string $format = null): string {
        return \Formwork\Utils\Date::formatTimestamp(
            $timestamp,
            $format ?? \Formwork\Formwork::instance()->config()->get('date.format')
        );
    },
    'datetime' => static function (int $timestamp): string {
        return \Formwork\Utils\Date::formatTimestamp(
            $timestamp,
            \Formwork\Formwork::instance()->config()->get('date.format') . ' ' . \Formwork\Formwork::instance()->config()->get('date.hour_format')
        );
    },
    'translate' => static function (string $key, ...$arguments) {
        return \Formwork\Formwork::instance()->translations()->getCurrent()->translate($key, ...$arguments);
    }
];
