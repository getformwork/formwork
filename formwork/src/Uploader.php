<?php

namespace Formwork;

use Exception;
use Formwork\Files\File;
use Formwork\Http\Files\UploadedFile;
use Formwork\Utils\Arr;
use Formwork\Utils\FileSystem;
use Formwork\Utils\MimeType;
use Formwork\Utils\Str;

class Uploader
{
    public function __construct(protected Config $config)
    {

    }

    public function allowedMimeTypes(): array
    {
        return Arr::map($this->config->get('system.files.allowedExtensions'), fn (string $ext) => MimeType::fromExtension($ext));
    }

    public function upload(UploadedFile $file, string $destinationPath, ?string $name = null): File
    {
        $mimeType = MimeType::fromFile($file->tempPath());

        if (!in_array($mimeType, $this->allowedMimeTypes(), true)) {
            throw new Exception('Invalid mime tpye');
        }

        $filename = Str::slug($name ?? pathinfo($file->clientName(), PATHINFO_FILENAME)) . '.' . MimeType::toExtension($mimeType);

        $file->move($destinationPath, $filename);

        return new File(FileSystem::joinPaths($destinationPath, $filename));
    }
}
