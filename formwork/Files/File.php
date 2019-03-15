<?php

namespace Formwork\Files;

use Formwork\Utils\FileSystem;
use Formwork\Utils\MimeType;
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
     * Get file type
     *
     * @return string|null
     */
    public function type()
    {
        if (!is_null($this->type)) {
            return $this->type;
        }
        if (strpos($this->mimeType, 'image') === 0) {
            return 'image';
        }
        if (strpos($this->mimeType, 'text') === 0) {
            return 'text';
        }
        if (strpos($this->mimeType, 'audio') === 0) {
            return 'audio';
        }
        if (strpos($this->mimeType, 'video') === 0) {
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
