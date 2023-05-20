<?php

namespace Formwork\Response;

use Formwork\Utils\FileSystem;
use Formwork\Utils\Header;
use Formwork\Utils\HTTPResponse;

class FileResponse extends Response
{
    /**
     * @inheritdoc
     */
    public function __construct(string $path, bool $download = false, int $status = 200, array $headers = [])
    {
        $headers += [
            'Content-Type'        => FileSystem::mimeType($path),
            'Content-Disposition' => !$download ? 'inline' : Header::make(['attachment', 'filename' => basename($path)]),
            'Content-Length'      => FileSystem::fileSize($path),
        ];
        parent::__construct(FileSystem::read($path), $status, $headers);
    }

    /**
     * @inheritdoc
     */
    public function send(): void
    {
        HTTPResponse::cleanOutputBuffers();
        parent::send();
    }
}
