<?php

namespace Formwork\Tests\Unit\Panel\Fixtures;

use Formwork\Exceptions\TranslatedException;
use Formwork\Http\Files\UploadedFile;
use Formwork\Utils\FileSystem;

/**
 * Uploaded file which can be moved outside of a real upload, as `move_uploaded_file()` accepts only real ones
 */
final class MovableUploadedFileFixture extends UploadedFile
{
    /**
     * Create a file uploaded without errors
     */
    public static function from(string $fieldName, string $tempPath, string $clientName, string $mimeType): self
    {
        return new self($fieldName, [
            'name'      => $clientName,
            'full_path' => $clientName,
            'type'      => $mimeType,
            'tmp_name'  => $tempPath,
            'error'     => (string) UPLOAD_ERR_OK,
            'size'      => (string) FileSystem::size($tempPath),
        ]);
    }

    public function move(string $destination, string $filename, bool $overwrite = false): bool
    {
        if ($this->error !== UPLOAD_ERR_OK) {
            throw new TranslatedException(sprintf('Cannot upload file "%s": %s', $this->fieldName(), $this->getErrorMessage()), $this->getErrorTranslationString());
        }

        $destinationPath = FileSystem::joinPaths($destination, $filename);

        if (!$overwrite && FileSystem::exists($destinationPath)) {
            throw new TranslatedException(sprintf('File "%s" already exists', $filename), 'upload.error.alreadyExists');
        }

        return rename($this->tempPath, $destinationPath);
    }
}
