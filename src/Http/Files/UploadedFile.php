<?php

namespace Formwork\Http\Files;

use Formwork\Exceptions\TranslatedException;
use Formwork\Utils\FileSystem;

class UploadedFile
{
    /**
     * Human-readable Uploader error messages
     *
     * @var array<int, string>
     */
    protected const array ERROR_MESSAGES = [
        UPLOAD_ERR_OK         => 'The file uploaded with success',
        UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
        UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION  => 'A Php extension stopped the file upload',
    ];

    /**
     * Uploader errors language strings
     *
     * @var array<int, string>
     */
    protected const array ERROR_TRANSLATION_STRINGS = [
        UPLOAD_ERR_INI_SIZE   => 'upload.error.size',
        UPLOAD_ERR_FORM_SIZE  => 'upload.error.size',
        UPLOAD_ERR_PARTIAL    => 'upload.error.partial',
        UPLOAD_ERR_NO_FILE    => 'upload.error.noFile',
        UPLOAD_ERR_NO_TMP_DIR => 'upload.error.noTemp',
        UPLOAD_ERR_CANT_WRITE => 'upload.error.cannotWrite',
        UPLOAD_ERR_EXTENSION  => 'upload.error.phpExtension',
    ];

    /**
     * Client file name
     */
    protected string $clientName;

    /**
     * Client file full path
     */
    protected string $clientFullPath;

    /**
     * MIME type sent by the client
     */
    protected string $clientMimeType;

    /**
     * Temporary file path
     */
    protected string $tempPath;

    /**
     * File size in bytes
     */
    protected int $size;

    /**
     * Uploader error code
     */
    protected int $error;

    /**
     * @param array{name: string, full_path: string, type: string, tmp_name: string, error: string, size: string} $data
     */
    public function __construct(
        protected string $fieldName,
        array $data,
    ) {
        $this->clientName = $data['name'];
        $this->clientFullPath = $data['full_path'];
        $this->clientMimeType = $data['type'];
        $this->tempPath = $data['tmp_name'];
        $this->error = (int) $data['error'];
        $this->size = (int) $data['size'];
    }

    /**
     * Get the name of the field used to upload the file
     */
    public function fieldName(): string
    {
        return $this->fieldName;
    }

    /**
     * Get the client file name
     */
    public function clientName(): string
    {
        return $this->clientName;
    }

    /**
     * Get the client file full path
     */
    public function clientFullPath(): string
    {
        return $this->clientFullPath;
    }

    /**
     * Get the MIME type sent by the client. Never trust this value without checking it
     */
    public function clientMimeType(): string
    {
        return $this->clientMimeType;
    }

    /**
     * Get the temporary file path
     */
    public function tempPath(): string
    {
        return $this->tempPath;
    }

    /**
     * Get the uploader error code
     */
    public function error(): int
    {
        return $this->error;
    }

    /**
     * Get the file size in bytes
     */
    public function size(): int
    {
        return $this->size;
    }

    /**
     * Return whether there is no file uploaded
     */
    public function isEmpty(): bool
    {
        return $this->error === UPLOAD_ERR_NO_FILE;
    }

    /**
     * Return whether the file has been uploaded
     */
    public function isUploaded(): bool
    {
        return $this->error === UPLOAD_ERR_OK;
    }

    /**
     * Get the human-readable error message
     */
    public function getErrorMessage(): string
    {
        return self::ERROR_MESSAGES[$this->error];
    }

    /**
     * Get the error translation string
     */
    public function getErrorTranslationString(): string
    {
        return self::ERROR_TRANSLATION_STRINGS[$this->error];
    }

    /**
     * Move the uploaded file to a destination
     *
     * @param string $destination Destination path
     * @param string $filename    Destination file name
     * @param bool   $overwrite   Whether to overwrite the file if it already exists
     */
    public function move(string $destination, string $filename, bool $overwrite = false): bool
    {
        if ($this->error !== UPLOAD_ERR_OK) {
            throw new TranslatedException(sprintf('Cannot upload file "%s": %s', $this->fieldName(), $this->getErrorMessage()), $this->getErrorTranslationString());
        }

        if (strlen($filename) > FileSystem::MAX_NAME_LENGTH) {
            throw new TranslatedException('File name too long', 'upload.error.fileNameTooLong');
        }

        $destinationPath = FileSystem::joinPaths($destination, $filename);

        if (strlen($destinationPath) > FileSystem::MAX_PATH_LENGTH) {
            throw new TranslatedException('Destination path too long', 'upload.error.destinationTooLong');
        }

        if (!$overwrite && FileSystem::exists($destinationPath)) {
            throw new TranslatedException(sprintf('File "%s" already exists', $filename), 'upload.error.alreadyExists');
        }

        if (move_uploaded_file($this->tempPath, $destinationPath)) {
            return true;
        }

        throw new TranslatedException('Cannot move uploaded file to destination', 'upload.error.cannotMoveToDestination');
    }
}
