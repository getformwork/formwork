<?php

use Formwork\App;
use Formwork\Http\Utils\Header;
use Formwork\Parsers\Markdown;
use Formwork\Utils\Date;
use Formwork\Utils\Html;
use Formwork\Utils\Str;
use Formwork\Utils\Text;

return function (App $app) {
    return [
        'attr' => Html::attributes(...),

        'escape' => Str::escape(...),

        'escapeAttr' => Str::escapeAttr(...),

        'removeHTML' => Str::removeHTML(...),

        'slug' => Str::slug(...),

        'countWords' => Text::countWords(...),

        'truncate' => Text::truncate(...),

        'truncateWords' => Text::truncateWords(...),

        'readingTime' => Text::readingTime(...),

        'redirect' => Header::redirect(...),

        'markdown' => static function (string $markdown) use ($app): string {
            $currentPage = $app->site()->currentPage();
            return Markdown::parse(
                $markdown,
                ['baseRoute' => $currentPage ? $currentPage->route() : '/']
            );
        },

        'date' => static function (int $timestamp, ?string $format = null) use ($app): string {
            return Date::formatTimestamp(
                $timestamp,
                $format ?? $app->config()->get('system.date.dateFormat')
            );
        },

        'datetime' => static function (int $timestamp) use ($app): string {
            return Date::formatTimestamp($timestamp, $app->config()->get('system.date.datetimeFormat'));
        },

        'translate' => fn (string $key, ...$arguments) => $app->translations()->getCurrent()->translate($key, ...$arguments),
    ];
};
