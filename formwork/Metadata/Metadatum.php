<?php

namespace Formwork\Metadata;

use Formwork\Utils\Str;

class Metadatum
{
    protected const HTTP_EQUIV_NAMES = ['content-type', 'default-style', 'refresh'];

    /**
     * Metadatum name
     *
     * @var string
     */
    protected $name;

    /**
     * Metadatum content
     *
     * @var string
     */
    protected $content;

    /**
     * Metadatum prefix
     *
     * @var string
     */
    protected $prefix;

    /**
     * Create a new Metadatum instance
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
     * Return metadatum name
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Return whether the metadatum is a charset declaration
     */
    public function isCharset(): bool
    {
        return $this->name === 'charset';
    }

    /**
     * Return whether the metadatum is an http-equiv directive
     */
    public function isHTTPEquiv(): bool
    {
        return in_array($this->name, self::HTTP_EQUIV_NAMES, true);
    }

    /**
     * Return metadatum content
     */
    public function content(): string
    {
        return $this->content;
    }

    /**
     * Return metadatum prefix
     */
    public function prefix(): ?string
    {
        return $this->prefix;
    }

    /**
     * Return whether the metadatum has a prefix (e.g. 'twitter' for 'twitter:card', 'og' for 'og:image')
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
