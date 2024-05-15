<?php

namespace Formwork\Security;

use Formwork\Http\Request;

class CsrfToken
{
    /**
     * Session key to store the CSRF token
     */
    protected const SESSION_KEY = 'CSRF_TOKEN';

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
    public function validate(string $token): bool
    {
        return ($storedToken = $this->get()) && hash_equals($token, $storedToken);
    }

    /**
     * Remove CSRF token from session data
     */
    public function destroy(): void
    {
        $this->request->session()->remove(self::SESSION_KEY);
    }
}
