<?php

namespace Formwork\Admin\Security;

use Formwork\Utils\HTTPRequest;
use Formwork\Utils\Registry;
use Formwork\Utils\Uri;

class AccessLimiter
{
    /**
     * Registry which contains access attempts
     *
     * @var Registry
     */
    protected $registry;

    /**
     * Limit of valid attempts
     *
     * @var int
     */
    protected $limit;

    /**
     * Seconds after which registry is reset
     *
     * @var int
     */
    protected $resetTime;

    /**
     * Hash which identifies the visitor which make attempts
     *
     * @var string
     */
    protected $attemptHash;

    /**
     * The number of access attempts
     *
     * @var int
     */
    protected $attempts;

    /**
     * Time of last valid attempt
     *
     * @var int
     */
    protected $lastAttemptTime;

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
        if (time() - $this->lastAttemptTime > $this->resetTime) {
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
