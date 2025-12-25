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
use Formwork\Utils\Path;
use Formwork\Utils\Str;

class FileUploader
{
    /**
     * @var array<string>
     */
    protected array $allowedMimeTypes;

    /**
     * @var array<string>
     */
    protected array $baseDestinations;

    public function __construct(
        protected Config $config,
        protected FileFactory $fileFactory,
    ) {
        $this->allowedMimeTypes = Arr::map($this->config->get('system.files.allowedExtensions'), fn(string $ext) => MimeType::fromExtension($ext));
        $this->baseDestinations = $this->config->get('system.files.uploads.baseDestinations');
    }

    /**
     * Get allowed MIME types
     *
     * @return array<string>
     */
    public function allowedMimeTypes(): array
    {
        return $this->allowedMimeTypes;
    }

    /**
     * Upload a file to the destination and return the corresponding File instance
     *
     * @param ?array<string> $allowedMimeTypes
     */
    public function upload(UploadedFile $uploadedFile, string $destinationPath, ?string $name = null, ?array $allowedMimeTypes = null, bool $overwrite = false): File
    {
        $mimeType = MimeType::fromFile($uploadedFile->tempPath());

        $allowedMimeTypes ??= $this->allowedMimeTypes;

        if (!in_array($mimeType, $allowedMimeTypes, true)) {
            throw new TranslatedException(sprintf('Invalid mime type %s for file uploads', $mimeType), 'upload.error.mimeType');
        }

        $resolvedPath = FileSystem::resolvePath($destinationPath);

        if (!Arr::some($this->baseDestinations, fn(string $basePath) => Path::isRelativeTo($resolvedPath, FileSystem::resolvePath($basePath)))) {
            throw new TranslatedException('Invalid destination path', 'upload.error.cannotMoveToDestination');
        }

        $clientExtension = FileSystem::extension($uploadedFile->clientName());

        $extension = in_array($clientExtension, MimeType::getAssociatedExtensions($mimeType), true)
            ? $clientExtension
            : MimeType::toExtension($mimeType);

        $filename = Str::slug($name ?? pathinfo($uploadedFile->clientName(), PATHINFO_FILENAME)) . ".{$extension}";

        $uploadedFile->move($destinationPath, $filename, $overwrite);

        $file = $this->fileFactory->make(FileSystem::joinPaths($destinationPath, $filename));

        if ($file instanceof Image) {
            switch ($file->mimeType()) {
                case 'image/jpeg':
                case 'image/png':
                case 'image/webp':
                case 'image/avif':
                    // Process JPEG, PNG, WebP and AVIF images according to system options (e.g. quality)
                    if ($this->config->get('system.uploads.processImages') && !$file->info()->isAnimation()) {
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
