<?php

namespace Formwork\Http\Utils;

use Formwork\Http\ResponseStatus;
use Formwork\Http\ResponseStatusType;
use Formwork\Traits\StaticClass;
use InvalidArgumentException;
use RuntimeException;

final class Header
{
    use StaticClass;

    /**
     * Send an HTTP response status code
     */
    public static function sendStatus(ResponseStatus $responseStatus): void
    {
        $protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
        $responseStatus = implode(' ', [$protocol, $responseStatus->value]);
        header($responseStatus);
    }

    /**
     * Send an HTTP response header
     *
     * @param bool $replace Whether to replace headers with the same name
     */
    public static function send(string $fieldName, string $fieldValue, bool $replace = true): void
    {
        if (headers_sent()) {
            throw new RuntimeException(sprintf('Cannot send %s header, HTTP headers already sent', $fieldName));
        }
        header($fieldName . ': ' . trim($fieldValue), $replace);
    }

    /**
     * Set Content-Type header
     */
    public static function contentType(string $mimeType): void
    {
        self::send('Content-Type', $mimeType);
    }

    /**
     * Send HTTP 404 Not Found status
     */
    public static function notFound(): void
    {
        self::sendStatus(ResponseStatus::NotFound);
    }

    /**
     * Redirect to a given URI and exit from the script
     *
     * @param ResponseStatus $responseStatus Redirect HTTP response status code
     */
    public static function redirect(string $uri, ResponseStatus $responseStatus = ResponseStatus::Found): never
    {
        if ($responseStatus->type() !== ResponseStatusType::Redirection) {
            throw new InvalidArgumentException(sprintf('Invalid response status "%s" for redirection, only 3XX statuses are allowed', $responseStatus->value));
        }
        self::sendStatus($responseStatus);
        self::send('Location', $uri);
        exit;
    }

    /**
     * Make header content
     *
     * @param array<int|string, string> $data
     */
    public static function make(array $data): string
    {
        $parts = [];
        foreach ($data as $key => $value) {
            $parts[] = is_int($key) ? $value : "{$key}={$value}";
        }
        return implode('; ', $parts);
    }
}
