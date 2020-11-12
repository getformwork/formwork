<?php

namespace Formwork\Utils;

use LogicException;
use RuntimeException;

class Header
{
    /**
     * Associative array containing HTTP response status codes
     *
     * @see https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     *
     * @var array
     */
    public const HTTP_STATUS = [
        // Informational
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        103 => 'Early Hints',

        // Successful
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',

        // Redirection
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',

        // Client Error
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Too Early',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',

        // Server Error
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required'
    ];

    /**
     * Send an HTTP response status code
     *
     * @param bool $send Whether to send status code or return
     * @param bool $exit Whether to exit from the script after sending the status code
     *
     * @return string|void
     */
    public static function status(int $code, bool $send = true, bool $exit = false)
    {
        if (!isset(self::HTTP_STATUS[$code])) {
            throw new LogicException('Unknown HTTP status code ' . $code);
        }
        $protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0';
        $status = $protocol . ' ' . $code . ' ' . self::HTTP_STATUS[$code];
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
     * @param bool $replace Whether to replace headers with the same name
     */
    public static function send(string $fieldName, string $fieldValue, bool $replace = true): void
    {
        if (headers_sent()) {
            throw new RuntimeException('Cannot send ' . $fieldName . ' header, HTTP headers already sent');
        }
        header($fieldName . ': ' . trim($fieldValue), $replace);
    }

    /**
     * Set Content-Type header
     */
    public static function contentType(string $mimeType): void
    {
        static::send('Content-Type', $mimeType);
    }

    /**
     * Send HTTP 404 Not Found status
     */
    public static function notFound(): void
    {
        static::status(404);
    }

    /**
     * Redirect to a given URI and exit from the script
     *
     * @param int $code Redirect HTTP response status code
     */
    public static function redirect(string $uri, int $code = 302): void
    {
        static::status($code);
        static::send('Location', $uri);
        exit;
    }
}
