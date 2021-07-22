<?php

namespace Formwork\Utils;

use InvalidArgumentException;
use UnexpectedValueException;

class Cookie
{
    /**
     * 'Strict' value for SameSite directive
     */
    public const SAMESITE_STRICT = 'Strict';

    /**
     * 'Lax' value for SameSite directive
     */
    public const SAMESITE_LAX = 'Lax';

    /**
     * Send a cookie
     *
     * @param bool $replace Whether to replace existing Set-Cookie header
     */
    public static function send(string $name, string $value, array $options = [], bool $replace = false): void
    {
        $options = array_merge(static::defaults(), (array) $options);
        if (array_key_exists(strtolower($name), $options)) {
            throw new InvalidArgumentException(sprintf('Invalid cookie name "%s"', $name));
        }
        $data = [$name => rawurlencode($value)] + static::parseOptions($options);
        Header::send('Set-Cookie', Header::make($data), $replace);
    }

    /**
     * Remove a cookie
     *
     * @param bool $replace Whether to replace existing Set-Cookie header
     */
    public static function remove(string $name, array $options = [], bool $replace = false): void
    {
        static::send($name, '', ['expires' => time() - 3600] + $options, $replace);
    }

    /**
     * Return an array containing the default cookie directives
     */
    protected static function defaults(): array
    {
        return [
            'expires'  => 0,
            'max-age'  => 0,
            'domain'   => null,
            'path'     => null,
            'secure'   => false,
            'httponly' => false,
            'samesite' => false
        ];
    }

    /**
     * Parse cookie options
     */
    protected static function parseOptions(array $options): array
    {
        $data = [];
        if ($options['expires'] > 0) {
            $data['Expires'] = gmdate('D, d M Y H:i:s T', $options['expires']);
        }
        if ($options['max-age'] > 0) {
            $data['Max-Age'] = $options['max-age'];
        }
        if (!empty($options['domain'])) {
            $data['Domain'] = $options['domain'];
        }
        if (!empty($options['path'])) {
            $data['Path'] = $options['path'];
        }
        if ($options['secure']) {
            $data[] = 'Secure';
        }
        if ($options['httponly']) {
            $data[] = 'HttpOnly';
        }
        if (!empty($options['samesite'])) {
            if (!in_array($options['samesite'], [self::SAMESITE_STRICT, self::SAMESITE_LAX], true)) {
                throw new UnexpectedValueException('Invalid value for cookie SameSite directive');
            }
            $data['SameSite'] = $options['samesite'];
        }
        return $data;
    }
}
