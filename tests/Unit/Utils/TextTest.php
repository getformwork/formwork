<?php

namespace Formwork\Tests\Unit\Utils;

use Formwork\Tests\Environment;
use Formwork\Tests\TestCase;
use Formwork\Utils\Text;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

#[CoversClass(Text::class)]
final class TextTest extends TestCase
{
    protected function tearDown(): void
    {
        Environment::enableExtension('mbstring');
    }

    public function testNormalizeWhitespace(): void
    {
        $this->assertSame('Hello World!', Text::normalizeWhitespace("  Hello   World!  \n"));
        $this->assertSame('Hello World!', Text::normalizeWhitespace("Hello\tWorld!"));
        $this->assertSame('', Text::normalizeWhitespace("   \n\t  "));
    }

    public function testWords(): void
    {
        $this->assertSame(['Hello', 'World!'], Text::splitWords('Hello World!'));
        $this->assertSame(['Hello', 'World!'], Text::splitWords("  Hello   World!  \n"));
        $this->assertSame(['Hello', 'World!'], Text::splitWords("Hello\tWorld!"));
        $this->assertSame([], Text::splitWords("   \n\t  "));
    }

    public function testWordsWithLimit(): void
    {
        $this->assertSame(['Hello', 'World! This is a test.'], Text::splitWords('Hello World! This is a test.', 2));
        $this->assertSame(['Hello', 'World!', 'This is a test.'], Text::splitWords('Hello World! This is a test.', 3));
    }

    public function testCountWords(): void
    {
        $this->assertSame(6, Text::countWords('Hello World! This is a test.'));
        $this->assertSame(6, Text::countWords("  Hello   World!  \nThis is a test.  "));
        $this->assertSame(6, Text::countWords("Hello\tWorld! This is a test."));
        $this->assertSame(0, Text::countWords("   \n\t  "));
    }

    public function testTruncate(): void
    {
        $this->assertSame('Hello…', Text::truncate('Hello World!', 5));
        $this->assertSame('Hello…', Text::truncate('Hello World!', 8));
        $this->assertSame('Hello World!', Text::truncate('Hello World!', 20));
    }

    public function testTruncateThrowsOnDisabledMbstring(): void
    {
        Environment::disableExtension('mbstring');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('requires the extension "mbstring" to be enabled');
        Text::truncate('Hello World!', 5);
    }

    public function testTruncateByWords(): void
    {
        $this->assertSame('Hello…', Text::truncateWords('Hello World! This is a test.', 1));
        $this->assertSame('Hello World! This…', Text::truncateWords('Hello World! This is a test.', 3));
        $this->assertSame('Hello World! This is a test.', Text::truncateWords('Hello World! This is a test.', 10));
    }

    public function testEstimateReadingTime(): void
    {
        $this->assertSame(1, Text::readingTime(''));
        $this->assertSame(1, Text::readingTime('This is a short text.'));
        $this->assertSame(3, Text::readingTime(str_repeat('This is a short text. ', 100)));
    }

    public function testTruncateKeepsTextThatFitsExactly(): void
    {
        $this->assertSame('Hello world', Text::truncate('Hello world', 11));
        $this->assertSame('Hello…', Text::truncate('Hello world', 10));
    }

    public function testTruncateCutsAtTheLastWordThatFits(): void
    {
        $this->assertSame('Hello…', Text::truncate('Hello wonderful world', 12));
        $this->assertSame('Hello wonderful…', Text::truncate('Hello wonderful world', 15));
        $this->assertSame('Hello wonderful…', Text::truncate('Hello wonderful world', 16));
    }

    public function testTruncateNormalizesWhitespaceBeforeMeasuring(): void
    {
        $this->assertSame('Hello world', Text::truncate("  Hello \n\t world  ", 20));
    }

    public function testTruncateCountsMultibyteCharacters(): void
    {
        $this->assertSame('Così è la…', Text::truncate('Così è la vita ancora', 10));
    }

    public function testTruncateUsesTheGivenEllipsis(): void
    {
        $this->assertSame('Hello...', Text::truncate('Hello world', 7, '...'));
    }

    #[DataProvider('longWordProvider')]
    public function testTruncatedTextNeverExceedsTheLengthWhenThereIsNoWordBoundary(string $text, int $length): void
    {
        $this->assertLessThanOrEqual($length, mb_strlen(rtrim(Text::truncate($text, $length), '…')));
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function longWordProvider(): iterable
    {
        yield 'single long word' => ['abcdefghijkl', 5];
        yield 'zero length' => ['Hello world', 0];
        yield 'long first word' => ['Supercalifragilistic word', 5];
    }

    public function testTruncateWordsKeepsTextWithExactlyTheGivenWords(): void
    {
        $this->assertSame('one two three', Text::truncateWords('one two three', 3));
        $this->assertSame('one two…', Text::truncateWords('one two three', 2));
        $this->assertSame('…', Text::truncateWords('one two three', 0));
    }

    public function testTruncateWordsIgnoresExtraWhitespace(): void
    {
        $this->assertSame('one…', Text::truncateWords("  one   two\n", 1));
        $this->assertSame('', Text::truncateWords('', 2));
    }
}
