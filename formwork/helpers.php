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
        $currentPage = \Formwork\Core\Formwork::instance()->site()->currentPage();
        return \Formwork\Parsers\Markdown::parse(
            $markdown,
            ['baseRoute' => $currentPage ? $currentPage->route() : '/']
        );
    },
    'date' => static function (int $timestamp): string {
        return date(
            \Formwork\Core\Formwork::instance()->option('date.format'),
            $timestamp
        );
    },
    'datetime' => static function (int $timestamp): string {
        return date(
            \Formwork\Core\Formwork::instance()->option('date.format') . ' ' . \Formwork\Core\Formwork::instance()->option('date.hour_format'),
            $timestamp
        );
    }
];
