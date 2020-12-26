<?php

namespace Formwork\Utils;

use RuntimeException;

class HTTPResponse
{
    /**
     * Return an array containing response headers
     */
    public static function headers(): array
    {
        $headers = [];
        foreach (headers_list() as $header) {
            [$key, $value] = explode(':', $header, 2);
            $headers[$key] = trim($value);
        }
        return $headers;
    }

    /**
     * Put file data into response content
     *
     * @param bool $download Whether to download file or not
     */
    public static function file(string $file, bool $download = false): void
    {
        $data = FileSystem::read($file);
        $mimeType = FileSystem::mimeType($file);
        Header::contentType($mimeType);
        if ($download) {
            Header::send('Content-Disposition', 'attachment; filename="' . basename($file) . '"');
        }
        static::cleanOutputBuffers(); // Clean output buffers to prevent displayed file alteration
        echo $data;
        exit;
    }

    /**
     * Put file data into response content and tell the browser to download
     *
     * @see HTTPResponse::file()
     */
    public static function download(string $file): void
    {
        static::file($file, true);
    }

    /**
     * Clean all output buffers which were not sent
     */
    public static function cleanOutputBuffers(): void
    {
        while (ob_get_level()) {
            ob_end_clean();
        }
    }
}
