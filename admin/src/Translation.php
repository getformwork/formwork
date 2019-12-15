<?php

namespace Formwork\Admin;

use Formwork\Languages\LanguageCodes;
use Formwork\Parsers\YAML;
use Formwork\Utils\FileSystem;
use LogicException;
use RuntimeException;

class Translation
{
    /**
     * Fallback language code
     *
     * @var string
     */
    protected const FALLBACK_LANGUAGE_CODE = 'en';

    /**
     * Array containing available languages
     *
     * @var array
     */
    protected static $availableLanguages = [];

    /**
     * Fallback translation instance
     *
     * @var Translation
     */
    protected static $fallbackTranslation;

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
    protected $strings = [];

    /**
     * Create a new Translation istance
     */
    public function __construct(string $code, array $strings)
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
     * Return whether a language string is set
     *
     * @return bool
     */
    public function has(string $key)
    {
        return isset($this->strings[$key]);
    }

    /**
     * Return a formatted language label
     *
     * @param float|int|string ...$arguments
     *
     * @return string
     */
    public function get(string $key, ...$arguments)
    {
        if (!$this->has($key)) {
            if ($this->code !== self::FALLBACK_LANGUAGE_CODE) {
                return $this->fallbackTranslation()->get($key, ...$arguments);
            }
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
     * @return self
     */
    public static function load(string $languageCode)
    {
        if (empty(static::$availableLanguages)) {
            foreach (FileSystem::listFiles(Admin::TRANSLATIONS_PATH) as $file) {
                $code = FileSystem::name($file);
                static::$availableLanguages[$code] = LanguageCodes::codeToNativeName($code) . ' (' . $code . ')';
            }
        }

        $translationFile = Admin::TRANSLATIONS_PATH . $languageCode . '.yml';

        if (!(FileSystem::exists($translationFile) && FileSystem::isReadable($translationFile))) {
            throw new RuntimeException('Cannot load Admin language file ' . $translationFile);
        }

        $languageStrings = YAML::parseFile($translationFile);

        return new static($languageCode, $languageStrings);
    }

    /**
     * Return fallback translation instance
     *
     * @return Translation
     */
    protected function fallbackTranslation()
    {
        if (static::$fallbackTranslation !== null) {
            return static::$fallbackTranslation;
        }
        return static::$fallbackTranslation = static::load(self::FALLBACK_LANGUAGE_CODE);
    }
}
