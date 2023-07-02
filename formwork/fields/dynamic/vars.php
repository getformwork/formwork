<?php

use Formwork\Formwork;
use Formwork\Languages\LanguageCodes;
use Formwork\Panel\Utils\DateFormats;

return [
    'formwork' => Formwork::instance(),

    'site' => Formwork::instance()->site(),

    'dateFormats' => [
        'date'      => DateFormats::date(),
        'hour'      => DateFormats::hour(),
        'timezones' => DateFormats::timezones()
    ],

    'languages' => [
        'names' => LanguageCodes::names()
    ]
];
