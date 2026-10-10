<?php

namespace Formwork\Tests\Unit\Translations;

use ArgumentCountError;
use Formwork\Tests\TestCase;
use Formwork\Translations\Translation;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use Stringable;

#[CoversClass(Translation::class)]
final class TranslationTest extends TestCase
{
    public function testCodeIsExposed(): void
    {
        $this->assertSame('it', (new Translation('it', []))->code());
    }

    public function testStringsCanBeTranslated(): void
    {
        $translation = new Translation('en', ['hello' => 'Hello', 'empty' => '']);

        $this->assertTrue($translation->has('hello'));
        $this->assertSame('Hello', $translation->translate('hello'));
        $this->assertSame('', $translation->translate('empty'));
    }

    public function testMissingStringsAreReported(): void
    {
        $translation = new Translation('en', ['hello' => 'Hello']);

        $this->assertFalse($translation->has('missing'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid language string "missing"');
        $translation->translate('missing');
    }

    public function testArgumentsAreFormattedWithSprintf(): void
    {
        $translation = new Translation('en', [
            'one'       => 'Hello %s',
            'two'       => '%s has %d items (%.1f%%)',
            'ordered'   => '%2$s %1$s',
            'untouched' => '100% sure',
        ]);

        $this->assertSame('Hello World', $translation->translate('one', 'World'));
        $this->assertSame('Anna has 3 items (12.5%)', $translation->translate('two', 'Anna', 3, 12.5));
        $this->assertSame('b a', $translation->translate('ordered', 'a', 'b'));
        $this->assertSame('100% sure', $translation->translate('untouched'));
    }

    public function testStringableArgumentsAreAccepted(): void
    {
        $translation = new Translation('en', ['key' => 'Value: %s']);

        $argument = new class implements Stringable {
            public function __toString(): string
            {
                return 'stringable';
            }
        };

        $this->assertSame('Value: stringable', $translation->translate('key', $argument));
    }

    public function testExtraArgumentsAreIgnored(): void
    {
        $translation = new Translation('en', ['key' => 'Hello %s']);

        $this->assertSame('Hello a', $translation->translate('key', 'a', 'b', 'c'));
    }

    public function testMissingArgumentsAreReported(): void
    {
        $translation = new Translation('en', ['key' => 'Hello %s and %s']);

        $this->expectException(ArgumentCountError::class);
        $translation->translate('key', 'only one');
    }

    public function testFormatCharactersAreNotInterpretedWithoutArguments(): void
    {
        $translation = new Translation('en', ['key' => 'Progress: 50%']);

        $this->assertSame('Progress: 50%', $translation->translate('key'));
    }

    public function testArgumentsAreNotInterpretedAsFormats(): void
    {
        $translation = new Translation('en', ['key' => 'Name: %s']);

        $this->assertSame('Name: %s %d', $translation->translate('key', '%s %d'));
    }

    public function testMissingStringsFallBackToTheFallbackTranslation(): void
    {
        $fallback = new Translation('en', ['only.english' => 'English only', 'both' => 'English']);
        $translation = new Translation('it', ['both' => 'Italiano']);
        $translation->setFallback($fallback);

        $this->assertSame('Italiano', $translation->translate('both'));
        $this->assertSame('English only', $translation->translate('only.english'));
    }

    public function testArgumentsAreForwardedToTheFallback(): void
    {
        $translation = new Translation('it', []);
        $translation->setFallback(new Translation('en', ['key' => 'Hello %s']));

        $this->assertSame('Hello World', $translation->translate('key', 'World'));
    }

    public function testStringsMissingFromBothTranslationsAreReported(): void
    {
        $translation = new Translation('it', []);
        $translation->setFallback(new Translation('en', []));

        $this->expectException(InvalidArgumentException::class);
        $translation->translate('missing');
    }

    public function testTranslationsDoNotFallBackToThemselves(): void
    {
        $translation = new Translation('en', []);
        $translation->setFallback(new Translation('en', ['key' => 'value']));

        $this->expectException(InvalidArgumentException::class);
        $translation->translate('key');
    }

    public function testFallbackCanBeRemoved(): void
    {
        $translation = new Translation('it', []);
        $translation->setFallback(new Translation('en', ['key' => 'value']));
        $translation->setFallback(null);

        $this->expectException(InvalidArgumentException::class);
        $translation->translate('key');
    }

    public function testFallbackChainsAreFollowed(): void
    {
        $english = new Translation('en', ['key' => 'English']);
        $italian = new Translation('it', []);
        $italian->setFallback($english);
        $sicilian = new Translation('scn', []);
        $sicilian->setFallback($italian);

        $this->assertSame('English', $sicilian->translate('key'));
    }

    public function testNonStringValuesCannotBeTranslated(): void
    {
        $translation = new Translation('en', ['list' => ['a', 'b'], 'number' => 5]);

        $this->expectException(InvalidArgumentException::class);
        $translation->translate('list');
    }

    public function testNumericValuesCannotBeTranslated(): void
    {
        $translation = new Translation('en', ['number' => 5]);

        $this->expectException(InvalidArgumentException::class);
        $translation->translate('number');
    }

    public function testStringListsAreReturnedByGetStrings(): void
    {
        $translation = new Translation('en', ['days' => ['day', 'days'], 'single' => 'one']);

        $this->assertSame(['day', 'days'], $translation->getStrings('days'));
        $this->assertSame(['one'], $translation->getStrings('single'));
    }

    public function testGetStringsFallsBackToTheFallbackTranslation(): void
    {
        $translation = new Translation('it', []);
        $translation->setFallback(new Translation('en', ['days' => ['day', 'days']]));

        $this->assertSame(['day', 'days'], $translation->getStrings('days'));
    }

    public function testGetStringsReportsMissingStrings(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid language string "missing"');
        (new Translation('en', []))->getStrings('missing');
    }

    public function testAllStringsIncludeTheOnesOfTheFallback(): void
    {
        $translation = new Translation('it', ['a' => 'A-it', 'c' => 'C-it']);
        $translation->setFallback(new Translation('en', ['a' => 'A-en', 'b' => 'B-en']));

        $this->assertSame(['a' => 'A-it', 'b' => 'B-en', 'c' => 'C-it'], $translation->getAllStrings());
    }

    public function testAllStringsWithoutFallback(): void
    {
        $this->assertSame(['a' => 'A'], (new Translation('en', ['a' => 'A']))->getAllStrings());
    }

    public function testAllStringsDoNotMergeListsOfTheFallback(): void
    {
        $translation = new Translation('it', ['days' => ['giorno', 'giorni']]);
        $translation->setFallback(new Translation('en', ['days' => ['day', 'days', 'extra']]));

        $this->assertSame(['days' => ['giorno', 'giorni']], $translation->getAllStrings());
    }

    public function testNullValuesAreTreatedAsMissing(): void
    {
        $translation = new Translation('it', ['key' => null]);
        $translation->setFallback(new Translation('en', ['key' => 'fallback']));

        $this->assertFalse($translation->has('key'));
        $this->assertSame('fallback', $translation->translate('key'));
    }
}
