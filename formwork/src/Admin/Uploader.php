<?php

namespace Formwork\Admin;

use Formwork\Exceptions\TranslatedException;
use Formwork\Formwork;
use Formwork\Utils\FileSystem;
use Formwork\Utils\HTTPRequest;
use Formwork\Utils\MimeType;

class Uploader
{
    /**
     * Human-readable Uploader error messages
     *
     * @var array
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
     *
     * @var array
     */
    protected const ERROR_LANGUAGE_STRINGS = [
        UPLOAD_ERR_INI_SIZE   => 'admin.uploader.error.size',
        UPLOAD_ERR_FORM_SIZE  => 'admin.uploader.error.size',
        UPLOAD_ERR_PARTIAL    => 'admin.uploader.error.partial',
        UPLOAD_ERR_NO_FILE    => 'admin.uploader.error.no-file',
        UPLOAD_ERR_NO_TMP_DIR => 'admin.uploader.error.no-temp',
        UPLOAD_ERR_CANT_WRITE => 'admin.uploader.error.cannot-write',
        UPLOAD_ERR_EXTENSION  => 'admin.uploader.error.php-extension'
    ];

    /**
     * Destination of uploaded file
     *
     * @var string
     */
    protected $destination;

    /**
     * Uploader options
     *
     * @var array
     */
    protected $options = [];

    /**
     * Array containing uploaded files
     *
     * @var array
     */
    protected $uploadedFiles = [];

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
            Formwork::instance()->config()->get('files.allowed_extensions')
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
    public function upload(string $name = null): bool
    {
        if (!HTTPRequest::hasFiles()) {
            return false;
        }
        $count = count(HTTPRequest::files());

        foreach (HTTPRequest::files() as $file) {
            if ($file['error'] === 0) {
                if ($name === null || $count > 1) {
                    $name = $file['name'];
                }
                $this->move($file['tmp_name'], $this->destination, $name);
            } else {
                throw new TranslatedException(self::ERROR_MESSAGES[$file['error']], self::ERROR_LANGUAGE_STRINGS[$file['error']]);
            }
        }

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
    public function uploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    /**
     * Move uploaded file to a destination
     *
     * @return bool Whether file was successfully moved or not
     */
    protected function move(string $source, string $destination, string $filename): bool
    {
        $mimeType = FileSystem::mimeType($source);

        if (!$this->isAllowedMimeType($mimeType)) {
            throw new TranslatedException(sprintf('MIME type %s is not allowed', $mimeType), 'admin.uploader.error.mime-type');
        }

        if (basename($filename)[0] === '.') {
            throw new TranslatedException(sprintf('Hidden file "%s" not allowed', $filename), 'admin.uploader.error.hidden-files');
        }

        $name = str_replace([' ', '.'], '-', FileSystem::name($filename));
        $extension = strtolower(FileSystem::extension($filename));

        if (empty($extension)) {
            $extension = MimeType::toExtension($mimeType, false);
        }

        $filename = $name . '.' . $extension;

        if (strlen($filename) > FileSystem::MAX_NAME_LENGTH) {
            throw new TranslatedException('File name too long', 'admin.uploader.error.file-name-too-long');
        }

        if (!(bool) preg_match('/^[a-z0-9_-]+(?:\.[a-z0-9]+)?$/i', $filename)) {
            throw new TranslatedException(sprintf('Invalid file name "%s"', $filename), 'admin.uploader.error.file-name');
        }

        $destinationPath = FileSystem::joinPaths($destination, $filename);

        if (strlen($destinationPath) > FileSystem::MAX_PATH_LENGTH) {
            throw new TranslatedException('Destination path too long', 'admin.uploader.error.destination-too-long');
        }

        if (!$this->options['overwrite'] && FileSystem::exists($destinationPath)) {
            throw new TranslatedException(sprintf('File "%s" already exists', $filename), 'admin.uploader.error.already-exists');
        }

        if (@move_uploaded_file($source, $destinationPath)) {
            $this->uploadedFiles[] = $filename;
            return true;
        }

        throw new TranslatedException('Cannot move uploaded file to destination', 'admin.uploader.error.cannot-move-to-destination');

        return false;
    }
}
