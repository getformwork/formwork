<?php

namespace Formwork\Admin;

use Formwork\Core\Formwork;
use Formwork\Languages\LanguageCodes;
use Formwork\Utils\FileSystem;

class Translation
{
    protected static $availableLanguages = [];

    /**
     * Get all available languages
     */
    public static function availableLanguages(): array
    {
        if (!empty(static::$availableLanguages)) {
            return static::$availableLanguages;
        }

        $path = Formwork::instance()->config()->get('translations.paths.admin');

        foreach (FileSystem::listFiles($path) as $file) {
            if (FileSystem::extension($file) === 'yml') {
                $code = FileSystem::name($file);
                static::$availableLanguages[$code] = LanguageCodes::codeToNativeName($code) . ' (' . $code . ')';
            }
        }

        return static::$availableLanguages;
    }
}
