<?php

namespace Formwork\Authentication\Exceptions;

use RuntimeException;
use Throwable;

class RateLimitExceededException extends RuntimeException
{
    /**
     * @param string $message   Exception message
     * @param int    $resetTime Time (in seconds) until the rate limit is reset
     * @param int    $code      Exception code
     */
    public function __construct(
        string $message = '',
        protected int $resetTime = 0,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get reset time
     */
    public function getResetTime(): int
    {
        return $this->resetTime;
    }
}
