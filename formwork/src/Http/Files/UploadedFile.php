<?php

namespace Formwork\Http\Files;

use Formwork\Exceptions\TranslatedException;
use Formwork\Utils\FileSystem;

class UploadedFile
{
    /**
     * Human-readable Uploader error messages
     */
    protected const ERROR_MESSAGES = [
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
     */
    protected const ERROR_TRANSLATION_STRINGS = [
        UPLOAD_ERR_INI_SIZE   => 'upload.error.size',
        UPLOAD_ERR_FORM_SIZE  => 'upload.error.size',
        UPLOAD_ERR_PARTIAL    => 'upload.error.partial',
        UPLOAD_ERR_NO_FILE    => 'upload.error.noFile',
        UPLOAD_ERR_NO_TMP_DIR => 'upload.error.noTemp',
        UPLOAD_ERR_CANT_WRITE => 'upload.error.cannotWrite',
        UPLOAD_ERR_EXTENSION  => 'upload.error.phpExtension',
    ];

    protected string $clientName;

    protected string $clientFullPath;

    protected string $clientMimeType;

    protected string $tempPath;

    protected int $size;

    protected int $error;

    /**
     * @param array{name: string, full_path: string, type: string, tmp_name: string, error: string, size: string} $data
     */
    public function __construct(protected string $fieldName, array $data)
    {
        $this->clientName = $data['name'];
        $this->clientFullPath = $data['full_path'];
        $this->clientMimeType = $data['type'];
        $this->tempPath = $data['tmp_name'];
        $this->error = (int) $data['error'];
        $this->size = (int) $data['size'];
    }

    public function fieldName(): string
    {
        return $this->fieldName;
    }

    public function clientName(): string
    {
        return $this->clientName;
    }

    public function clientFullPath(): string
    {
        return $this->clientFullPath;
    }

    public function clientMimeType(): string
    {
        return $this->clientMimeType;
    }

    public function tempPath(): string
    {
        return $this->tempPath;
    }

    public function error(): int
    {
        return $this->error;
    }

    public function size(): int
    {
        return $this->size;
    }

    public function isEmpty(): bool
    {
        return $this->error === UPLOAD_ERR_NO_FILE;
    }

    public function isUploaded(): bool
    {
        return $this->error === UPLOAD_ERR_OK;
    }

    public function getErrorMessage(): string
    {
        return self::ERROR_MESSAGES[$this->error];
    }

    public function getErrorTranslationString(): string
    {
        return self::ERROR_TRANSLATION_STRINGS[$this->error];
    }

    public function move(string $destination, string $filename, bool $overwrite = false): bool
    {
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
