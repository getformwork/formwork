<?php

namespace Formwork\Files;

use Formwork\Utils\FileSystem;
use Formwork\Utils\MimeType;
use LogicException;

class File
{
    protected $path;

    protected $name;

    protected $extension;

    protected $mimeType;

    protected $type;

    protected $size;

    public function __construct($path)
    {
        $this->path = $path;
        $this->name = FileSystem::basename($path);
        $this->extension = FileSystem::extension($path);
        $this->mimeType = FileSystem::mimeType($path);
        $this->type = $this->type();
        $this->size = FileSystem::size($path);
    }

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
        if (in_array($this->extension, array('pdf', 'doc', 'docx', 'odt'))) {
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
