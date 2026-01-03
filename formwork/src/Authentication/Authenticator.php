<?php

namespace Formwork\Authentication;

use Formwork\Authentication\Exceptions\AuthenticationFailedException;
use Formwork\Authentication\Exceptions\RateLimitExceededException;
use Formwork\Authentication\Exceptions\UserNotLoggedException;
use Formwork\Http\Session\Session;
use Formwork\Users\User;
use Formwork\Users\Users;
use SensitiveParameter;

class Authenticator
{
    public const string SESSION_LOGGED_USER_KEY = '_formwork_logged_user';

    public function __construct(
        protected Users $users,
        protected Session $session,
        protected RateLimiter $rateLimiter
    ) {}

    /**
     * Login a user with given credentials
     *
     * @throws AuthenticationFailedException If authentication fails
     * @throws RateLimitExceededException    If rate limit is exceeded
     */
    public function login(
        string $login,
        #[SensitiveParameter]
        string $password
    ): User {
        try {
            $this->rateLimiter->assertAllowed();

            /** @var ?User */
            $user = $this->users->find(fn(User $user) => $user->username() === $login || $user->email() === $login);

            if (!$user?->verifyPassword($password)) {
                throw new AuthenticationFailedException(sprintf('Authentication failed for "%s"', $login));
            }
        } catch (RateLimitExceededException|AuthenticationFailedException $e) {
            // Delay processing for 0.5-1s
            usleep(random_int(500, 1000) * 1000);

            throw $e;
        }

        $this->session->regenerate();
        $this->session->set(self::SESSION_LOGGED_USER_KEY, $user->username());

        $user->set('lastAccess', time());
        $user->save();

        $this->rateLimiter->resetAttempts();

        return $user;
    }

    /**
     * Return whether a user is logged in
     */
    public function isLoggedIn(): bool
    {
        return $this->session->has(self::SESSION_LOGGED_USER_KEY);
    }

    /**
     * Logout currently logged in user
     */
    public function logout(): void
    {
        if (!$this->isLoggedIn()) {
            throw new UserNotLoggedException('Cannot logout, no user is logged in');
        }
        $this->session->remove(self::SESSION_LOGGED_USER_KEY);
        $this->session->regenerate();
    }

    /**
     * Get currently logged in user if any
     */
    public function getUser(): ?User
    {
        $username = $this->session->get(self::SESSION_LOGGED_USER_KEY);
        return $this->users->find(fn(User $user) => $user->username() === $username);
    }
}
