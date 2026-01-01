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
    'DateInput'   => [
        'weekStarts'     => $config->get('system.date.weekStarts'),
        'dateFormat'     => Date::formatToPattern($config->get('system.date.dateFormat')),
        'dateTimeFormat' => Date::formatToPattern($config->get('system.date.datetimeFormat')),
        'time'           => true,
        'labels'         => [
            'today'      => $translation->translate('date.today'),
            'weekdays'   => ['long' => $translation->getStrings('date.weekdays.long'), 'short' => $translation->getStrings('date.weekdays.short')],
            'months'     => ['long' => $translation->getStrings('date.months.long'), 'short' => $translation->getStrings('date.months.short')],
            'prevMonth'  => $translation->translate('fields.date.previousMonth'),
            'nextMonth'  => $translation->translate('fields.date.nextMonth'),
            'prevHour'   => $translation->translate('fields.date.previousHour'),
            'nextHour'   => $translation->translate('fields.date.nextHour'),
            'prevMinute' => $translation->translate('fields.date.previousMinute'),
            'nextMinute' => $translation->translate('fields.date.nextMinute'),
        ],
    ],
    'DurationInput' => [
        'labels' => [
            'years'   => $translation->getStrings('date.duration.years'),
            'months'  => $translation->getStrings('date.duration.months'),
            'weeks'   => $translation->getStrings('date.duration.weeks'),
            'days'    => $translation->getStrings('date.duration.days'),
            'hours'   => $translation->getStrings('date.duration.hours'),
            'minutes' => $translation->getStrings('date.duration.minutes'),
            'seconds' => $translation->getStrings('date.duration.seconds'),
        ],
    ],
    'EditorInput' => [
        'labels' => [
            'bold'           => $translation->translate('panel.editor.bold'),
            'italic'         => $translation->translate('panel.editor.italic'),
            'link'           => $translation->translate('panel.editor.link'),
            'image'          => $translation->translate('panel.editor.image'),
            'quote'          => $translation->translate('panel.editor.quote'),
            'undo'           => $translation->translate('panel.editor.undo'),
            'redo'           => $translation->translate('panel.editor.redo'),
            'bulletList'     => $translation->translate('panel.editor.bulletList'),
            'numberedList'   => $translation->translate('panel.editor.numberedList'),
            'code'           => $translation->translate('panel.editor.code'),
            'heading1'       => $translation->translate('panel.editor.heading1'),
            'heading2'       => $translation->translate('panel.editor.heading2'),
            'heading3'       => $translation->translate('panel.editor.heading3'),
            'heading4'       => $translation->translate('panel.editor.heading4'),
            'heading5'       => $translation->translate('panel.editor.heading5'),
            'heading6'       => $translation->translate('panel.editor.heading6'),
            'paragraph'      => $translation->translate('panel.editor.paragraph'),
            'increaseIndent' => $translation->translate('panel.editor.increaseIndent'),
            'decreaseIndent' => $translation->translate('panel.editor.decreaseIndent'),
            'edit'           => $translation->translate('panel.modal.action.edit'),
            'delete'         => $translation->translate('panel.modal.action.delete'),
            'toggleMarkdown' => $translation->translate('panel.editor.toggleMarkdown'),
        ],
    ],
    'SelectInput' => [
        'labels' => [
            'empty' => $translation->translate(('fields.select.empty')),
        ],
    ],
    'TagsInput' => [
        'labels' => [
            'remove' => $translation->translate('fields.tags.remove'),
        ],
    ],
    'Backups' => [
        'labels' => [
            'now' => $translation->translate('date.now'),
        ],
    ],
];
