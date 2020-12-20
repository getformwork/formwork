<?php

namespace Formwork\Admin\Security;

use Formwork\Utils\HTTPRequest;
use Formwork\Utils\Session;
use RuntimeException;

class CSRFToken
{
    /**
     * Session key to store the CSRF token
     *
     * @var string
     */
    protected const SESSION_KEY = 'CSRF_TOKEN';

    /**
     * Input name to retrieve the CSRF token
     *
     * @var string
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
        return Session::has(self::SESSION_KEY) ? Session::get(self::SESSION_KEY) : null;
    }

    /**
     * Check if given CSRF token is valid
     */
    public static function validate(string $token = null): bool
    {
        if ($token === null) {
            $postData = HTTPRequest::postData();
            $valid = isset($postData[self::INPUT_NAME]) && $postData[self::INPUT_NAME] === static::get();
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
