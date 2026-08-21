<?php

namespace Formwork\Translations;

use Formwork\Utils\Arr;
use InvalidArgumentException;
use Stringable;

class Translation
{
    protected ?Translation $fallback = null;

    /**
     * @param array<string, list<string>|string> $data
     */
    public function __construct(
        protected string $code,
        protected array $data,
    ) {}

    /**
     * Set the fallback translation
     */
    public function setFallback(?Translation $fallbackTranslation): void
    {
        $this->fallback = $fallbackTranslation;
    }

    /**
     * Get the translation language code
     */
    public function code(): string
    {
        return $this->code;
    }

    /**
     * Return whether a language string is set
     */
    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * Return a formatted language string
     *
     * @throws InvalidArgumentException If the language string key is invalid and no fallback is available
     */
    public function translate(string $key, int|float|string|Stringable ...$arguments): string
    {
        if ($this->has($key)) {
            $value = $this->data[$key];
            if (is_string($value)) {
                if ($arguments !== []) {
                    return sprintf($value, ...$arguments);
                }
                return $value;
            }
        }

        if ($this->fallback !== null && $this->fallback->code() !== $this->code) {
            return $this->fallback->translate($key, ...$arguments);
        }

        throw new InvalidArgumentException(sprintf('Invalid language string "%s"', $key));
    }

    /**
     * Return the language strings for a given key with multiple values (e.g. plural forms, weekdays, etc.)
     *
     * @throws InvalidArgumentException If the language string key is invalid and no fallback is available
     *
     * @return list<string>
     */
    public function getStrings(string $key): array
    {
        if ($this->has($key)) {
            return (array) $this->data[$key];
        }

        if ($this->fallback !== null && $this->fallback->code() !== $this->code) {
            return $this->fallback->getStrings($key);
        }

        throw new InvalidArgumentException(sprintf('Invalid language string "%s"', $key));
    }

    /**
     * Return all language strings
     *
     * @return array<string, list<string>|string>
     */
    public function getAllStrings(): array
    {
        if ($this->fallback !== null && $this->fallback->code() !== $this->code) {
            return Arr::override($this->fallback->getAllStrings(), $this->data);
        }
        return $this->data;
    }
}
