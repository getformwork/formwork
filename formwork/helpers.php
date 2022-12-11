<?php

use Formwork\Formwork;
use Formwork\Parsers\Markdown;
use Formwork\Utils\Date;
use Formwork\Utils\Header;
use Formwork\Utils\HTML;
use Formwork\Utils\Str;
use Formwork\Utils\Text;

return [
    'attr' => [HTML::class, 'attributes'],

    'escape' => [Str::class, 'escape'],

    'escapeAttr' => [Str::class, 'escapeAttr'],

    'removeHTML' => [Str::class, 'removeHTML'],

    'slug' => [Str::class, 'slug'],

    'countWords' => [Text::class, 'countWords'],

    'truncate' => [Text::class, 'truncate'],

    'truncateWords' => [Text::class, 'truncateWords'],

    'readingTime' => [Text::class, 'readingTime'],

    'redirect' => [Header::class, 'redirect'],

    'markdown' => static function (string $markdown): string {
        $currentPage = Formwork::instance()->site()->currentPage();
        return Markdown::parse(
            $markdown,
            ['baseRoute' => $currentPage ? $currentPage->route() : '/']
        );
    },

    'date' => static function (int $timestamp, ?string $format = null): string {
        return Date::formatTimestamp(
            $timestamp,
            $format ?? Formwork::instance()->config()->get('date.format')
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
