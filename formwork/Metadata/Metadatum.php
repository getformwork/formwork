<?php

namespace Formwork\Metadata;

class Metadatum
{
    const HTTP_EQUIV_NAMES = array('content-type', 'default-style', 'refresh');

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
     *
     * @param string $name
     * @param string $content
     */
    public function __construct($name, $content)
    {
        $this->name = strtolower($name);
        $this->content = $content;

        if ($prefix = strstr($name, ':', true)) {
            $this->prefix = $prefix;
        }
    }

    /**
     * Return whether the metadatum is a charset declaration
     *
     * @return bool
     */
    public function isCharset()
    {
        return $this->name === 'charset';
    }

    /**
     * Return whether the metadatum is an http-equiv directive
     *
     * @return bool
     */
    public function isHTTPEquiv()
    {
        return in_array($this->name, self::HTTP_EQUIV_NAMES, true);
    }

    /**
     * Return whether the metadatum has a prefix (e.g. 'twitter' for 'twitter:card', 'og' for 'og:image')
     *
     * @return bool
     */
    public function hasPrefix()
    {
        return !is_null($this->prefix);
    }

    public function __call($name, $arguments)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        throw new LogicException('Invalid method ' . static::class . '::' . $name);
    }
}
