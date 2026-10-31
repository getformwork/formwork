<?php

namespace Formwork\Tests\Unit\Images\Binary;

use Formwork\Images\Binary\Bytes;
use Formwork\Images\Binary\Endianness;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use UnexpectedValueException;

#[CoversClass(Bytes::class)]
final class BytesTest extends TestCase
{
    public function testUnsignedIntegersCanBePackedInLittleEndianOrder(): void
    {
        $this->assertSame("\x02\x01", Bytes::pack(0x0102, 2, Endianness::Little));
        $this->assertSame("\x04\x03\x02\x01", Bytes::pack(0x01020304, 4, Endianness::Little));
        $this->assertSame("\x01\x02", Bytes::pack(0x0102, 2));
    }

    public function testUnsignedIntegersAreUnpackedAtAnOffset(): void
    {
        $this->assertSame(0x0203, Bytes::unpack("\x01\x02\x03\x04", 1, 2));
        $this->assertSame(0x0302, Bytes::unpack("\x01\x02\x03\x04", 1, 2, Endianness::Little));
        $this->assertSame(0, Bytes::unpack('abc', 3, 0));
    }

    #[DataProvider('invalidUnpackProvider')]
    public function testUnpackingBeyondTheDataIsNotAllowed(int $offset, int $size): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Unexpected end of data');

        Bytes::unpack('abc', $offset, $size);
    }

    /**
     * @return iterable<string, array{int, int}>
     */
    public static function invalidUnpackProvider(): iterable
    {
        yield 'past the end' => [2, 2];
        yield 'negative offset' => [-1, 1];
        yield 'negative size' => [0, -1];
        yield 'empty data' => [4, 0];
    }

    public function testPackingAndUnpackingAreSymmetricalInBothOrders(): void
    {
        foreach ([Endianness::Big, Endianness::Little] as $endianness) {
            $this->assertSame(0xCAFEBABE, Bytes::unpack(Bytes::pack(0xCAFEBABE, 4, $endianness), 0, 4, $endianness));
        }
    }

    public function testReplacementsReferToTheOriginalOffsets(): void
    {
        $data = '0123456789';

        $this->assertSame('01ABX789', Bytes::replace($data, [[6, 1, 'X'], [2, 3, 'AB'], [5, 1, '']]));
    }

    public function testReplacementsCanInsertData(): void
    {
        $this->assertSame('ab-cd', Bytes::replace('abcd', [[2, 0, '-']]));
    }

    public function testNoReplacementsLeaveTheDataUntouched(): void
    {
        $this->assertSame('abcd', Bytes::replace('abcd', []));
    }

    /**
     * @param list<array{int, int, string}> $replacements
     */
    #[DataProvider('invalidReplacementProvider')]
    public function testInvalidReplacementsAreNotApplied(array $replacements): void
    {
        $this->expectException(UnexpectedValueException::class);

        Bytes::replace('abcd', $replacements);
    }

    /**
     * @return iterable<string, array{list<array{int, int, string}>}>
     */
    public static function invalidReplacementProvider(): iterable
    {
        yield 'beyond the end' => [[[3, 2, '']]];
        yield 'negative offset' => [[[-1, 1, '']]];
        yield 'negative length' => [[[1, -1, '']]];
        yield 'overlapping' => [[[0, 3, ''], [2, 1, 'x']]];
    }
}
