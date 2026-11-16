<?php

namespace Formwork\Tests\Unit\Images\Exif;

use Formwork\Images\Exif\ExifDateTime;
use Formwork\Images\Exif\ExifReader;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Images\Fixtures\TiffFixture;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use UnexpectedValueException;

#[CoversClass(ExifReader::class)]
#[UsesClass(ExifDateTime::class)]
final class ExifReaderTest extends TestCase
{
    public function testUnchangedValuesAreReadOnlyAsRawValues(): void
    {
        $tags = $this->read([0x010F => [TiffFixture::ASCII, 'Canon']]);

        $this->assertSame(['Canon'], $tags['Make']);
    }

    public function testSectionsThatAreNotTagsAreIgnored(): void
    {
        $tags = $this->read([0x010F => [TiffFixture::ASCII, 'Canon']], [0xA001 => [TiffFixture::SHORT, 1]], [0x0001 => [TiffFixture::ASCII, 'N']]);

        $this->assertSame(['Make', 'ColorSpace', 'GPSLatitudeRef'], array_keys($tags));
    }

    #[DataProvider('describedValueProvider')]
    public function testKnownValuesAreDescribed(int $tag, int $type, mixed $value, string $key, mixed $expected): void
    {
        $tags = $this->read([], [$tag => [$type, $value]]);

        $this->assertSame($expected, $tags[$key][1]);
    }

    /**
     * @return iterable<string, array{int, int, mixed, string, mixed}>
     */
    public static function describedValueProvider(): iterable
    {
        yield 'value of a table' => [0xA001, TiffFixture::SHORT, 1, 'ColorSpace', 'sRGB'];
        yield 'value of a function' => [0x9101, TiffFixture::UNDEFINED, "\x01\x02\x03\x00", 'ComponentsConfiguration', ['Y', 'Cb', 'Cr', '-']];
        yield 'binary value of a table' => [0xA300, TiffFixture::UNDEFINED, "\x03", 'FileSource', 'DSC'];
        yield 'exposure time in seconds' => [0x829A, TiffFixture::RATIONAL, [[25, 10]], 'ExposureTime', 2.5];
    }

    public function testUnknownValuesAreNotDescribed(): void
    {
        $tags = $this->read([0x0112 => [TiffFixture::SHORT, 9]]);

        $this->assertSame([9], $tags['Orientation']);
    }

    /**
     * @param list<array{int, int}> $value
     */
    #[DataProvider('rationalProvider')]
    public function testRationalsAreParsed(int $tag, int $type, array $value, string $key, mixed $expected): void
    {
        $tags = $this->read([], [$tag => [$type, $value]]);

        $this->assertSame($expected, $tags[$key][1]);
    }

    /**
     * @return iterable<string, array{int, int, list<array{int, int}>, string, mixed}>
     */
    public static function rationalProvider(): iterable
    {
        yield 'single value' => [0x920A, TiffFixture::RATIONAL, [[50, 1]], 'FocalLength', 50.0];
        yield 'signed value' => [0x9204, TiffFixture::SRATIONAL, [[-1, 4]], 'ExposureBiasValue', -0.25];
        yield 'list of values' => [0xA432, TiffFixture::RATIONAL, [[24, 1], [70, 1], [28, 10], [4, 1]], 'LensSpecification', [24.0, 70.0, 2.8, 4.0]];
    }

    public function testRationalsWithZeroDenominatorAreNotNumbers(): void
    {
        $tags = $this->read([], [], [0x0006 => [TiffFixture::RATIONAL, [[0, 0]]], 0x000D => [TiffFixture::RATIONAL, [[5, 0]]]]);

        $this->assertNan($tags['GPSAltitude'][1]);
        $this->assertInfinite($tags['GPSSpeed'][1]);
    }

    #[DataProvider('coordinateProvider')]
    public function testCoordinatesAreSignedByTheirReference(?string $reference, float $expected): void
    {
        $gps = [0x0002 => [TiffFixture::RATIONAL, [[45, 1], [30, 1], [36, 1]]]];

        if ($reference !== null) {
            $gps[0x0001] = [TiffFixture::ASCII, $reference];
        }

        $tags = $this->read([], [], $gps);

        $this->assertSame($expected, $tags['GPSLatitude'][1]);
    }

    /**
     * @return iterable<string, array{?string, float}>
     */
    public static function coordinateProvider(): iterable
    {
        yield 'north' => ['N', 45.51];
        yield 'south' => ['S', -45.51];
        yield 'without reference' => [null, 45.51];
    }

    public function testCoordinatesWithMissingComponentsAreCompleted(): void
    {
        $tags = $this->read([], [], [0x0003 => [TiffFixture::ASCII, 'W'], 0x0004 => [TiffFixture::RATIONAL, [[9, 1], [15, 1]]]]);

        $this->assertSame(-9.25, $tags['GPSLongitude'][1]);
    }

