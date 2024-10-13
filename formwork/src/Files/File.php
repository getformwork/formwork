<?php

namespace Formwork\Files;

use Formwork\App;
use Formwork\Data\Contracts\Arrayable;
use Formwork\Files\Exceptions\FileUriGenerationException;
use Formwork\Model\Attributes\ReadonlyModelProperty;
use Formwork\Model\Model;
use Formwork\Parsers\Yaml;
use Formwork\Utils\FileSystem;
use Formwork\Utils\MimeType;
use Formwork\Utils\Str;
use RuntimeException;
use Stringable;

class File extends Model implements Arrayable, Stringable
{
    protected const MODEL_IDENTIFIER = 'file';

    protected const SCHEME_IDENTIFIER = 'files.file';

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

    protected FileUriGenerator $uriGenerator;

    /**
     * Create a new File instance
     *
     * @param string $path File path
     */
    public function __construct(protected string $path)
    {
        $this->name = basename($path);
        $this->extension = FileSystem::extension($path);
        $this->loadData();
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
     * Get file type in a human-readable format
     */
    public function type(): ?string
    {
        if ($this->type !== null) {
            return $this->type;
        }
        if (Str::startsWith($this->mimeType(), 'image')) {
            return $this->type = 'image';
        }
        if (Str::startsWith($this->mimeType(), 'text')) {
            return $this->type = 'text';
        }
        if (Str::startsWith($this->mimeType(), 'audio')) {
            return $this->type = 'audio';
        }
        if (Str::startsWith($this->mimeType(), 'video')) {
            return $this->type = 'video';
        }
        if ($this->mimeType() === MimeType::fromExtension('pdf')) {
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
        if (isset($this->hash)) {
            return $this->hash;
        }
        if ($hash = hash_file('sha256', $this->path)) {
            return $this->hash = $hash;
        }
        throw new RuntimeException('Cannot calculate file hash');
    }

    public function setUriGenerator(FileUriGenerator $uriGenerator): void
    {
        $this->uriGenerator = $uriGenerator;
    }

    public function uri(): string
    {
        if (!isset($this->uriGenerator)) {
            throw new FileUriGenerationException('Cannot generate file uri: generator not set');
        }
        return $this->uriGenerator->generate($this);
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

    private function loadData(): void
    {
        $app = App::instance();

        $this->scheme = $app->schemes()->get(static::SCHEME_IDENTIFIER);
        $this->fields = $this->scheme->fields();

        $metadataFile = $this->path . $app->config()->get('system.files.metadataExtension');

        $this->data = FileSystem::exists($metadataFile) ? Yaml::parseFile($metadataFile) : [];

        $this->fields->setValues($this->data);
    }

    /**
     * Match MIME type with an array of extensions
     *
     * @param list<string> $extensions
     */
    private function matchExtensions(array $extensions): bool
    {
        $mimeTypes = array_map(
            static fn (string $extension): string => MimeType::fromExtension($extension),
            $extensions
        );
        return in_array($this->mimeType, $mimeTypes, true);
    }
}
