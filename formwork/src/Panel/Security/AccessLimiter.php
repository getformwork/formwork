<?php

namespace Formwork\Panel\Security;

use Formwork\Utils\HTTPRequest;
use Formwork\Utils\Registry;
use Formwork\Utils\Uri;

class AccessLimiter
{
    /**
     * Registry which contains access attempts
     */
    protected Registry $registry;

    /**
     * Limit of valid attempts
     */
    protected int $limit;

    /**
     * Seconds after which registry is reset
     */
    protected int $resetTime;

    /**
     * Hash which identifies the visitor which make attempts
     */
    protected string $attemptHash;

    /**
     * The number of access attempts
     */
    protected int $attempts = 0;

    /**
     * Time of last valid attempt
     */
    protected int $lastAttemptTime;

    /**
     * Create a new AccessLimiter instance
     */
    public function __construct(Registry $registry, int $limit, int $resetTime)
    {
        $this->registry = $registry;
        $this->limit = $limit;
        $this->resetTime = $resetTime;

        // Hash visitor IP address followed by current host
        $this->attemptHash = hash('sha256', HTTPRequest::ip() . '@' . Uri::host());

        if ($registry->has($this->attemptHash)) {
            [$this->attempts, $this->lastAttemptTime] = $registry->get($this->attemptHash);
        }
    }

    /**
     * Return whether attempts limit is reached
     */
    public function hasReachedLimit(): bool
    {
        if (isset($this->lastAttemptTime) && time() - $this->lastAttemptTime > $this->resetTime) {
            $this->resetAttempts();
        }
        return $this->attempts > $this->limit;
    }

    /**
     * Register an access attempt
     */
    public function registerAttempt(): void
    {
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
}
