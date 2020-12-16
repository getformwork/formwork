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
        $this->available = (array) Formwork::instance()->config()->get('languages.available');
        $this->current = $this->default = Formwork::instance()->config()->get('languages.default', $this->available[0] ?? null);
    }

    /**
     * Get available languages
     */
    public function available(): array
    {
        return $this->available;
    }

    /**
     * Get default language code
     */
    public function default(): ?string
    {
        return $this->default;
    }

    /**
     * Get current language code
     */
    public function current(): ?string
    {
        return $this->current;
    }

    /**
     * Get requested language code
     */
    public function requested(): ?string
    {
        return $this->requested;
    }

    /**
     * Get preferred language code
     */
    public function preferred(): ?string
    {
        return $this->preferred;
    }

    /**
     * Return whether current language is the default
     */
    public function isDefault(): bool
    {
        return $this->current === $this->default;
    }

    /**
     * Create a Languages instance from a given request
     */
    public static function fromRequest(string $request): self
    {
        $languages = new static();

        if (preg_match('~^/(' . implode('|', $languages->available) . ')/~i', $request, $matches)) {
            $languages->requested = $languages->current = $matches[1];
        }

        if (Formwork::instance()->config()->get('languages.http_preferred')) {
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
