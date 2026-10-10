<?php

namespace Formwork\Http;

enum RequestMethod: string
{
    case HEAD = 'HEAD';
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case PATCH = 'PATCH';
    case DELETE = 'DELETE';

    /**
     * Return whether the HTTP method is considered safe (does not modify server state)
     */
    public function isSafe(): bool
    {
        return match ($this) {
            self::GET, self::HEAD => true,
            default               => false,
        };
    }

    /**
     * Return whether the HTTP method is considered idempotent (multiple identical requests have the same effect as a single request)
     */
    public function isIdempotent(): bool
    {
        return match ($this) {
            self::GET, self::HEAD, self::PUT, self::DELETE => true,
            default                                        => false,
        };
    }

    /**
     * Return whether the HTTP method is considered cacheable (responses to the method can be cached)
     */
    public function isCacheable(): bool
    {
        return match ($this) {
            self::GET, self::HEAD => true,
            default               => false,
        };
    }
}
