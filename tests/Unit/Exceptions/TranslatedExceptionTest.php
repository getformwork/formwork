<?php

namespace Formwork\Tests\Unit\Exceptions;

use Exception;
use Formwork\Exceptions\RecursionException;
use Formwork\Exceptions\TranslatedException;
use Formwork\Tests\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use TypeError;

#[CoversClass(TranslatedException::class)]
#[CoversClass(RecursionException::class)]
final class TranslatedExceptionTest extends TestCase
{
    public function testMessageAndLanguageStringAreExposed(): void
    {
        $exception = new TranslatedException('Cannot save the page', 'panel.pages.error.cannotSave');

        $this->assertSame('Cannot save the page', $exception->getMessage());
        $this->assertSame('panel.pages.error.cannotSave', $exception->getLanguageString());
    }

    public function testCodeAndPreviousExceptionAreOptional(): void
    {
        $exception = new TranslatedException('message', 'language.string');

        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testCodeAndPreviousExceptionAreKept(): void
    {
        $previous = new InvalidArgumentException('cause');

        $exception = new TranslatedException('message', 'language.string', 42, $previous);

        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testExceptionCanBeCaughtAsAGenericException(): void
    {
        try {
            throw new TranslatedException('message', 'language.string');
        } catch (Exception $exception) {
            $this->assertInstanceOf(TranslatedException::class, $exception);
        }
    }

    public function testLanguageStringIsNotPartOfTheMessage(): void
    {
        $exception = new TranslatedException('English message', 'language.string');

        $this->assertStringNotContainsString('language.string', $exception->getMessage());
        $this->assertStringNotContainsString('language.string', (string) $exception->getTraceAsString());
    }

    public function testEmptyLanguageStringsAreAllowed(): void
    {
        $this->assertSame('', (new TranslatedException('message', ''))->getLanguageString());
    }

    public function testErrorsCanBeWrappedAsThePreviousThrowable(): void
    {
        // Any throwable is a valid previous exception, including errors such as `TypeError`
        $previous = new TypeError('wrong type');

        $exception = new TranslatedException('message', 'language.string', previousException: $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testRecursionExceptionIsARuntimeException(): void
    {
        $exception = new RecursionException('Recursion detected');

        $this->assertInstanceOf(RuntimeException::class, $exception);
        $this->assertSame('Recursion detected', $exception->getMessage());
    }
}
