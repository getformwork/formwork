<?php

namespace Formwork\Tests\Unit\Exceptions;

use Formwork\Exceptions\TranslatedException;
use Formwork\Tests\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(TranslatedException::class)]
final class TranslatedExceptionTest extends TestCase
{
    public function testMessageAndLanguageStringAreExposed(): void
    {
        $translatedException = new TranslatedException('Cannot save the page', 'panel.pages.error.cannotSave');

        $this->assertSame('Cannot save the page', $translatedException->getMessage());
        $this->assertSame('panel.pages.error.cannotSave', $translatedException->getLanguageString());
    }

    public function testCodeAndPreviousExceptionArePreserved(): void
    {
        $invalidArgumentException = new InvalidArgumentException('cause');

        $translatedException = new TranslatedException('message', 'language.string', 42, $invalidArgumentException);

        $this->assertSame(42, $translatedException->getCode());
        $this->assertSame($invalidArgumentException, $translatedException->getPrevious());
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
}
