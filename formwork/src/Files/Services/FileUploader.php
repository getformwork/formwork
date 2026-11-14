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
use InvalidArgumentException;
use UnexpectedValueException;

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
        $this->allowedMimeTypes = Arr::map($this->config->getArray('system.files.allowedExtensions', []), fn(string $ext) => MimeType::fromExtension($ext));
        $this->baseDestinations = $this->config->getArray('system.files.uploads.baseDestinations', []);
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
        $mimeType = $this->validateUploadedFile($uploadedFile, $allowedMimeTypes ?? $this->allowedMimeTypes);

        $clientExtension = FileSystem::extension($uploadedFile->clientName());

        $extension = in_array($clientExtension, MimeType::getAssociatedExtensions($mimeType), true)
            ? $clientExtension
            : MimeType::toExtension($mimeType);

        if (($slug = Str::slug($name ?? pathinfo($uploadedFile->clientName(), PATHINFO_FILENAME))) === '') {
            throw new TranslatedException('Invalid file name', 'upload.error.fileName');
        }

        return $this->store($uploadedFile, $destinationPath, "{$slug}.{$extension}", $mimeType, $overwrite);
    }

    /**
     * Replace the given file with an uploaded one of the same MIME type, keeping its name
     */
    public function replace(UploadedFile $uploadedFile, File $file): File
    {
        $mimeType = $this->validateUploadedFile($uploadedFile, [$file->mimeType()]);

        return $this->store($uploadedFile, dirname($file->path()), basename($file->path()), $mimeType, overwrite: true);
    }

    /**
     * Validate an uploaded file and return its MIME type
     *
     * @param array<string> $allowedMimeTypes
     *
     * @throws TranslatedException If the file was not uploaded or its MIME type is not allowed
     */
    protected function validateUploadedFile(UploadedFile $uploadedFile, array $allowedMimeTypes): string
    {
        if (!$uploadedFile->isUploaded()) {
            throw new TranslatedException(sprintf('Cannot upload file "%s": %s', $uploadedFile->fieldName(), $uploadedFile->getErrorMessage()), $uploadedFile->getErrorTranslationString());
        }

        $mimeType = MimeType::fromFile($uploadedFile->tempPath());

        if (!in_array($mimeType, $allowedMimeTypes, true)) {
            throw new TranslatedException(sprintf('Invalid mime type %s for file uploads', $mimeType), 'upload.error.mimeType');
        }

        return $mimeType;
    }

    /**
     * Move an uploaded file to the destination with the given filename and return the corresponding File instance
     *
     * @throws TranslatedException If the destination is not allowed
     */
    protected function store(UploadedFile $uploadedFile, string $destinationPath, string $filename, string $mimeType, bool $overwrite): File
    {
        $resolvedPath = FileSystem::resolvePath($destinationPath);

        if (!Arr::some($this->baseDestinations, fn(string $basePath) => Path::isRelativeTo($resolvedPath, FileSystem::resolvePath($basePath)))) {
            throw new TranslatedException('Invalid destination path', 'upload.error.cannotMoveToDestination');
        }

        // Sanitize SVG images before they are moved, so that unsafe data never reaches the destination
        if ($mimeType === 'image/svg+xml') {
            $this->sanitizeSvg($uploadedFile->tempPath());
        }

        $uploadedFile->move($destinationPath, $filename, $overwrite);

        $file = $this->fileFactory->make(FileSystem::joinPaths($destinationPath, $filename));

        // Process JPEG, PNG, WebP and AVIF images according to system options (e.g. quality)
        if (
            $file instanceof Image
            && in_array($file->mimeType(), ['image/jpeg', 'image/png', 'image/webp', 'image/avif'], true)
            && $this->config->getBool('system.uploads.processImages')
            && !$file->info()->isAnimation()
        ) {
            // The image is encoded again with the system options
            $file->saveAs($file->path());
        }

        return $file;
    }

    /**
     * Sanitize an SVG image in place
     *
     * @throws TranslatedException If the SVG image cannot be sanitized
     */
    protected function sanitizeSvg(string $path): void
    {
        try {
            $data = (new SvgSanitizer())->sanitize(FileSystem::read($path));
        } catch (InvalidArgumentException|UnexpectedValueException $e) {
            throw new TranslatedException(sprintf('Cannot sanitize SVG image: %s', $e->getMessage()), 'upload.error.invalidSvg', previous: $e);
        }

        FileSystem::write($path, $data);
    }
}
