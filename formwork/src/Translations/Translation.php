<?php

namespace Formwork\Translations;

use Formwork\Formwork;
use InvalidArgumentException;

class Translation
{
    /**
     * Translation language code
     */
    protected string $code;

    /**
     * Translation data
     */
    protected array $data = [];

    public function __construct(string $code, array $data)
    {
        $this->code = $code;
        $this->data = $data;
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
     */
    public function translate(string $key, ...$arguments)
    {
        if ($this->has($key)) {
            if (!empty($arguments)) {
                return sprintf($this->data[$key], ...$arguments);
            }
            return $this->data[$key];
        }

        $fallback = Formwork::instance()->translations()->getFallback();

        if ($fallback->code() !== $this->code) {
            return $fallback->translate($key, ...$arguments);
        }

        throw new InvalidArgumentException(sprintf('Invalid language string "%s"', $key));
    }
}
