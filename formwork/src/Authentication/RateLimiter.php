<?php

namespace Formwork\Authentication;

use Formwork\Authentication\Exceptions\RateLimitExceededException;
use Formwork\Http\Request;
use Formwork\Log\Registry;

final class RateLimiter
{
    /**
     * Hash which identifies the visitor making the access attempts
     */
    private string $attemptHash;

    /**
     * The number of access attempts
     */
    private int $attempts = 0;

    /**
     * Time of last valid attempt
     */
    private int $lastAttemptTime;

    public function __construct(
        private Registry $registry,
        private int $limit,
        private int $resetTime,
        Request $request,
    ) {
        // Hash visitor IP address followed by current host
        $this->attemptHash = hash('sha256', "{$request->ip()}@{$request->host()}");

        if ($registry->has($this->attemptHash)) {
            [$this->attempts, $this->lastAttemptTime] = $registry->get($this->attemptHash);
        }
    }

    /**
     * Assert that access attempts are allowed
     *
     * @throws RateLimitExceededException If attempts limit is reached
     */
    public function assertAllowed(): void
    {
        $this->registerAttempt();
        if ($this->hasReachedLimit()) {
            throw new RateLimitExceededException('Rate limit exceeded', $this->resetTime);
        }
    }

    /**
     * Return whether attempts limit is reached
     */
    public function hasReachedLimit(): bool
    {
        return $this->attempts > $this->limit;
    }

    /**
     * Register an access attempt
     */
    public function registerAttempt(): void
    {
        if (isset($this->lastAttemptTime) && time() - $this->lastAttemptTime > $this->resetTime) {
            $this->resetAttempts();
        }
        if ($this->hasReachedLimit()) {
            // Do not register further attempts if limit is reached
            return;
        }
        $this->registry->set($this->attemptHash, [++$this->attempts, time()]);
    }

    /**
     * Reset attempts registry
     */
    public function resetAttempts(): void
    {
        $this->attempts = 0;
        $this->registry->remove($this->attemptHash);
    }

    /**
     * Get the attempts reset time
     */
    public function getResetTime(): int
    {
        return $this->resetTime;
    }
}
