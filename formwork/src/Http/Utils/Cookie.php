<?php

namespace Formwork\Http\Utils;

use Formwork\Traits\StaticClass;
use Formwork\Utils\Arr;
use InvalidArgumentException;

class Cookie
{
    use StaticClass;

    /**
     * 'Strict' value for SameSite attribute
     */
    public const SAMESITE_STRICT = 'Strict';

    /**
     * 'Lax' value for SameSite attribute
     */
    public const SAMESITE_LAX = 'Lax';

    public const SAMESITE_NONE = 'None';

    protected const INVALID_NAME_CHARACTERS = '/[\x00-\x20.()<>@,;:\"\/[\]?={}]|[^\x21-\x7e]/';

    /**
     * Send a cookie
     *
     * @param array{expires?: int, path?: string, domain?: string, secure?: bool, httpOnly?: bool, sameSite?: self::SAMESITE_LAX|self::SAMESITE_NONE|self::SAMESITE_STRICT} $options
     */
    public static function send(string $name, string $value, array $options = []): bool
    {
        $options = [...static::defaults(), ...$options];

        static::validateName($name);

        static::removeSetCookieHeader($name);

        return setcookie($name, $value, [
            'expires'  => $options['expires'],
            'path'     => $options['path'],
            'domain'   => $options['domain'],
            'secure'   => $options['secure'],
            'httponly' => $options['httpOnly'],
            'samesite' => $options['sameSite'],
        ]);
    }

    /**
     * Remove a cookie
     *
     * @param array{expires?: int, path?: string, domain?: string, secure?: bool, httpOnly?: bool, sameSite?: self::SAMESITE_LAX|self::SAMESITE_NONE|self::SAMESITE_STRICT} $options
     */
    public static function remove(string $name, array $options = [], bool $forceSend = false): bool
    {
        static::validateName($name);

        if ($forceSend || isset($_COOKIE[$name])) {
            return static::send($name, '', [...static::defaults(), ...$options, 'expires' => time() - 3600]);
        }

        return static::removeSetCookieHeader($name) !== null;
    }

    protected static function validateName(string $name): bool
    {
        if (preg_match(self::INVALID_NAME_CHARACTERS, $name, $matches, PREG_OFFSET_CAPTURE)) {
            [$character, $position] = $matches[0];
            throw new InvalidArgumentException(sprintf('Invalid cookie name "%s", unexpected character "%s" at position %d', $name, $character, $position));
        }

        return true;
    }

    protected static function removeSetCookieHeader(string $name): ?string
    {
        $cookies = Arr::filter(headers_list(), function ($header) use ($name, &$result) {
            if (preg_match('/^Set-Cookie: (?<name>[^=]+)=/', $header, $matches, PREG_UNMATCHED_AS_NULL)) {
                if ($matches['name'] === $name) {
                    $result = $name;
                    return false;
                }
                return true;
            }
            return false;
        });

        header_remove('Set-Cookie');

        foreach ($cookies as $cookie) {
            header($cookie);
        }

        return $result ?? null;
    }

    /**
     * Return an array containing the default cookie attributes
     *
     * @return array{expires: int, path: string, domain: string, secure: bool, httpOnly: bool, sameSite: self::SAMESITE_LAX|self::SAMESITE_NONE|self::SAMESITE_STRICT}
     */
    protected static function defaults(): array
    {
        return [
            'expires'  => 0,
            'domain'   => '',
            'path'     => '',
            'secure'   => false,
            'httpOnly' => false,
            'sameSite' => self::SAMESITE_LAX,
        ];
    }
}
