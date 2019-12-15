<?php

namespace Formwork\Languages;

use Formwork\Core\Formwork;
use Formwork\Utils\HTTPNegotiation;

class Languages
{
    /**
     * Array containing available languages
     *
     * @var array
     */
    protected $available = [];

    /**
     * Default language code
     *
     * @var string
     */
    protected $default;

    /**
     * Current language code
     *
     * @var string
     */
    protected $current;

    /**
     * Requested language code
     *
     * @var string
     */
    protected $requested;

    /**
     * Preferred language code
     *
     * @var string
     */
    protected $preferred;

    /**
     * Create a new Languages instance
     */
    public function __construct()
    {
        $this->available = (array) Formwork::instance()->option('languages.available');
        $this->current = $this->default = Formwork::instance()->option('languages.default', $this->available[0] ?? null);
    }

    /**
     * Get available languages
     *
     * @return array
     */
    public function available()
    {
        return $this->available;
    }

    /**
     * Get default language code
     *
     * @return string
     */
    public function default()
    {
        return $this->default;
    }

    /**
     * Get current language code
     *
     * @return string
     */
    public function current()
    {
        return $this->current;
    }

    /**
     * Get requested language code
     *
     * @return string
     */
    public function requested()
    {
        return $this->requested;
    }

    /**
     * Get preferred language code
     *
     * @return string
     */
    public function preferred()
    {
        return $this->preferred;
    }

    /**
     * Return whether current language is the default
     *
     * @return bool
     */
    public function isDefault()
    {
        return $this->current === $this->default;
    }

    /**
     * Create a Languages instance from a given request
     *
     * @param string $request
     *
     * @return self
     */
    public static function fromRequest(string $request)
    {
        $languages = new static();

        if (preg_match('~^/(' . implode('|', $languages->available) . ')/~i', $request, $matches)) {
            $languages->requested = $languages->current = $matches[1];
        }

        if (Formwork::instance()->option('languages.http_preferred')) {
            foreach (array_keys(HTTPNegotiation::language()) as $code) {
                if (in_array($code, $languages->available, true)) {
                    $languages->preferred = $code;
                    break;
                }
            }
        }

        return $languages;
    }
}
