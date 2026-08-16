<?php

namespace Formwork\Http\Utils;

use Formwork\Traits\StaticClass;
use Formwork\Utils\Arr;
use InvalidArgumentException;

final class Cookie
{
    use StaticClass;

    /**
     * 'Strict' value for SameSite attribute
     */
    public const string SAMESITE_STRICT = 'Strict';

    /**
     * 'Lax' value for SameSite attribute
     */
    public const string SAMESITE_LAX = 'Lax';

    /**
     * 'None' value for SameSite attribute
     */
    public const string SAMESITE_NONE = 'None';

    /**
     * Regex pattern for invalid cookie name characters
     */
    private const string INVALID_NAME_CHARACTERS = '/[\x00-\x20.()<>@,;:\"\/[\]?={}]|[^\x21-\x7e]/';

    /**
     * Send a cookie
     *
     * @param array{expires?: int, path?: string, domain?: string, secure?: bool, httpOnly?: bool, sameSite?: self::SAMESITE_LAX|self::SAMESITE_NONE|self::SAMESITE_STRICT} $options
     */
    public static function send(string $name, string $value, array $options = []): bool
    {
        $options = [...self::defaults(), ...$options];

        self::validateName($name);

        self::removeSetCookieHeader($name);

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
        self::validateName($name);

        if ($forceSend || isset($_COOKIE[$name])) {
            return self::send($name, '', [...self::defaults(), ...$options, 'expires' => time() - 3600]);
        }

        return self::removeSetCookieHeader($name) !== null;
    }

    /**
     * Validate a cookie name
     */
    private static function validateName(string $name): true
    {
        if (preg_match(self::INVALID_NAME_CHARACTERS, $name, $matches, PREG_OFFSET_CAPTURE)) {
            [$character, $position] = $matches[0];
            throw new InvalidArgumentException(sprintf('Invalid cookie name "%s", unexpected character "%s" at position %d', $name, $character, $position));
        }

        return true;
    }

    /**
     * Remove a 'Set-Cookie' header from headers list
     */
    private static function removeSetCookieHeader(string $name): ?string
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

        // @phpstan-ignore nullCoalesce.unnecessary
        return $result ?? null;
    }

    /**
     * Return an array containing the default cookie attributes
     *
     * @return array{expires: int, path: string, domain: string, secure: bool, httpOnly: bool, sameSite: self::SAMESITE_LAX|self::SAMESITE_NONE|self::SAMESITE_STRICT}
     */
    private static function defaults(): array
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
