<?php

namespace Formwork\Admin\Security;

use Formwork\Admin\Utils\Registry;
use Formwork\Utils\HTTPRequest;
use Formwork\Utils\Uri;

class AccessLimiter
{
    protected $registry;

    protected $limit;

    protected $resetTime;

    protected $attemptHash;

    protected $attempts;

    protected $lastAttemptTime;

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

    public function hasReachedLimit()
    {
        if (time() - $this->lastAttemptTime > $this->resetTime) {
            $this->resetAttempts();
        }
        return $this->attempts > $this->limit;
    }

    public function registerAttempt()
    {
        $this->registry->set($this->attemptHash, array(++$this->attempts, time()));
    }

    public function resetAttempts()
    {
        $this->attempts = 0;
        $this->registry->remove($this->attemptHash);
    }
}
