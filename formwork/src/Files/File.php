<?php

namespace Formwork\Files;

use Formwork\Data\Contracts\Arrayable;
use Formwork\Files\Exceptions\FileUriGenerationException;
use Formwork\Model\Attributes\ReadonlyModelProperty;
use Formwork\Model\Model;
use Formwork\Schemes\Scheme;
use Formwork\Utils\FileSystem;
use Formwork\Utils\MimeType;
use Formwork\Utils\Str;
use RuntimeException;
use Stringable;

class File extends Model implements Arrayable, Stringable
{
    /**
     * File scheme identifier
     */
    public const string SCHEME_IDENTIFIER = 'files.file';

    /**
     * Model identifier
     */
    protected const string MODEL_IDENTIFIER = 'file';

    /**
     * File name
     */
    #[ReadonlyModelProperty]
    protected string $name;

    /**
     * File extension
     */
    #[ReadonlyModelProperty]
    protected string $extension;

    /**
     * File MIME type
     */
    #[ReadonlyModelProperty]
    protected string $mimeType;

    /**
     * File type in a human-readable format
     *
     * @var 'archive'|'audio'|'document'|'image'|'pdf'|'presentation'|'spreadsheet'|'text'|'video'|null
     */
    #[ReadonlyModelProperty]
    protected ?string $type = null;

    /**
     * File size in a human-readable format
     */
    #[ReadonlyModelProperty]
    protected string $size;

    /**
     * File last modified time
     */
    #[ReadonlyModelProperty]
    protected int $lastModifiedTime;

    /**
     * File hash
     */
    #[ReadonlyModelProperty]
    protected string $hash;

    /**
     * File content hash
     */
    #[ReadonlyModelProperty]
    protected string $contentHash;

    protected FileUriGenerator $uriGenerator;

    /**
     * @param string $path File path
     */
    public function __construct(
        protected string $path,
    ) {
        $this->name = basename($path);
        $this->extension = FileSystem::extension($path);
    }

    public function __toString(): string
    {
        return $this->name;
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
     * Get file MIME type
     */
    public function mimeType(): string
    {
        return $this->mimeType ??= FileSystem::mimeType($this->path);
    }

    /**
     * Get file type in a human-readable format (`archive`, `audio`, `document`, `image`, `pdf`, `presentation`, `spreadsheet`, `text`, `video` or `null` if unknown)
     *
     * Image, audio and video types are restricted to the supported formats
     *
     * @return 'archive'|'audio'|'document'|'image'|'pdf'|'presentation'|'spreadsheet'|'text'|'video'|null
     */
    public function type(): ?string
    {
        if ($this->type !== null) {
            return $this->type;
        }
        if (Str::startsWith($this->mimeType(), 'text')) {
            return $this->type = 'text';
        }
        if (Str::startsWith($this->mimeType(), 'image') && $this->matchExtensions(['gif', 'jpg', 'jpeg', 'jpe', 'png', 'svg', 'webp'])) {
            return $this->type = 'image';
        }
        if (Str::startsWith($this->mimeType(), 'audio') && $this->matchExtensions(['mp3', 'mpga', 'mp2', 'm2a', 'mp2a', 'm3a', 'm4a', 'mp4a', 'aac', 'wav', 'oga', 'ogg', 'spx', 'opus', 'flac'])) {
            return $this->type = 'audio';
        }
        if (Str::startsWith($this->mimeType(), 'video') && $this->matchExtensions(['mp4', 'mpg4', 'mp4v', 'webm', 'ogg', 'mov'])) {
            return $this->type = 'video';
        }
        if ($this->matchExtensions(['pdf'])) {
            return $this->type = 'pdf';
        }
        if ($this->matchExtensions(['doc', 'docx', 'odt', 'odm', 'ott'])) {
            return $this->type = 'document';
        }
        if ($this->matchExtensions(['ppt', 'pptx', 'pps', 'odp', 'otp'])) {
            return $this->type = 'presentation';
        }
        if ($this->matchExtensions(['xls', 'xlsx', 'ods', 'ots'])) {
            return $this->type = 'spreadsheet';
        }
        if ($this->matchExtensions(['gz', '7z', 'bz2', 'rar', 'tar', 'zip'])) {
            return $this->type = 'archive';
        }
        return null;
    }

    /**
     * Get file size
     */
    public function size(): string
    {
        return $this->size ??= FileSystem::formatSize(FileSystem::fileSize($this->path));
    }

    /**
     * Get file last modified time
     */
    public function lastModifiedTime(): int
    {
        return $this->lastModifiedTime ??= FileSystem::lastModifiedTime($this->path);
    }

    /**
     * Get file hash
     */
    public function hash(): string
    {
        return $this->hash ??= hash('sha256', "{$this->path}:{$this->lastModifiedTime()}");
    }

    /**
     * Get file content hash
     *
     * @throws RuntimeException If file hash calculation fails
     */
    public function contentHash(): string
    {
        if (isset($this->contentHash)) {
            return $this->contentHash;
        }
        if ($hash = hash_file('sha256', $this->path)) {
            return $this->contentHash = $hash;
        }
        throw new RuntimeException('Cannot calculate file hash');
    }

    /**
     * Set URI generator
     *
     * @internal
     */
    public function setUriGenerator(FileUriGenerator $uriGenerator): void
    {
        $this->uriGenerator = $uriGenerator;
    }

    /**
     * Get file URI
     *
     * @throws FileUriGenerationException If URI generator is not set
     */
    public function uri(): string
    {
        if (!isset($this->uriGenerator)) {
            throw new FileUriGenerationException('Cannot generate file uri: generator not set');
        }
        return $this->uriGenerator->generate($this);
    }

    /**
     * Get file absolute URI
     *
     * @throws FileUriGenerationException If URI generator is not set
     */
    public function absoluteUri(): string
    {
        if (!isset($this->uriGenerator)) {
            throw new FileUriGenerationException('Cannot generate file absolute uri: generator not set');
        }
        return $this->uriGenerator->generateAbsolute($this);
    }

    public function toArray(): array
    {
        return [
            'path'             => $this->path,
            'name'             => $this->name,
            'extension'        => $this->extension,
            'type'             => $this->type(),
            'size'             => $this->size(),
            'lastModifiedTime' => $this->lastModifiedTime(),
        ];
    }

    /**
     * Set file scheme
     */
    public function setScheme(Scheme $scheme): void
    {
        $this->scheme = $scheme;
        $this->fields = $scheme->fields();
    }

    /**
     * Match MIME type with an array of extensions
     *
     * @param list<string> $extensions
     */
    private function matchExtensions(array $extensions): bool
    {
        $mimeTypes = array_map(
            static fn(string $extension): string => MimeType::fromExtension($extension),
            $extensions
        );
        return in_array($this->mimeType, $mimeTypes, true);
    }
}
