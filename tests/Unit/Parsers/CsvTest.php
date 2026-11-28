<?php

namespace Formwork\Tests\Unit\Parsers;

use Formwork\Parsers\Csv;
use Formwork\Tests\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

#[CoversClass(Csv::class)]
final class CsvTest extends TestCase
{
    public function testCsvStringsAreParsedIntoRows(): void
    {
        $this->assertSame([['key', 'value'], ['first', '1'], ['second', '2']], Csv::parse("key,value\nfirst,1\nsecond,2\n"));
    }

    #[DataProvider('separatorProvider')]
    public function testCustomSeparatorsAreSupported(string $separator): void
    {
        $input = implode($separator, ['first', 'second']) . "\n";

        $this->assertSame([['first', 'second']], Csv::parse($input, ['separator' => $separator]));
        $this->assertSame($input, Csv::encode([['first', 'second']], ['separator' => $separator]));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function separatorProvider(): iterable
    {
        yield 'comma' => [Csv::SEPARATOR_COMMA];
        yield 'semicolon' => [Csv::SEPARATOR_SEMICOLON];
        yield 'tab' => [Csv::SEPARATOR_TAB];
    }

    public function testEmptyLinesAreSkipped(): void
    {
        $this->assertSame([['first'], ['second']], Csv::parse("first\n\nsecond\n"));
    }

    public function testEnclosedFieldsMayContainSeparatorsQuotesAndNewlines(): void
    {
        $rows = [['first, second', 'say "foo"', "multi\nline"]];

        $this->assertSame($rows, Csv::parse(Csv::encode($rows)));
    }

    public function testSingleQuoteEnclosureIsSupported(): void
    {
        $this->assertSame([['first,second', 'bar']], Csv::parse("'first,second',bar\n", ['enclosure' => Csv::ENCLOSURE_SINGLE_QUOTE]));
    }

    public function testBackslashesAreNotTreatedAsEscapeCharacters(): void
    {
        $rows = [['foo\\', 'bar']];

        $this->assertSame($rows, Csv::parse(Csv::encode($rows)));
    }

    public function testEncodeWritesRows(): void
    {
        $this->assertSame("key,value\nfirst,1\n\"foo bar\",\"a,b\"\n", Csv::encode([['key', 'value'], ['first', 1], ['foo bar', 'a,b']]));
    }

    public function testEncodeOfNoRowsIsEmpty(): void
    {
        $this->assertSame('', Csv::encode([]));
    }

    public function testParseRejectsUnsupportedSeparators(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported CSV separator "|"');

        Csv::parse('foo|bar', ['separator' => '|']);
    }

    public function testParseRejectsUnsupportedEnclosures(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported CSV enclosure "`"');

        Csv::parse('foo,bar', ['enclosure' => '`']);
    }

    public function testEncodeRejectsUnsupportedSeparators(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported CSV separator "|"');

        Csv::encode([['foo']], ['separator' => '|']);
    }

    public function testEncodeRejectsUnsupportedEnclosures(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported CSV enclosure "`"');

        Csv::encode([['foo']], ['enclosure' => '`']);
    }

    #[DataProvider('invalidDataProvider')]
    public function testEncodeRejectsDataThatIsNotAnArrayOfArrays(mixed $data): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Data must be an array of arrays to encode as CSV');

        Csv::encode($data);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function invalidDataProvider(): iterable
    {
        yield 'string' => ['foo'];
        yield 'flat array' => [['foo', 'bar']];
        yield 'mixed array' => [[['foo'], 'bar']];
    }
}
