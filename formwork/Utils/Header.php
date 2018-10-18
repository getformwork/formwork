<?php

namespace Formwork\Utils;

use LogicException;
use RuntimeException;

class Header
{
    /**
     * Associative array containing HTTP response status codes
     *
     * @see https://tools.ietf.org/html/rfc7231#section-6
     *
     * @var array
     */
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

    /**
     * Send an HTTP response status code
     *
     * @param int|string $code
     * @param bool       $send Whether to send status code or return
     * @param bool       $exit Whether to exit from the script after sending the status code
     *
     * @return bool|null
     */
    public static function status($code, $send = true, $exit = false)
    {
        if (!isset(static::$statuses[$code])) {
            throw new LogicException('Unknown HTTP status code ' . $code);
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

    /**
     * Send an HTTP response header
     *
     * @param string $fieldName
     * @param string $fieldValue
     * @param bool   $replace    Whether to replace headers with the same name
     */
    public static function send($fieldName, $fieldValue, $replace = true)
    {
        if (headers_sent()) {
            throw new RuntimeException('Cannot send ' . $fieldName . ' header, HTTP headers already sent');
        }
        header($fieldName . ': ' . trim($fieldValue), $replace);
    }

    /**
     * Set Content-Type header
     *
     * @param string $mimeType
     */
    public static function contentType($mimeType)
    {
        static::send('Content-Type', $mimeType);
    }

    /**
     * Send HTTP 404 Not Found status
     */
    public static function notFound()
    {
        static::status(404);
    }

    /**
     * Redirect to a given URI and exit from the script
     *
     * @param string $uri
     * @param int    $code Redirect HTTP response status code
     */
    public static function redirect($uri, $code = 302)
    {
        static::status($code);
        static::send('Location', $uri);
        exit;
    }
}
