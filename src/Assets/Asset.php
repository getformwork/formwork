<?php

namespace Formwork\Assets;

use Formwork\Assets\Exceptions\AssetNotFoundException;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Uri;

class Asset
{
    /**
     * Asset file path
     */
    private string $path;

    /**
     * Asset URI
     */
    private string $uri;

    /**
     * Asset version (based on last modification time)
     */
    private string $version;

    /**
     * Asset integrity hash
     */
    private string $integrityHash;

    /**
     * Asset MIME type
     */
    private string $mimeType;

    /**
     * @param array<string, mixed> $meta Asset metadata
     *
     * @throws AssetNotFoundException If the asset file is not found
     */
    public function __construct(string $path, string $uri, private array $meta = [])
    {
        $this->path = FileSystem::normalizePath($path);
        $this->uri = Uri::normalize($uri);

        if (!FileSystem::isFile($this->path)) {
            throw new AssetNotFoundException(sprintf('Asset file "%s" not found', $this->path));
        }
    }

    /**
     * Get asset path
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * Get asset URI
     */
    public function uri(bool $includeVersion = false): string
    {
        $uri = $this->uri;
        if ($includeVersion) {
            $uri .= "?v={$this->version()}";
        }
        return $uri;
    }

    /**
     * Get asset version
     */
    public function version(): string
    {
        return $this->version ??= dechex(FileSystem::lastModifiedTime($this->path));
    }

    /**
     * Get asset integrity hash
     */
    public function integrityHash(): string
    {
        return $this->integrityHash ??= 'sha256-' . base64_encode(hash('sha256', FileSystem::read($this->path), true));
    }

    /**
     * Get asset MIME type
     */
    public function mimeType(): string
    {
        return $this->mimeType ??= FileSystem::mimeType($this->path);
    }

    /**
     * Get asset content
     */
    public function content(): string
    {
        return FileSystem::read($this->path);
    }

    /**
     * Get asset content as base64 encoded string
     */
    public function toBase64(): string
    {
        return "data:{$this->mimeType()};base64," . base64_encode($this->content());
    }

    /**
     * Get asset metadata value by key
     *
     * @since 2.2.0
     */
    public function getMeta(string $key, mixed $default = null): mixed
    {
        return $this->meta[$key] ?? $default;
    }
}
