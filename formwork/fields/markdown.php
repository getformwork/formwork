<?php

use Formwork\Cms\App;
use Formwork\Cms\Site;
use Formwork\Fields\Field;
use Formwork\Parsers\Markdown;
use Formwork\Utils\Str;

return function (App $app, Site $site) {
    return [
        'extend'  => 'textarea',
        'methods' => [
            /**
             * Return the rows displayed by default in the textarea
             *
             * If not set, the default is 15 rows
             */
            'rows' => function (Field $field): int {
                return $field->get('rows', 15);
            },

            /**
             * Convert the field value to HTML
             */
            'toHTML' => function (Field $field) use ($app, $site): string {
                $currentPage = $site->currentPage();
                return Markdown::parse(
                    (string) $field->value(),
                    [
                        'site'                 => $site,
                        'baseRoute'            => $currentPage !== null ? $currentPage->route() : '/',
                        'allowHtml'            => $app->config()->getBool('system.pages.content.allowHtml'),
                        'addHeadingIds'        => $app->config()->getBool('system.pages.content.addHeadingIds'),
                        'commonmarkExtensions' => $app->config()->getArray('system.pages.content.commonmarkExtensions', []),
                    ]
                );
            },

            'toString' => function (Field $field): string {
                return $field->toHTML();
            },

            /**
             * Get the field value as plain text
             */
            'toPlainText' => function (Field $field): string {
                return Str::removeHTML($field->toHTML());
            },

            'return' => function (Field $field): Field {
                return $field;
            },
        ],
    ];
};