    public function testDatesIncludeTheirSubsecondsAndOffsets(): void
    {
        $tags = $this->read([], [
            0x9003 => [TiffFixture::ASCII, '2024:05:06 07:08:09'],
            0x9291 => [TiffFixture::ASCII, '25'],
            0x9011 => [TiffFixture::ASCII, '+02:00'],
        ]);

        $this->assertInstanceOf(ExifDateTime::class, $tags['DateTimeOriginal'][1]);
        $this->assertSame('2024:05:06 07:08:09.250000+02:00', (string) $tags['DateTimeOriginal'][1]);
    }

    public function testInvalidDatesAreNotParsed(): void
    {
        $tags = $this->read([], [0x9003 => [TiffFixture::ASCII, 'not a date']]);

        $this->assertSame(['not a date', null], $tags['DateTimeOriginal']);
    }

    #[DataProvider('textProvider')]
    public function testTextsAreDecodedFromTheirEncoding(string $value, bool $bigEndian): void
    {
        $tags = $this->read([], [0x9286 => [TiffFixture::UNDEFINED, $value]], bigEndian: $bigEndian);

        $this->assertSame('Hi', $tags['UserComment'][1]);
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function textProvider(): iterable
    {
        yield 'ASCII' => ["ASCII\x00\x00\x00Hi\x00\x00", false];
        yield 'JIS' => ["JIS\x00\x00\x00\x00\x00Hi", false];
        yield 'little-endian Unicode' => ["UNICODE\x00H\x00i\x00", false];
        yield 'big-endian Unicode' => ["UNICODE\x00\x00H\x00i", true];
        yield 'undefined encoding' => ["\x00\x00\x00\x00\x00\x00\x00\x00Hi", false];
    }

    #[DataProvider('versionProvider')]
    public function testVersionsAreFormatted(string $value, string $expected): void
    {
        $tags = $this->read([], [0x9000 => [TiffFixture::UNDEFINED, $value]]);

        $this->assertSame($expected, $tags['ExifVersion'][1]);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function versionProvider(): iterable
    {
        yield 'minor version' => ['0231', '2.31'];
        yield 'major version' => ['0100', '1.0'];
    }

    public function testBinaryValuesThatAreNotDigitsAreSkipped(): void
    {
        $tags = $this->read([0x010F => [TiffFixture::ASCII, 'Canon']], [0xA301 => [TiffFixture::UNDEFINED, 'A'], 0xA300 => [TiffFixture::UNDEFINED, "\x03"]]);

        $this->assertArrayNotHasKey('SceneType', $tags);
        $this->assertSame(['Canon'], $tags['Make']);
        $this->assertSame(["\x03", 'DSC'], $tags['FileSource']);
    }

    public function testUndefinedTagsAreNamedAfterTheirStandardTags(): void
    {
        $tags = $this->read([], [0xA434 => [TiffFixture::ASCII, 'EF50mm f/1.8'], 0xC000 => [TiffFixture::ASCII, 'Unknown']]);

        $this->assertSame(['EF50mm f/1.8'], $tags['LensModel']);
        $this->assertArrayNotHasKey('UndefinedTag:0xA434', $tags);
        $this->assertArrayNotHasKey('UndefinedTag:0xC000', $tags);
    }

    public function testAliasesShareTheValueOfTheirTags(): void
    {
        $tags = $this->read([], [0x8827 => [TiffFixture::SHORT, 400]], [0x0000 => [TiffFixture::BYTE, "\x02\x03\x00\x00"]]);

        $this->assertSame([400], $tags['PhotographicSensitivity']);
        $this->assertSame([400], $tags['ISOSpeedRatings']);
        $this->assertSame("\x02\x03\x00\x00", $tags['GPSVersionID'][0]);
        $this->assertSame('2.3.0.0', $tags['GPSVersionID'][1]);
    }

    public function testStringsThatAreNotValidUtf8AreSkipped(): void
    {
        $tags = $this->read([0x010F => [TiffFixture::ASCII, "\xFF\xFE"], 0x0110 => [TiffFixture::ASCII, 'EOS 5D']]);

        $this->assertArrayNotHasKey('Make', $tags);
        $this->assertSame(['EOS 5D'], $tags['Model']);
    }

    public function testDataWithoutTagsCannotBeRead(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Cannot read EXIF data');
        $data = 'Not EXIF data';
        (new ExifReader())->read($data);
    }

    /**
     * @param array<int, array{int, mixed}> $ifd0
     * @param array<int, array{int, mixed}> $exif
     * @param array<int, array{int, mixed}> $gps
     *
     * @return array<string, mixed>
     */
    private function read(array $ifd0, array $exif = [], array $gps = [], bool $bigEndian = false): array
    {
        $data = TiffFixture::tiff($ifd0, $exif, $gps, $bigEndian);
        return (new ExifReader())->read($data);
    }
}
