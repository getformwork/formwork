<?php

namespace Formwork\Files;

use Formwork\Utils\FileSystem;
use Formwork\Utils\MimeType;
use Formwork\Utils\Str;
use Formwork\Utils\Uri;
use LogicException;

class File
{
    /**
     * File path
     *
     * @var string
     */
    protected $path;

    /**
     * File name
     *
     * @var string
     */
    protected $name;

    /**
     * File extension
     *
     * @var string
     */
    protected $extension;

    /**
     * File uri
     *
     * @var string
     */
    protected $uri;

    /**
     * File MIME type
     *
     * @var string
     */
    protected $mimeType;

    /**
     * File type (image, text, audio, video or document)
     *
     * @var string
     */
    protected $type;

    /**
     * File size in a human-readable format
     *
     * @var string
     */
    protected $size;

    /**
     * Create a new File instance
     *
     * @param string $path
     */
    public function __construct($path)
    {
        $this->path = $path;
        $this->name = basename($path);
        $this->extension = FileSystem::extension($path);
        $this->mimeType = FileSystem::mimeType($path);
        $this->type = $this->type();
        $this->size = FileSystem::size($path);
    }

    /**
     * Get file uri
     *
     * @return string|null
     */
    public function uri()
    {
        if (!is_null($this->uri)) {
            return $this->uri;
        }
        return $this->uri = Uri::resolveRelativeUri($this->name, Uri::relativePath());
    }

    /**
     * Get file type
     *
     * @return string|null
     */
    public function type()
    {
        if (!is_null($this->type)) {
            return $this->type;
        }
        if (Str::startsWith($this->mimeType, 'image')) {
            return 'image';
        }
        if (Str::startsWith($this->mimeType, 'text')) {
            return 'text';
        }
        if (Str::startsWith($this->mimeType, 'audio')) {
            return 'audio';
        }
        if (Str::startsWith($this->mimeType, 'video')) {
            return 'video';
        }
        if (in_array($this->extension, array('pdf', 'doc', 'docx', 'odt'), true)) {
            return 'document';
        }
        return null;
    }

    public function __call($name, $arguments)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        throw new LogicException('Invalid method ' . static::class . '::' . $name);
    }

    public function __toString()
    {
        return $this->name;
    }
}
