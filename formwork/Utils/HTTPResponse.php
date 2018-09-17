<?php

namespace Formwork\Utils;

class HTTPResponse
{
    protected static $headers = array();

    public static function headers()
    {
        if (!empty(static::$headers)) {
            return static::$headers;
        }
        foreach (headers_list() as $header) {
            list($key, $value) = explode(':', $header, 2);
            static::$headers[$key] = trim($value);
        }
        return static::$headers;
    }

    public static function file($file, $download = false)
    {
        FileSystem::assert($file);
        $mimeType = FileSystem::mimeType($file);
        Header::contentType($mimeType);
        if ($download) {
            Header::send('Content-Disposition', 'attachment; filename="' . FileSystem::basename($file) . '"');
        }
        static::cleanOutputBuffers(); // Clean output buffers to prevent displayed file alteration
        readfile($file);
        exit;
    }

    public static function download($file)
    {
        static::file($file, true);
    }

    public static function cleanOutputBuffers()
    {
        while (ob_get_level()) {
            ob_end_clean();
        }
    }
}
