<?php

namespace Formwork\Tests\Unit\Data\Exceptions;

use Formwork\Data\Exceptions\InvalidValueException;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(InvalidValueException::class)]
final class InvalidValueExceptionTest extends TestCase
{
    public function testConstructorWithMessage(): void
    {
        $invalidValueException = new InvalidValueException('Invalid value provided');
        $this->assertSame('Invalid value provided', $invalidValueException->getMessage());
    }

    public function testConstructorWithIdentifierAndContext(): void
    {
        $invalidValueException = new InvalidValueException('Invalid age', 'age.invalid', ['age' => -5]);
        $this->assertSame('age.invalid', $invalidValueException->getIdentifier());
        $this->assertSame(['age' => -5], $invalidValueException->getContext());
    }
}
