<?php

namespace Formwork\Admin;

use Formwork\Admin\Utils\LanguageCodes;
use Formwork\Parsers\YAML;
use Formwork\Utils\FileSystem;
use LogicException;
use RuntimeException;

class Language
{
    /**
     * Array containing languages available in administration panel
     *
     * @var array
     */
    protected static $availableLanguages = array();

    /**
     * Language code
     *
     * @var string
     */
    protected $code;

    /**
     * Array containing language strings
     *
     * @var array
     */
    protected $strings = array();

    /**
     * Create a new Language istance
     *
     * @param string $code
     * @param array  $strings
     */
    public function __construct($code, $strings)
    {
        $this->code = $code;
        $this->strings = $strings;
    }

    /**
     * Get all available languages
     *
     * @return array
     */
    public static function availableLanguages()
    {
        return static::$availableLanguages;
    }

    /**
     * Get language code
     *
     * @return string
     */
    public function code()
    {
        return $this->code;
    }

    /**
     * Return a formatted language label
     *
     * @param string           $key
     * @param float|int|string ...$arguments
     *
     * @return string
     */
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

    /**
     * Load administration panel language
     *
     * @param string $languageCode
     *
     * @return self
     */
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
