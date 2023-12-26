<?php

namespace Formwork\Files;

use Formwork\Config\Config;
use Formwork\Http\Files\UploadedFile;
use Formwork\Utils\Arr;
use Formwork\Utils\FileSystem;
use Formwork\Utils\MimeType;
use Formwork\Utils\Str;
use RuntimeException;

class FileUploader
{
    public function __construct(protected Config $config)
    {

    }

    /**
     * @return array<string>
     */
    public function allowedMimeTypes(): array
    {
        return Arr::map($this->config->get('system.files.allowedExtensions'), fn (string $ext) => MimeType::fromExtension($ext));
    }

    public function upload(UploadedFile $file, string $destinationPath, ?string $name = null): File
    {
        $mimeType = MimeType::fromFile($file->tempPath());

        if (!in_array($mimeType, $this->allowedMimeTypes(), true)) {
            throw new RuntimeException(sprintf('Invalid mime type %s for file uploads', $mimeType));
        }

        $filename = Str::slug($name ?? pathinfo($file->clientName(), PATHINFO_FILENAME)) . '.' . MimeType::toExtension($mimeType);

        $file->move($destinationPath, $filename);

        return new File(FileSystem::joinPaths($destinationPath, $filename));
    }
}
