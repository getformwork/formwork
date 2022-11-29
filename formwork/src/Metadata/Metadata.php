<?php

namespace Formwork\Metadata;

use Formwork\Utils\Str;

class Metadata
{
    protected const HTTP_EQUIV_NAMES = ['content-type', 'default-style', 'refresh'];

    /**
     * Metadata name
     */
    protected string $name;

    /**
     * Metadata content
     */
    protected string $content;

    /**
     * Metadata prefix
     */
    protected string $prefix;

    /**
     * Create a new Metadata instance
     */
    public function __construct(string $name, string $content)
    {
        $this->name = strtolower($name);
        $this->content = $content;

        if ($prefix = Str::before($name, ':')) {
            $this->prefix = $prefix;
        }
    }

    /**
     * Return metadata name
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Return whether the metadata is a charset declaration
     */
    public function isCharset(): bool
    {
        return $this->name === 'charset';
    }

    /**
     * Return whether the metadata is an http-equiv directive
     */
    public function isHTTPEquiv(): bool
    {
        return in_array($this->name, self::HTTP_EQUIV_NAMES, true);
    }

    /**
     * Return metadata content
     */
    public function content(): string
    {
        return $this->content;
    }

    /**
     * Return metadata prefix
     */
    public function prefix(): ?string
    {
        return $this->prefix;
    }

    /**
     * Return whether the metadata has a prefix (e.g. `twitter` for `twitter:card`, `og` for `og:image`)
     */
    public function hasPrefix(): bool
    {
        return $this->prefix !== null;
    }

    public function __toString(): string
    {
        return $this->content();
    }
}
