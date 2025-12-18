<?php

use Formwork\Cms\App;
use Formwork\Http\Utils\Header;
use Formwork\Parsers\Markdown;
use Formwork\Utils\Date;
use Formwork\Utils\Html;
use Formwork\Utils\Path;
use Formwork\Utils\Str;
use Formwork\Utils\Text;
use Formwork\Utils\Uri;

return function (App $app) {
    return [
        'attr' => Html::attributes(...),

        'classes' => Html::classes(...),

        'escape' => Str::escape(...),

        'escapeAttr' => Str::escapeAttr(...),

        'removeHTML' => Str::removeHTML(...),

        'slug' => Str::slug(...),

        'append' => Str::append(...),

        'prepend' => Str::prepend(...),

        'countWords' => Text::countWords(...),

        'truncate' => Text::truncate(...),

        'truncateWords' => Text::truncateWords(...),

        'readingTime' => Text::readingTime(...),

        'redirect' => Header::redirect(...),

        /**
         * Generates a URI for the given route
         */
        'uri' => static function (string $route) use ($app): string {
            return Uri::make([], Path::join([$app->request()->root(), $route]));
        },

        /**
         * Parses the given Markdown string and returns the HTML output
         */
        'markdown' => static function (string $markdown) use ($app): string {
            $currentPage = $app->site()->currentPage();
            return Markdown::parse(
                $markdown,
                [
                    'site'                 => $app->site(),
                    'baseRoute'            => $currentPage !== null ? $currentPage->route() : '/',
                    'allowHtml'            => $app->config()->get('system.pages.content.allowHtml'),
                    'addHeadingIds'        => $app->config()->get('system.pages.content.addHeadingIds'),
                    'commonmarkExtensions' => $app->config()->get('system.pages.content.commonmarkExtensions', []),
                ]
            );
        },

        /**
         * Formats a timestamp as a date string
         */
        'date' => static function (int $timestamp, ?string $format = null) use ($app): string {
            return Date::formatTimestamp(
                $timestamp,
                $format ?? $app->config()->get('system.date.dateFormat'),
                $app->translations()->getCurrent()
            );
        },

        /**
         * Formats a timestamp as a datetime string
         */
        'datetime' => static function (int $timestamp) use ($app): string {
            return Date::formatTimestamp($timestamp, $app->config()->get('system.date.datetimeFormat'), $app->translations()->getCurrent());
        },

        /**
         * Formats a timestamp as a relative time string, e.g. "2 days ago", "in 3 hours"
         */
        'timedistance' => static function (int $timestamp) use ($app): string {
            return Date::formatTimestampAsDistance($timestamp, $app->translations()->getCurrent());
        },

        /**
         * Translates a given key using the current translations
         */
        'translate' => fn(string $key, ...$arguments) => $app->translations()->getCurrent()->translate($key, ...$arguments),
    ];
};
