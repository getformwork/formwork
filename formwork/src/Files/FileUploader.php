<?php

namespace Formwork\Files;

use Formwork\Exceptions\TranslatedException;
use Formwork\Http\Files\UploadedFile;
use Formwork\Utils\FileSystem;
use Formwork\Utils\MimeType;
use Formwork\Utils\Str;

class FileUploader
{
    /**
     * @param list<string> $allowedMimeTypes
     */
    public function __construct(protected array $allowedMimeTypes)
    {
    }

    /**
     * @return array<string>
     */
    public function allowedMimeTypes(): array
    {
        return $this->allowedMimeTypes;
    }

    public function upload(UploadedFile $uploadedFile, string $destinationPath, ?string $name = null, bool $overwrite = false): File
    {
        $mimeType = MimeType::fromFile($uploadedFile->tempPath());

        if (!in_array($mimeType, $this->allowedMimeTypes, true)) {
            throw new TranslatedException(sprintf('Invalid mime type %s for file uploads', $mimeType), 'upload.error.mimeType');
        }

        $filename = Str::slug($name ?? pathinfo($uploadedFile->clientName(), PATHINFO_FILENAME)) . '.' . MimeType::toExtension($mimeType);

        $uploadedFile->move($destinationPath, $filename, $overwrite);

        return new File(FileSystem::joinPaths($destinationPath, $filename));
    }
}
