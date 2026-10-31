<?php

namespace Formwork\Tests\Unit\Images\Binary;

use Formwork\Images\Binary\ByteReader;
use Formwork\Images\Binary\Bytes;
use Formwork\Images\Binary\Endianness;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use UnexpectedValueException;

#[CoversClass(ByteReader::class)]
#[CoversClass(Bytes::class)]
final class ByteReaderTest extends TestCase
{
    public function testBytesAreReadInSequence(): void
    {
        $reader = new ByteReader('abcdef');

        $this->assertSame('ab', $reader->bytes(2));
        $this->assertSame('cd', $reader->bytes(2));
        $this->assertSame(2, $reader->remaining());
    }

    public function testRemainingBytesAreRead(): void
    {
        $reader = new ByteReader('abcdef');
        $reader->skip(4);

        $this->assertSame('ef', $reader->rest());
        $this->assertSame(0, $reader->remaining());
        $this->assertSame('', $reader->rest());
    }

    #[DataProvider('unsignedIntegerProvider')]
    public function testUnsignedIntegersAreReadInBigEndianOrder(string $data, int $size, int $expected): void
    {
        $this->assertSame($expected, (new ByteReader($data))->uint($size));
    }

    /**
     * @return iterable<string, array{string, int, int}>
     */
    public static function unsignedIntegerProvider(): iterable
    {
        yield 'no bytes' => ['', 0, 0];
        yield 'one byte' => ["\xFF", 1, 255];
        yield 'two bytes' => ["\x01\x02", 2, 258];
        yield 'three bytes' => ["\x01\x00\x00", 3, 65536];
        yield 'four bytes' => ["\xFF\xFF\xFF\xFF", 4, 4294967295];
        yield 'eight bytes' => ["\x00\x00\x00\x01\x00\x00\x00\x00", 8, 4294967296];
    }

    #[DataProvider('invalidLengthProvider')]
    public function testReadingBeyondTheDataIsNotAllowed(int $length): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Unexpected end of data');

        (new ByteReader('abc'))->bytes($length);
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function invalidLengthProvider(): iterable
    {
        yield 'more than available' => [4];
        yield 'negative' => [-1];
    }

    #[DataProvider('packedIntegerProvider')]
    public function testUnsignedIntegersArePackedInBigEndianOrder(int $value, int $size, string $expected): void
    {
        $this->assertSame($expected, Bytes::pack($value, $size));
    }

    /**
     * @return iterable<string, array{int, int, string}>
     */
    public static function packedIntegerProvider(): iterable
    {
        yield 'no bytes' => [0, 0, ''];
        yield 'one byte' => [255, 1, "\xFF"];
        yield 'two bytes' => [258, 2, "\x01\x02"];
        yield 'four bytes' => [4294967295, 4, "\xFF\xFF\xFF\xFF"];
        yield 'eight bytes' => [4294967296, 8, "\x00\x00\x00\x01\x00\x00\x00\x00"];
    }

    public function testPackingAndReadingAreSymmetrical(): void
    {
        $this->assertSame(123456, (new ByteReader(Bytes::pack(123456, 4)))->uint(4));
    }

    #[DataProvider('unpackableIntegerProvider')]
    public function testIntegersNotFittingTheGivenSizeAreNotPacked(int $value, int $size): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage(sprintf('Value %d does not fit in %d bytes', $value, $size));

        Bytes::pack($value, $size);
    }

    /**
     * @return iterable<string, array{int, int}>
     */
    public static function unpackableIntegerProvider(): iterable
    {
        yield 'too large for one byte' => [256, 1];
        yield 'too large for two bytes' => [65536, 2];
        yield 'negative' => [-1, 4];
    }

    public function testFullBoxHeadersHoldAVersionAndThreeFlagBytes(): void
    {
        $this->assertSame("\x01\x00\x00\x00", Bytes::fullBox(1));
        $this->assertSame("\x02\x00\x00\x07", Bytes::fullBox(2, 7));
    }

    public function testUnsignedIntegersCanBeReadInLittleEndianOrder(): void
    {
        $reader = new ByteReader("\x01\x02\x03\x04\x05\x06");

        $this->assertSame(0x0201, $reader->uint(2, Endianness::Little));
        $this->assertSame(0x06050403, $reader->uint(4, Endianness::Little));
    }

    public function testIntegersWiderThanThePlatformOnesAreNotRead(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Integer is too large');

        (new ByteReader("\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF"))->uint(8);
    }

    public function testPositionCanBeSetAndRead(): void
    {
        $reader = new ByteReader('abcdef', 2);

        $this->assertSame(2, $reader->position());
        $this->assertSame('cd', $reader->bytes(2));
        $this->assertSame(4, $reader->position());

        $reader->seek(1);
        $this->assertSame('bc', $reader->bytes(2));

        $reader->seek(6);
        $this->assertSame(0, $reader->remaining());
    }

    #[DataProvider('invalidPositionProvider')]
    public function testPositionOutsideTheDataIsNotAllowed(int $position): void
    {
        $this->expectException(UnexpectedValueException::class);

        (new ByteReader('abc'))->seek($position);
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function invalidPositionProvider(): iterable
    {
        yield 'past the end' => [4];
        yield 'negative' => [-1];
    }

    public function testBytesCanBePeekedWithoutMovingThePosition(): void
    {
        $reader = new ByteReader('abcdef');
        $reader->skip(1);

        $this->assertSame('bcd', $reader->peek(3));
        $this->assertSame(1, $reader->position());
        $this->assertSame('bcd', $reader->bytes(3));
    }

    public function testPeekingBeyondTheDataIsNotAllowed(): void
    {
        $this->expectException(UnexpectedValueException::class);

        (new ByteReader('abc'))->peek(4);
    }

    public function testNullTerminatedStringsAreRead(): void
    {
        $reader = new ByteReader("name\x00\x00rest");

        $this->assertSame('name', $reader->cString());
        $this->assertSame('', $reader->cString());
        $this->assertSame('rest', $reader->rest());
    }

    public function testUnterminatedStringsAreNotRead(): void
    {
        $this->expectException(UnexpectedValueException::class);

        (new ByteReader('name'))->cString();
    }
}
