<?php

namespace Formwork\Files;

use Formwork\Formwork;
use Formwork\Utils\FileSystem;
use Formwork\Utils\HTTPRequest;
use Formwork\Utils\Str;
use Formwork\Utils\Uri;

class File
{
    /**
     * File path
     */
    protected string $path;

    /**
     * File name
     */
    protected string $name;

    /**
     * File extension
     */
    protected string $extension;

    /**
     * File uri
     */
    protected string $uri;

    /**
     * File MIME type
     */
    protected string $mimeType;

    /**
     * File type (image, text, audio, video or document)
     */
    protected ?string $type;

    /**
     * File size in a human-readable format
     */
    protected string $size;

    /**
     * File hash
     */
    protected string $hash;

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
        $this->uri = Uri::resolveRelative($this->name, HTTPRequest::root() . ltrim(Formwork::instance()->request(), '/'));
        $this->size = FileSystem::formatSize(FileSystem::fileSize($path));
    }

    /**
     * Get file path
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * Get file name
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Get file extension
     */
    public function extension(): string
    {
        return $this->extension;
    }

    /**
     * Get file URI
     */
    public function uri(): string
    {
        return $this->uri;
    }

    /**
     * Get file MIME type
     */
    public function mimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * Get file type
     */
    public function type(): ?string
    {
        if (isset($this->type)) {
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
        if (in_array($this->extension, ['gz', '7z', 'bz2', 'rar', 'tar', 'zip'], true)) {
            return 'archive';
        }
        return null;
    }

    /**
     * Get file size
     */
    public function size(): string
    {
        return $this->size;
    }

    /**
     * Get file hash
     */
    public function hash(): string
    {
        if (isset($this->hash)) {
            return $this->hash;
        }
        return $this->hash = hash_file('sha256', $this->path);
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
