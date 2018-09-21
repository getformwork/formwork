<?php

namespace Formwork\Utils;

use LogicException;

class Cookie
{
    public static function send($name, $value, $options = array(), $replace = false)
    {
        $options = array_merge(static::defaults(), (array) $options);
        if (in_array(strtolower($name), array_keys($options))) {
            throw new LogicException('Invalid cookie name "' . $name . '"');
        }
        $data = array($name => rawurlencode($value)) + static::parseOptions($options);
        Header::send('Set-Cookie', static::makeHeader($data), $replace);
    }

    protected static function defaults()
    {
        return array(
            'expires' => 0,
            'max-age' => 0,
            'domain' => null,
            'path' => null,
            'secure' => false,
            'httponly' => false,
            'samesite' => false
        );
    }

    protected static function parseOptions($options)
    {
        $data = array();
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
            $data['SameSite'] = ucfirst(strtolower($options['samesite']));
        }
        return $data;
    }

    protected static function makeHeader($data)
    {
        $parts = array();
        foreach ($data as $key => $value) {
            $parts[] = is_int($key) ? $value : $key . '=' . $value;
        }
        return implode('; ', $parts);
    }
}
