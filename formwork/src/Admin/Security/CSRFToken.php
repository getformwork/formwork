<?php

namespace Formwork\Admin\Security;

use Formwork\Traits\StaticClass;
use Formwork\Utils\HTTPRequest;
use Formwork\Utils\Session;
use RuntimeException;

class CSRFToken
{
    use StaticClass;

    /**
     * Session key to store the CSRF token
     */
    protected const SESSION_KEY = 'CSRF_TOKEN';

    /**
     * Input name to retrieve the CSRF token
     */
    protected const INPUT_NAME = 'csrf-token';

    /**
     * Generate a new CSRF token
     */
    public static function generate(): string
    {
        $token = base64_encode(random_bytes(36));
        Session::set(self::SESSION_KEY, $token);
        return $token;
    }

    /**
     * Get current CSRF token
     */
    public static function get(): ?string
    {
        return Session::get(self::SESSION_KEY);
    }

    /**
     * Check if given CSRF token is valid
     */
    public static function validate(string $token = null): bool
    {
        if ($token === null) {
            $postData = HTTPRequest::postData();
            $valid = $postData->has(self::INPUT_NAME) && $postData->get(self::INPUT_NAME) === static::get();
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
        Session::remove(self::SESSION_KEY);
    }
}
