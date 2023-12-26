<?php

namespace Formwork\Languages;

class Languages
{
    /**
     * Array containing available languages
     */
    protected LanguageCollection $available;

    /**
     * Default language code
     */
    protected ?Language $default = null;

    /**
     * Current language code
     */
    protected ?Language $current = null;

    /**
     * Requested language code
     */
    protected ?Language $requested = null;

    /**
     * Preferred language code
     */
    protected ?Language $preferred = null;

    /**
     * Create a new Languages instance
     *
     * @param array{available?: array<string>, default?: Language|string|null, current?: Language|string|null, requested?: Language|string|null, preferred?: Language|string|null} $options
     */
    public function __construct(array $options = [])
    {
        $this->available = new LanguageCollection($options['available'] ?? []);

        $this->default = $this->resolveLanguage($options['default'] ?? null);

        $this->current = $this->resolveLanguage($options['current'] ?? $this->default);

        $this->requested = $this->resolveLanguage($options['requested'] ?? null);

        $this->preferred = $this->resolveLanguage($options['preferred'] ?? null);
    }

    /**
     * Get available languages
     */
    public function available(): LanguageCollection
    {
        return $this->available;
    }

    /**
     * Get default language code
     */
    public function default(): ?Language
    {
        return $this->default;
    }

    /**
     * Get current language code
     */
    public function current(): ?Language
    {
        return $this->current;
    }

    /**
     * Get requested language code
     */
    public function requested(): ?Language
    {
        return $this->requested;
    }

    /**
     * Get preferred language code
     */
    public function preferred(): ?Language
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
     * Get the proper `Language` instance
     */
    protected function resolveLanguage(Language|string|null $language): ?Language
    {
        switch (true) {
            case $language instanceof Language:
                return $language;

            case is_string($language):
                return $this->available->get($language, null);

            default:
                return null;
        }
    }
}
