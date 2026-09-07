<?php

namespace Formwork\Http;

use Formwork\Http\Files\UploadedFile;
use Formwork\Utils\Arr;

class FilesData extends RequestData
{
    /**
     * Return whether all uploaded files are empty
     */
    public function isEmpty(): bool
    {
        if (parent::isEmpty()) {
            return true;
        }
        return Arr::every($this->getAll(), fn(UploadedFile $uploadedFile) => $uploadedFile->isEmpty());
    }

    /**
     * Get all uploaded files
     *
     * @return array<UploadedFile>
     */
    public function getAll(): array
    {
        return Arr::flatten($this->data);
    }
}
