<?php

namespace Formwork\Tests\Data\Exceptions;

use Formwork\Data\Exceptions\InvalidValueException;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(InvalidValueException::class)]
final class InvalidValueExceptionTest extends TestCase
{
    public function testConstructorWithMessage(): void
    {
        $exception = new InvalidValueException('Invalid value provided');
        $this->assertSame('Invalid value provided', $exception->getMessage());
    }

    public function testConstructorWithIdentifierAndContext(): void
    {
        $exception = new InvalidValueException('Invalid age', 'age.invalid', ['age' => -5]);
        $this->assertSame('age.invalid', $exception->getIdentifier());
        $this->assertSame(['age' => -5], $exception->getContext());
    }
}
