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
        $translatedException = new TranslatedException('Cannot save the page', 'panel.pages.error.cannotSave');

        $this->assertSame('Cannot save the page', $translatedException->getMessage());
        $this->assertSame('panel.pages.error.cannotSave', $translatedException->getLanguageString());
    }

    public function testCodeAndPreviousExceptionAreOptional(): void
    {
        $translatedException = new TranslatedException('message', 'language.string');

        $this->assertSame(0, $translatedException->getCode());
        $this->assertNull($translatedException->getPrevious());
    }

    public function testCodeAndPreviousExceptionAreKept(): void
    {
        $invalidArgumentException = new InvalidArgumentException('cause');

        $translatedException = new TranslatedException('message', 'language.string', 42, $invalidArgumentException);

        $this->assertSame(42, $translatedException->getCode());
        $this->assertSame($invalidArgumentException, $translatedException->getPrevious());
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
        $translatedException = new TranslatedException('English message', 'language.string');

        $this->assertStringNotContainsString('language.string', $translatedException->getMessage());
        $this->assertStringNotContainsString('language.string', $translatedException->getTraceAsString());
    }

    public function testEmptyLanguageStringsAreAllowed(): void
    {
        $this->assertSame('', (new TranslatedException('message', ''))->getLanguageString());
    }

    public function testErrorsCanBeWrappedAsThePreviousThrowable(): void
    {
        // Any throwable is a valid previous exception, including errors such as `TypeError`
        $typeError = new TypeError('wrong type');

        $translatedException = new TranslatedException('message', 'language.string', previous: $typeError);

        $this->assertSame($typeError, $translatedException->getPrevious());
    }

    public function testRecursionExceptionIsARuntimeException(): void
    {
        $recursionException = new RecursionException('Recursion detected');

        $this->assertInstanceOf(RuntimeException::class, $recursionException);
        $this->assertSame('Recursion detected', $recursionException->getMessage());
    }
}
