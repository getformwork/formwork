<?php

namespace Formwork\Files\Services;

use Formwork\Config\Config;
use Formwork\Exceptions\TranslatedException;
use Formwork\Files\File;
use Formwork\Files\FileFactory;
use Formwork\Http\Files\UploadedFile;
use Formwork\Images\Image;
use Formwork\Sanitizer\SvgSanitizer;
use Formwork\Utils\Arr;
use Formwork\Utils\FileSystem;
use Formwork\Utils\MimeType;
use Formwork\Utils\Str;

class FileUploader
{
    /**
     * @var list<string>
     */
    protected array $allowedMimeTypes;

    public function __construct(protected Config $config, protected FileFactory $fileFactory)
    {
        $this->allowedMimeTypes = Arr::map($this->config->get('system.files.allowedExtensions'), fn (string $ext) => MimeType::fromExtension($ext));
    }

    /**
     * @return list<string>
     */
    public function allowedMimeTypes(): array
    {
        return $this->allowedMimeTypes;
    }

    /**
     * @param ?list<string> $allowedMimeTypes
     */
    public function upload(UploadedFile $uploadedFile, string $destinationPath, ?string $name = null, bool $overwrite = false, ?array $allowedMimeTypes = null): File
    {
        $mimeType = MimeType::fromFile($uploadedFile->tempPath());

        $allowedMimeTypes ??= $this->allowedMimeTypes;

        if (!in_array($mimeType, $allowedMimeTypes, true)) {
            throw new TranslatedException(sprintf('Invalid mime type %s for file uploads', $mimeType), 'upload.error.mimeType');
        }

        $filename = Str::slug($name ?? pathinfo($uploadedFile->clientName(), PATHINFO_FILENAME)) . '.' . MimeType::toExtension($mimeType);

        $uploadedFile->move($destinationPath, $filename, $overwrite);

        $file = $this->fileFactory->make(FileSystem::joinPaths($destinationPath, $filename));

        if ($file instanceof Image) {
            switch ($file->mimeType()) {
                case 'image/jpeg':
                case 'image/png':
                case 'image/webp':
                    // Process JPEG, PNG and WebP images according to system options (e.g. quality)
                    if ($this->config->get('system.uploads.processImages')) {
                        $file->save();
                    }
                    break;

                case 'image/svg+xml':
                    // Sanitize SVG images
                    $svgSanitizer = new SvgSanitizer();
                    $data = FileSystem::read($file->path());
                    FileSystem::write($file->path(), $svgSanitizer->sanitize($data));
                    break;
            }
        }

        return $file;
    }
}
