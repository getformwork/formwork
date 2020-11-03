<?php

namespace Formwork\Admin\Security;

use Formwork\Admin\Utils\Session;
use Formwork\Utils\HTTPRequest;
use RuntimeException;

class CSRFToken
{
    /**
     * Current CSRF token
     */
    protected static $token;

    /**
     * Generate a new CSRF token
     */
    public static function generate(): string
    {
        static::$token = base64_encode(random_bytes(36));
        Session::set('CSRF_TOKEN', static::$token);
        return static::$token;
    }

    /**
     * Get current CSRF token
     */
    public static function get(): string
    {
        return Session::has('CSRF_TOKEN') ? Session::get('CSRF_TOKEN') : null;
    }

    /**
     * Check if given CSRF token is valid
     */
    public static function validate(string $token = null): bool
    {
        if ($token === null) {
            $postData = HTTPRequest::postData();
            $valid = isset($postData['csrf-token']) && $postData['csrf-token'] === static::get();
        } else {
            $valid = $token === static::get();
        }
        if (!$valid) {
            static::destroy();
            throw new RuntimeException('CSRF token not valid');
        }
        return $valid;
    }

    /**
     * Remove CSRF token from session data
     */
    public static function destroy(): void
    {
        Session::remove('CSRF_TOKEN');
    }
}
