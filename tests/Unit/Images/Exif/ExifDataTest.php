<?php

namespace Formwork\Tests\Unit\Images\Exif;

use Formwork\Images\Exif\ExifData;
use Formwork\Images\Exif\ExifDateTime;
use Formwork\Images\Exif\ExifReader;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Images\Fixtures\TiffFixture;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(ExifData::class)]
#[UsesClass(ExifReader::class)]
#[UsesClass(ExifDateTime::class)]
final class ExifDataTest extends TestCase
{
    public function testDataIsKeptAsGiven(): void
    {
        $data = TiffFixture::tiff([0x010F => [TiffFixture::ASCII, 'Canon']]);

        $this->assertSame($data, (new ExifData($data))->getData());
    }

    public function testTagsHaveRawAndParsedValues(): void
    {
        $exifData = $this->exifData([0x010F => [TiffFixture::ASCII, 'Canon'], 0x0112 => [TiffFixture::SHORT, 6]]);

        $this->assertSame(['Make' => ['Canon'], 'Orientation' => [6, 'Rotated 90 degrees, clockwise']], $exifData->getTags());
        $this->assertSame(['Make' => 'Canon', 'Orientation' => 'Rotated 90 degrees, clockwise'], $exifData->toArray());
        $this->assertSame(6, $exifData->getRaw('Orientation'));
        $this->assertSame('Rotated 90 degrees, clockwise', $exifData->get('Orientation'));
        $this->assertSame('Canon', $exifData->get('Make'));
    }

    public function testMissingTagsHaveDefaultValues(): void
    {
        $exifData = $this->exifData([0x010F => [TiffFixture::ASCII, 'Canon']]);

        $this->assertFalse($exifData->has('Model'));
        $this->assertSame('default', $exifData->get('Model', 'default'));
        $this->assertSame('default', $exifData->getRaw('Model', 'default'));
    }

    public function testMultipleTagsExistOnlyIfAllOfThemExist(): void
    {
        $exifData = $this->exifData([0x010F => [TiffFixture::ASCII, 'Canon'], 0x0110 => [TiffFixture::ASCII, 'EOS 5D']]);

        $this->assertTrue($exifData->hasMultiple(['Make', 'Model']));
        $this->assertFalse($exifData->hasMultiple(['Make', 'LensModel']));
    }

    public function testPositionDataNeedsLatitudeAndLongitude(): void
    {
        $latitude = [0x0002 => [TiffFixture::RATIONAL, [[45, 1], [0, 1], [0, 1]]]];
        $longitude = [0x0004 => [TiffFixture::RATIONAL, [[9, 1], [0, 1], [0, 1]]]];

        $this->assertTrue($this->exifData([], [], $latitude + $longitude)->hasPositionData());
        $this->assertFalse($this->exifData([], [], $latitude)->hasPositionData());
    }

