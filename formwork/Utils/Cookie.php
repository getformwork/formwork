<?php

namespace Formwork\Utils;

use LogicException;

class Cookie
{
    /**
     * 'Strict' value for SameSite directive
     *
     * @var string
     */
    public const SAMESITE_STRICT = 'Strict';

    /**
     * 'Lax' value for SameSite directive
     *
     * @var string
     */
    public const SAMESITE_LAX = 'Lax';

    /**
     * Send a cookie
     *
     * @param string $name
     * @param string $value
     * @param array  $options
     * @param bool   $replace Whether to replace existing Set-Cookie header
     */
    public static function send(string $name, string $value, array $options = [], bool $replace = false)
    {
        $options = array_merge(static::defaults(), (array) $options);
        if (in_array(strtolower($name), array_keys($options), true)) {
            throw new LogicException('Invalid cookie name "' . $name . '"');
        }
        $data = [$name => rawurlencode($value)] + static::parseOptions($options);
        Header::send('Set-Cookie', static::makeHeader($data), $replace);
    }

    /**
     * Return an array containing the default cookie directives
     *
     * @return array
     */
    protected static function defaults()
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
     *
     * @param array $options
     *
     * @return array
     */
    protected static function parseOptions(array $options)
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
                throw new LogicException('Invalid value for cookie SameSite directive');
            }
            $data['SameSite'] = $options['samesite'];
        }
        return $data;
    }

    /**
     * Make Set-Cookie header string
     *
     * @param array $data
     *
     * @return string
     */
    protected static function makeHeader(array $data)
    {
        $parts = [];
        foreach ($data as $key => $value) {
            $parts[] = is_int($key) ? $value : $key . '=' . $value;
        }
        return implode('; ', $parts);
    }
}
