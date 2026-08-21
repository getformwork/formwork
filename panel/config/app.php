<?php

use Formwork\Cms\Site;
use Formwork\Config\Config;
use Formwork\Panel\Panel;
use Formwork\Security\CsrfToken;
use Formwork\Translations\Translation;
use Formwork\Utils\Date;

return fn(Site $site, Panel $panel, CsrfToken $csrfToken, Config $config, Translation $translation) => [
    'siteUri'     => $site->uri(includeLanguage: false),
    'baseUri'     => $panel->panelUri(),
    'csrfToken'   => $csrfToken->get($panel->getCsrfTokenName()),
    'colorScheme' => $panel->compatibleColorSchemes(),
    'translation' => [
        'code' => $translation->code(),
        'data' => $translation->getAllStrings(),
    ],
    'DateInput' => [
        'weekStarts'     => $config->getInt('system.date.weekStarts'),
        'dateFormat'     => Date::formatToPattern($config->getString('system.date.dateFormat')),
        'dateTimeFormat' => Date::formatToPattern($config->getString('system.date.datetimeFormat')),
        'time'           => true,
    ],
];
