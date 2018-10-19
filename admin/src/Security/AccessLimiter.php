<?php

namespace Formwork\Admin\Security;

use Formwork\Admin\Utils\Registry;
use Formwork\Utils\HTTPRequest;
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
     *
     * @param int $limit
     * @param int $resetTime
     */
    public function __construct(Registry $registry, $limit, $resetTime)
    {
        $this->registry = $registry;
        $this->limit = $limit;
        $this->resetTime = $resetTime;

        // Hash visitor IP address followed by current host
        $this->attemptHash = sha1(HTTPRequest::ip() . '@' . Uri::host());

        $this->attempts = $registry->get($this->attemptHash)[0];
        $this->lastAttemptTime = $registry->get($this->attemptHash)[1];
    }

    /**
     * Return whether attempts limit is reached
     *
     * @return bool
     */
    public function hasReachedLimit()
    {
        if (time() - $this->lastAttemptTime > $this->resetTime) {
            $this->resetAttempts();
        }
        return $this->attempts > $this->limit;
    }

    /**
     * Register an access attempt
     */
    public function registerAttempt()
    {
        $this->registry->set($this->attemptHash, array(++$this->attempts, time()));
    }

    /**
     * Reset attempts registry
     */
    public function resetAttempts()
    {
        $this->attempts = 0;
        $this->registry->remove($this->attemptHash);
    }
}
