<?php

namespace Formwork\Panel\Security;

use Formwork\Http\Request;
use Formwork\Log\Registry;

final class AccessLimiter
{
    /**
     * Hash which identifies the visitor which make attempts
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
