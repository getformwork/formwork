<?php

namespace Formwork\Http\Utils;

use Formwork\Http\ResponseStatus;
use Formwork\Http\ResponseStatusType;
use Formwork\Traits\StaticClass;
use InvalidArgumentException;
use RuntimeException;

class Header
{
    use StaticClass;

    /**
     * Send an HTTP response status code
     *
     * @param bool $send Whether to send status code or return
     * @param bool $exit Whether to exit from the script after sending the status code
     *
     * @return string|void
     */
    public static function status(ResponseStatus $status, bool $send = true, bool $exit = false)
    {
        $protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0';
        $status = implode(' ', [$protocol, $status->value]);
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
            throw new RuntimeException(sprintf('Cannot send %d header, HTTP headers already sent', $fieldName));
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
        static::status(ResponseStatus::NotFound);
    }

    /**
     * Redirect to a given URI and exit from the script
     *
     * @param ResponseStatus $status Redirect HTTP response status code
     */
    public static function redirect(string $uri, ResponseStatus $status = ResponseStatus::Found): void
    {
        if ($status->type() !== ResponseStatusType::Redirection) {
            throw new InvalidArgumentException();
        }
        static::status($status);
        static::send('Location', $uri);
        exit;
    }

    /**
     * Make header content
     */
    public static function make(array $data): string
    {
        $parts = [];
        foreach ($data as $key => $value) {
            $parts[] = is_int($key) ? $value : $key . '=' . $value;
        }
        return implode('; ', $parts);
    }
}
