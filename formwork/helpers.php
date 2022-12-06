<?php

use Formwork\Formwork;
use Formwork\Parsers\Markdown;
use Formwork\Utils\Date;
use Formwork\Utils\Header;

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
    'redirect' => [
        'Formwork\\Utils\\Header',
        'redirect'
    ],
    'markdown' => static function (string $markdown): string {
        $currentPage = Formwork::instance()->site()->currentPage();
        return Markdown::parse(
            $markdown,
            ['baseRoute' => $currentPage ? $currentPage->route() : '/']
        );
    },
    'date' => static function (int $timestamp, string $format = null): string {
        return Date::formatTimestamp(
            $timestamp,
            $format ?? \Formwork\Formwork::instance()->config()->get('date.format')
        );
    },
    'datetime' => static function (int $timestamp): string {
        return Date::formatTimestamp(
            $timestamp,
            Formwork::instance()->config()->get('date.format') . ' ' . Formwork::instance()->config()->get('date.timeFormat')
        );
    },
    'translate' => fn (string $key, ...$arguments) => Formwork::instance()->translations()->getCurrent()->translate($key, ...$arguments),
];
