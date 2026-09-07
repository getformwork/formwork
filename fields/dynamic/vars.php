<?php

use Formwork\Cms\App;
use Formwork\Languages\LanguageCodes;
use Formwork\Panel\Utils\DateFormats;
use Formwork\Utils\MimeType;

return function (App $app) {
    return [
        'formwork' => $app,

        'site' => $app->site(),

        'dateFormats' => [
            'date'      => DateFormats::date(),
            'hour'      => DateFormats::hour(),
            'timezones' => DateFormats::timezones(),
        ],

        'languages' => [
            'names' => LanguageCodes::names(),
        ],

        'mimeTypes' => [
            'getExtensionTypes' => MimeType::extensionTypes(...),
        ],
    ];
};
