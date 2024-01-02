<?php

namespace Formwork\Files;

use Formwork\Config\Config;
use Formwork\Exceptions\TranslatedException;
use Formwork\Http\Files\UploadedFile;
use Formwork\Utils\Arr;
use Formwork\Utils\FileSystem;
use Formwork\Utils\MimeType;
use Formwork\Utils\Str;

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

    public function upload(UploadedFile $uploadedFile, string $destinationPath, ?string $name = null): File
    {
        $mimeType = MimeType::fromFile($uploadedFile->tempPath());

        if (!in_array($mimeType, $this->allowedMimeTypes(), true)) {
            throw new TranslatedException(sprintf('Invalid mime type %s for file uploads', $mimeType), 'upload.error.mimeType');
        }

        $filename = Str::slug($name ?? pathinfo($uploadedFile->clientName(), PATHINFO_FILENAME)) . '.' . MimeType::toExtension($mimeType);

        $uploadedFile->move($destinationPath, $filename);

        return new File(FileSystem::joinPaths($destinationPath, $filename));
    }
}
