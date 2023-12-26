<?php

namespace Formwork\Http;

use Formwork\Http\Utils\Header;
use Formwork\Utils\FileSystem;

class FileResponse extends Response
{
    /**
     * @inheritdoc
     */
    public function __construct(string $path, ResponseStatus $status = ResponseStatus::OK, array $headers = [], bool $download = false)
    {
        $headers += [
            'Content-Type'        => FileSystem::mimeType($path),
            'Content-Disposition' => !$download ? 'inline' : Header::make(['attachment', 'filename' => basename($path)]),
            'Content-Length'      => (string) FileSystem::fileSize($path),
        ];
        parent::__construct(FileSystem::read($path), $status, $headers);
    }

    /**
     * @inheritdoc
     */
    public function send(): void
    {
        parent::cleanOutputBuffers();
        parent::send();
    }
}
