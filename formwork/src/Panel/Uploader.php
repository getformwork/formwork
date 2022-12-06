<?php

namespace Formwork\Panel;

use Formwork\Exceptions\TranslatedException;
use Formwork\Files\FileCollection;
use Formwork\Formwork;
use Formwork\Utils\FileSystem;
use Formwork\Utils\HTTPRequest;
use Formwork\Utils\MimeType;

class Uploader
{
    /**
     * Human-readable Uploader error messages
     */
    protected const ERROR_MESSAGES = [
        UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
        UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload'
    ];

    /**
     * Uploader errors language strings
     */
    protected const ERROR_LANGUAGE_STRINGS = [
        UPLOAD_ERR_INI_SIZE   => 'panel.uploader.error.size',
        UPLOAD_ERR_FORM_SIZE  => 'panel.uploader.error.size',
        UPLOAD_ERR_PARTIAL    => 'panel.uploader.error.partial',
        UPLOAD_ERR_NO_FILE    => 'panel.uploader.error.noFile',
        UPLOAD_ERR_NO_TMP_DIR => 'panel.uploader.error.noTemp',
        UPLOAD_ERR_CANT_WRITE => 'panel.uploader.error.cannotWrite',
        UPLOAD_ERR_EXTENSION  => 'panel.uploader.error.phpExtension'
    ];

    /**
     * Destination of uploaded file
     */
    protected string $destination;

    /**
     * Uploader options
     */
    protected array $options = [];

    /**
     * Array containing uploaded files
     */
    protected FileCollection $uploadedFiles;

    /**
     * Create a new Uploader instance
     */
    public function __construct(string $destination, array $options = [])
    {
        $this->destination = FileSystem::normalizePath($destination);
        $this->options = array_merge($this->defaults(), $options);
    }

    /**
     * Return Uploader default options
     */
    public function defaults(): array
    {
        $mimeTypes = array_map(
            [MimeType::class, 'fromExtension'],
            Formwork::instance()->config()->get('files.allowedExtensions')
        );
        return [
            'allowedMimeTypes' => $mimeTypes,
            'overwrite'        => false,
        ];
    }

    /**
     * Upload one or more files
     *
     * @return bool Whether files were uploaded or not
     */
    public function upload(?string $name = null): bool
    {
        if (!HTTPRequest::hasFiles()) {
            return false;
        }

        $count = count(HTTPRequest::files());

        $filenames = [];

        foreach (HTTPRequest::files() as $file) {
            if ($file['error'] === 0) {
                if ($name === null || $count > 1) {
                    $name = $file['name'];
                }

                $filenames[] = $this->move($file['tmp_name'], $this->destination, $name);
            } else {
                throw new TranslatedException(self::ERROR_MESSAGES[$file['error']], self::ERROR_LANGUAGE_STRINGS[$file['error']]);
            }
        }

        $this->uploadedFiles = FileCollection::fromPath($this->destination, $filenames);

        return true;
    }

    /**
     * Return if a MIME type is allowed by Formwork
     */
    public function isAllowedMimeType(string $mimeType): bool
    {
        if ($this->options['allowedMimeTypes'] === null) {
            return true;
        }
        return in_array($mimeType, (array) $this->options['allowedMimeTypes'], true);
    }

    /**
     * Return uploaded files
     */
    public function uploadedFiles(): FileCollection
    {
        return $this->uploadedFiles;
    }

    /**
     * Move uploaded file to a destination
     */
    protected function move(string $source, string $destination, string $filename): string
    {
        $mimeType = FileSystem::mimeType($source);

        if (!$this->isAllowedMimeType($mimeType)) {
            throw new TranslatedException(sprintf('MIME type %s is not allowed', $mimeType), 'panel.uploader.error.mimeType');
        }

        if (basename($filename)[0] === '.') {
            throw new TranslatedException(sprintf('Hidden file "%s" not allowed', $filename), 'panel.uploader.error.hiddenFiles');
        }

        $name = str_replace([' ', '.'], '-', FileSystem::name($filename));
        $extension = strtolower(FileSystem::extension($filename));

        if (empty($extension)) {
            $extension = MimeType::toExtension($mimeType, false);
        }

        $filename = $name . '.' . $extension;

        if (strlen($filename) > FileSystem::MAX_NAME_LENGTH) {
            throw new TranslatedException('File name too long', 'panel.uploader.error.fileNameTooLong');
        }

        if (!(bool) preg_match('/^[a-z0-9_-]+(?:\.[aZ0-9]+)?$/i', $filename)) {
            throw new TranslatedException(sprintf('Invalid file name "%s"', $filename), 'panel.uploader.error.fileName');
        }

        $destinationPath = FileSystem::joinPaths($destination, $filename);

        if (strlen($destinationPath) > FileSystem::MAX_PATH_LENGTH) {
            throw new TranslatedException('Destination path too long', 'panel.uploader.error.destinationTooLong');
        }

        if (!$this->options['overwrite'] && FileSystem::exists($destinationPath)) {
            throw new TranslatedException(sprintf('File "%s" already exists', $filename), 'panel.uploader.error.alreadyExists');
        }

        if (@move_uploaded_file($source, $destinationPath)) {
            return $filename;
        }

        throw new TranslatedException('Cannot move uploaded file to destination', 'panel.uploader.error.cannotMoveToDestination');
    }
}
