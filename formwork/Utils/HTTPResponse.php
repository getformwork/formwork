<?php

namespace Formwork\Utils;

class HTTPResponse
{
    /**
     * Array containing HTTP response headers
     *
     * @var array
     */
    protected static $headers = array();

    /**
     * Return an array containing response headers
     *
     * @return array
     */
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

    /**
     * Put file data into response content
     *
     * @param string $file
     * @param bool   $download Whether to download file or not
     */
    public static function file($file, $download = false)
    {
        FileSystem::assert($file);
        $mimeType = FileSystem::mimeType($file);
        Header::contentType($mimeType);
        if ($download) {
            Header::send('Content-Disposition', 'attachment; filename="' . basename($file) . '"');
        }
        static::cleanOutputBuffers(); // Clean output buffers to prevent displayed file alteration
        readfile($file);
        exit;
    }

    /**
     * Put file data into response content and tell the browser to download
     *
     * @param string $file
     *
     * @see HTTPResponse::file()
     */
    public static function download($file)
    {
        static::file($file, true);
    }

    /**
     * Clean all output buffers which were not sent
     */
    public static function cleanOutputBuffers()
    {
        while (ob_get_level()) {
            ob_end_clean();
        }
    }
}
