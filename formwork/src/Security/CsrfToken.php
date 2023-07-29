<?php

namespace Formwork\Security;

use Formwork\Http\Request;
use Formwork\Utils\Session;
use RuntimeException;

class CsrfToken
{
    /**
     * Session key to store the CSRF token
     */
    protected const SESSION_KEY = 'CSRF_TOKEN';

    /**
     * Input name to retrieve the CSRF token
     */
    protected const INPUT_NAME = 'csrf-token';

    public function __construct(protected Request $request)
    {
    }

    /**
     * Generate a new CSRF token
     */
    public function generate(): string
    {
        $token = base64_encode(random_bytes(36));
        $this->request->session()->set(self::SESSION_KEY, $token);
        return $token;
    }

    /**
     * Get current CSRF token
     */
    public function get(): ?string
    {
        return $this->request->session()->get(self::SESSION_KEY);
    }

    /**
     * Check if given CSRF token is valid
     */
    public function validate(?string $token = null): bool
    {
        if ($token === null) {
            $postData = $this->request->input();
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
    public function destroy(): void
    {
        $this->request->session()->remove(self::SESSION_KEY);
    }
}