    #[DataProvider('formattedValueProvider')]
    public function testValuesAreFormatted(string $method, mixed $expected): void
    {
        $exifData = $this->exifData([0x010F => [TiffFixture::ASCII, 'Canon']], [
            0x829A => [TiffFixture::RATIONAL, [[1, 250]]],
            0x829D => [TiffFixture::RATIONAL, [[28, 10]]],
            0x8827 => [TiffFixture::SHORT, 400],
            0x9204 => [TiffFixture::SRATIONAL, [[-1, 3]]],
            0x920A => [TiffFixture::RATIONAL, [[50, 1]]],
            0xA001 => [TiffFixture::SHORT, 1],
            0xA434 => [TiffFixture::ASCII, 'EF50mm f/1.8'],
        ]);

        $this->assertSame($expected, $exifData->{$method}());
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function formattedValueProvider(): iterable
    {
        yield 'lens model' => ['lensModel', 'EF50mm ƒ/1.8'];
        yield 'focal length' => ['focalLength', '50 mm'];
        yield 'exposure time' => ['exposureTime', '1/250 s'];
        yield 'aperture' => ['aperture', 'ƒ/2.8'];
        yield 'photographic sensitivity' => ['photographicSensitivity', 'ISO 400'];
        yield 'exposure compensation' => ['exposureCompensation', '-0.33 EV'];
        yield 'color space' => ['colorSpace', 'sRGB'];
    }

    #[DataProvider('missingValueProvider')]
    public function testMissingValuesAreNotFormatted(string $method): void
    {
        $exifData = $this->exifData([0x010F => [TiffFixture::ASCII, 'Canon']]);

        $this->assertNull($exifData->{$method}());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function missingValueProvider(): iterable
    {
        yield 'date and time' => ['dateTimeOriginal'];
        yield 'lens model' => ['lensModel'];
        yield 'focal length' => ['focalLength'];
        yield 'exposure time' => ['exposureTime'];
        yield 'aperture' => ['aperture'];
        yield 'photographic sensitivity' => ['photographicSensitivity'];
        yield 'exposure compensation' => ['exposureCompensation'];
        yield 'white balance' => ['hasAutoWhiteBalance'];
        yield 'flash' => ['hasFlashFired'];
        yield 'metering mode' => ['meteringMode'];
        yield 'color space' => ['colorSpace'];
    }

    public function testOriginalDateAndTimeIsParsed(): void
    {
        $exifData = $this->exifData([], [0x9003 => [TiffFixture::ASCII, '2024:05:06 07:08:09']]);

        $this->assertSame('2024:05:06 07:08:09.000000+00:00', (string) $exifData->dateTimeOriginal());
    }

    public function testInvalidOriginalDateAndTimeIsMissing(): void
    {
        $exifData = $this->exifData([], [0x9003 => [TiffFixture::ASCII, 'not a date']]);

        $this->assertNull($exifData->dateTimeOriginal());
    }

    #[DataProvider('makeAndModelProvider')]
    public function testMakeIsNotRepeatedInTheModel(?string $make, ?string $model, ?string $expected): void
    {
        $tags = [0x0112 => [TiffFixture::SHORT, 1]];

        if ($make !== null) {
            $tags[0x010F] = [TiffFixture::ASCII, $make];
        }

        if ($model !== null) {
            $tags[0x0110] = [TiffFixture::ASCII, $model];
        }

        $this->assertSame($expected, $this->exifData($tags)->makeAndModel());
    }

    /**
     * @return iterable<string, array{?string, ?string, ?string}>
     */
    public static function makeAndModelProvider(): iterable
    {
        yield 'model with the make' => ['Canon', 'Canon EOS 5D', 'Canon EOS 5D'];
        yield 'model without the make' => ['NIKON', 'D850', 'NIKON D850'];
        yield 'only the make' => ['NIKON', null, 'NIKON'];
        yield 'neither' => [null, null, null];
    }

    #[DataProvider('exposureProgramProvider')]
    public function testExposureProgramsAreAbbreviated(int $exposureProgram, string $expected): void
    {
        $exifData = $this->exifData([], [0x8822 => [TiffFixture::SHORT, $exposureProgram]]);

        $this->assertSame($expected, $exifData->exposureProgram());
    }

    /**
     * @return iterable<string, array{int, string}>
     */
    public static function exposureProgramProvider(): iterable
    {
        yield 'manual' => [1, 'M'];
        yield 'program' => [2, 'P'];
        yield 'aperture priority' => [3, 'A'];
        yield 'shutter priority' => [4, 'S'];
        yield 'not defined' => [0, 'AUTO'];
        yield 'portrait mode' => [7, 'AUTO'];
    }

    #[DataProvider('meteringModeProvider')]
    public function testMeteringModesAreGrouped(int $meteringMode, string $expected): void
    {
        $exifData = $this->exifData([], [0x9207 => [TiffFixture::SHORT, $meteringMode]]);

        $this->assertSame($expected, $exifData->meteringMode());
    }

    /**
     * @return iterable<string, array{int, string}>
     */
    public static function meteringModeProvider(): iterable
    {
        yield 'unknown' => [0, 'average'];
        yield 'average' => [1, 'average'];
        yield 'center weighted average' => [2, 'average'];
        yield 'spot' => [3, 'spot'];
        yield 'multi spot' => [4, 'evaluative'];
        yield 'pattern' => [5, 'evaluative'];
        yield 'partial' => [6, 'partial'];
        yield 'other' => [255, 'average'];
    }

    #[DataProvider('flashProvider')]
    public function testFlashHasFiredIfItsFirstBitIsSet(int $flash, bool $expected): void
    {
        $exifData = $this->exifData([], [0x9209 => [TiffFixture::SHORT, $flash]]);

        $this->assertSame($expected, $exifData->hasFlashFired());
    }

    /**
     * @return iterable<string, array{int, bool}>
     */
    public static function flashProvider(): iterable
    {
        yield 'fired' => [0x01, true];
        yield 'fired with red-eye reduction' => [0x41, true];
        yield 'not fired' => [0x00, false];
        yield 'not fired in compulsory mode' => [0x10, false];
    }

    #[DataProvider('whiteBalanceProvider')]
    public function testWhiteBalanceIsAutomaticOnlyIfSoRecorded(int $whiteBalance, bool $expected): void
    {
        $exifData = $this->exifData([], [0xA403 => [TiffFixture::SHORT, $whiteBalance]]);

        $this->assertSame($expected, $exifData->hasAutoWhiteBalance());
    }

    /**
     * @return iterable<string, array{int, bool}>
     */
    public static function whiteBalanceProvider(): iterable
    {
        yield 'automatic' => [0, true];
        yield 'manual' => [1, false];
    }

    /**
     * @param array<int, array{int, mixed}> $ifd0
     * @param array<int, array{int, mixed}> $exif
     * @param array<int, array{int, mixed}> $gps
     */
    private function exifData(array $ifd0, array $exif = [], array $gps = []): ExifData
    {
        return new ExifData(TiffFixture::tiff($ifd0, $exif, $gps));
    }
}
