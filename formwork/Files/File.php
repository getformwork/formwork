<?php

namespace Formwork\Files;

use Formwork\Core\Formwork;
use Formwork\Utils\FileSystem;
use Formwork\Utils\HTTPRequest;
use Formwork\Utils\Str;
use Formwork\Utils\Uri;

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
     */
    public function __construct(string $path)
    {
        $this->path = $path;
        $this->name = basename($path);
        $this->extension = FileSystem::extension($path);
        $this->mimeType = FileSystem::mimeType($path);
        $this->type = $this->type();
        $this->uri = Uri::resolveRelativeUri($this->name, HTTPRequest::root() . ltrim(Formwork::instance()->request(), '/'));
        $this->size = FileSystem::size($path);
    }

    /**
     * Get file path
     *
     * @return string
     */
    public function path()
    {
        return $this->path;
    }

    /**
     * Get file name
     *
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * Get file extension
     *
     * @return string
     */
    public function extension()
    {
        return $this->extension;
    }

    /**
     * Get file URI
     *
     * @return string
     */
    public function uri()
    {
        return $this->uri;
    }

    /**
     * Get file MIME type
     *
     * @return string
     */
    public function mimeType()
    {
        return $this->mimeType;
    }

    /**
     * Get file type
     *
     * @return string|null
     */
    public function type()
    {
        if ($this->type !== null) {
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
        if (in_array($this->extension, ['pdf', 'doc', 'docx', 'odt'], true)) {
            return 'document';
        }
        return null;
    }

    /**
     * Get file size
     *
     * @return string
     */
    public function size()
    {
        return $this->size;
    }

    public function __toString()
    {
        return $this->name;
    }
}
