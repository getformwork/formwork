<?php

namespace Formwork\Security;

use Formwork\Http\Request;

final class CsrfToken
{
    /**
     * Session key to store the CSRF token
     */
    private const string SESSION_KEY_PREFIX = '_formwork_csrf_tokens';

    public function __construct(
        private Request $request,
    ) {}

    /**
     * Generate a new CSRF token
     */
    public function generate(string $name): string
    {
        $token = base64_encode(random_bytes(36));
        $this->request->session()->set(self::SESSION_KEY_PREFIX . ".{$name}", $token);
        return $token;
    }

    /**
     * Check if CSRF token exists
     */
    public function has(string $name): bool
    {
        return $this->request->session()->has(self::SESSION_KEY_PREFIX . ".{$name}");
    }

    /**
     * Get CSRF token by name
     */
    public function get(string $name, bool $autoGenerate = false): ?string
    {
        if ($autoGenerate && !$this->has($name)) {
            return $this->generate($name);
        }
        return $this->request->session()->get(self::SESSION_KEY_PREFIX . ".{$name}");
    }

    /**
     * Check if given CSRF token is valid
     */
    public function validate(string $name, string $token): bool
    {
        return ($storedToken = $this->get($name)) && hash_equals($token, $storedToken);
    }

    /**
     * Remove CSRF token from session data
     */
    public function destroy(string $name): void
    {
        $this->request->session()->remove(self::SESSION_KEY_PREFIX . ".{$name}");
    }
}
