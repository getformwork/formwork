<?php

namespace Formwork\Utils;

use LogicException;

class Header
{
    public static $statuses = array(
        // Informational
        '100' => 'Continue',
        '101' => 'Switching Protocols',

        // Successful
        '200' => 'OK',
        '201' => 'Created',
        '202' => 'Accepted',
        '203' => 'Non-Authoritative Information',
        '204' => 'No Content',
        '205' => 'Reset Content',
        '206' => 'Partial Content',

        // Redirection
        '300' => 'Multiple Choices',
        '301' => 'Moved Permanently',
        '302' => 'Found',
        '303' => 'See Other',
        '304' => 'Not Modified',
        '305' => 'Use Proxy',
        '307' => 'Temporary Redirect',

        // Client Error
        '400' => 'Bad Request',
        '401' => 'Unauthorized',
        '402' => 'Payment Required',
        '403' => 'Forbidden',
        '404' => 'Not Found',
        '405' => 'Method Not Allowed',
        '406' => 'Not Acceptable',
        '407' => 'Proxy Authentication Required',
        '408' => 'Request Timeout',
        '409' => 'Conflict',
        '410' => 'Gone',
        '411' => 'Length Required',
        '412' => 'Precondition Failed',
        '413' => 'Payload Too Large',
        '414' => 'URI Too Long',
        '415' => 'Unsupported Media Type',
        '416' => 'Range Not Satisfiable',
        '417' => 'Expectation Failed',
        '426' => 'Upgrade Required',

        // Server Error
        '500' => 'Internal Server Error',
        '501' => 'Not Implemented',
        '502' => 'Bad Gateway',
        '503' => 'Service Unavailable',
        '504' => 'Gateway Timeout',
        '505' => 'HTTP Version Not Supported'
    );

    public static function status($code, $send = true, $exit = false)
    {
        if (!isset(static::$statuses[$code])) {
            throw new LogicException('Unknown HTTP status code');
        }
        $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
        $status = $protocol . ' ' . $code . ' ' . static::$statuses[$code];
        if (!$send) {
            return $status;
        }
        header($status);
        if ($exit) {
            exit;
        }
    }

    public static function send($fieldName, $fieldValue)
    {
        header($fieldName . ': ' . trim($fieldValue));
    }

    public static function contentType($mimeType)
    {
        static::send('Content-Type', $mimeType);
    }

    public static function file($file, $download = false)
    {
        FileSystem::assert($file);
        $mimeType = FileSystem::mimeType($file);
        static::contentType($mimeType);
        if ($download) {
            static::send('Content-Disposition', 'attachment; filename="' . FileSystem::basename($file) . '"');
        }
        ob_end_clean(); // Clean output buffer to prevent displayed file alteration
        readfile($file);
        exit;
    }

    public static function download($file)
    {
        static::file($file, true);
    }

    public static function notFound()
    {
        static::status(404);
    }

    public static function redirect($uri, $code = 302, $exit = false)
    {
        static::status($code);
        static::send('Location', $uri);
        if ($exit) {
            exit;
        }
    }
}
