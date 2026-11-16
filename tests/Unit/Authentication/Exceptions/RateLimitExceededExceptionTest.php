<?php

namespace Formwork\Tests\Unit\Authentication\Exceptions;

use Formwork\Authentication\Exceptions\RateLimitExceededException;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;

#[CoversClass(RateLimitExceededException::class)]
final class RateLimitExceededExceptionTest extends TestCase
{
    public function testResetTimeIsKeptWithTheMessageAndThePreviousException(): void
    {
        $previous = new RuntimeException('Previous');

        $rateLimitExceededException = new RateLimitExceededException('Too many attempts', 300, 429, $previous);

        $this->assertSame('Too many attempts', $rateLimitExceededException->getMessage());
        $this->assertSame(300, $rateLimitExceededException->getResetTime());
        $this->assertSame(429, $rateLimitExceededException->getCode());
        $this->assertSame($previous, $rateLimitExceededException->getPrevious());
    }

    public function testResetTimeIsZeroByDefault(): void
    {
        $this->assertSame(0, (new RateLimitExceededException())->getResetTime());
    }
}
