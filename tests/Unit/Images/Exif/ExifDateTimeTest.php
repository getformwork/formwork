<?php

namespace Formwork\Tests\Unit\Images\Exif;

use Formwork\Images\Exif\ExifDateTime;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(ExifDateTime::class)]
final class ExifDateTimeTest extends TestCase
{
    #[DataProvider('exifDataProvider')]
    public function testDatesAreCreatedFromExifData(string $datetime, ?string $subseconds, ?string $timeoffset, string $expected): void
    {
        $exifDateTime = ExifDateTime::createFromExifData($datetime, $subseconds, $timeoffset);

        $this->assertInstanceOf(ExifDateTime::class, $exifDateTime);
        $this->assertSame($expected, (string) $exifDateTime);
    }

    /**
     * @return iterable<string, array{string, ?string, ?string, string}>
     */
    public static function exifDataProvider(): iterable
    {
        yield 'only the date' => ['2024:01:02 03:04:05', null, null, '2024:01:02 03:04:05.000000+00:00'];
        yield 'subseconds and offset' => ['2024:01:02 03:04:05', '5', '+02:00', '2024:01:02 03:04:05.500000+02:00'];
        yield 'empty subseconds' => ['2024:01:02 03:04:05', '', null, '2024:01:02 03:04:05.000000+00:00'];
        yield 'subseconds of spaces' => ['2024:01:02 03:04:05', '   ', null, '2024:01:02 03:04:05.000000+00:00'];
        yield 'subseconds padded with null bytes' => ['2024:01:02 03:04:05', "12\x00", null, '2024:01:02 03:04:05.120000+00:00'];
        yield 'more than six digits of subseconds' => ['2024:01:02 03:04:05', '1234567', null, '2024:01:02 03:04:05.123456+00:00'];
        yield 'invalid subseconds' => ['2024:01:02 03:04:05', 'ab', null, '2024:01:02 03:04:05.000000+00:00'];
        yield 'an offset of spaces' => ['2024:01:02 03:04:05', null, '      ', '2024:01:02 03:04:05.000000+00:00'];
        yield 'an offset padded with null bytes' => ['2024:01:02 03:04:05', null, "-05:00\x00", '2024:01:02 03:04:05.000000-05:00'];
        yield 'an invalid offset' => ['2024:01:02 03:04:05', null, 'Z', '2024:01:02 03:04:05.000000+00:00'];
        yield 'a date padded with null bytes' => ["2024:01:02 03:04:05\x00", null, null, '2024:01:02 03:04:05.000000+00:00'];
    }

    public function testInvalidDatesAreNotCreated(): void
    {
        // Unknown dates are written with spaces in place of digits
        $this->assertFalse(ExifDateTime::createFromExifData('    :  :     :  :  '));
        $this->assertFalse(ExifDateTime::createFromExifData("2024:01:02\x0003:04:05"));
    }
}
