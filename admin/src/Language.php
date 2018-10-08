<?php

namespace Formwork\Admin;

use Formwork\Admin\Utils\LanguageCodes;
use Formwork\Parsers\YAML;
use Formwork\Utils\FileSystem;
use LogicException;
use RuntimeException;

class Language
{
    protected static $availableLanguages;

    protected $code;

    protected $strings;

    public function __construct($code, $strings)
    {
        $this->code = $code;
        $this->strings = $strings;
    }

    public static function availableLanguages()
    {
        return static::$availableLanguages;
    }

    public function code()
    {
        return $this->code;
    }

    public function get($key, ...$arguments)
    {
        if (!isset($this->strings[$key])) {
            throw new LogicException('Invalid language string "' . $key . '"');
        }
        if (!empty($arguments)) {
            return sprintf($this->strings[$key], ...$arguments);
        }
        return $this->strings[$key];
    }

    public static function load($languageCode)
    {
        if (empty(static::$availableLanguages)) {
            foreach (FileSystem::listFiles(LANGUAGES_PATH) as $file) {
                $code = FileSystem::name($file);
                static::$availableLanguages[$code] = LanguageCodes::codeToNativeName($code) . ' (' . $code . ')';
            }
        }
        $languageFile = LANGUAGES_PATH . $languageCode . '.yml';
        if (!(FileSystem::exists($languageFile) && FileSystem::isReadable($languageFile))) {
            throw new RuntimeException('Cannot load Admin language file ' . $languageFile);
        }
        $languageStrings = YAML::parseFile($languageFile);
        return new static($languageCode, $languageStrings);
    }
}
