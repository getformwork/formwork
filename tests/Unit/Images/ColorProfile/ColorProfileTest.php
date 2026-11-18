<?php

namespace Formwork\Tests\Unit\Images\ColorProfile;

use Formwork\Images\ColorProfile\ColorProfile;
use Formwork\Images\ColorProfile\ColorSpace;
use Formwork\Images\ColorProfile\DeviceClass;
use Formwork\Images\ColorProfile\RenderingIntent;
use Formwork\Tests\TestCase;
use Formwork\Tests\UsesTempDirectory;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use UnexpectedValueException;

#[CoversClass(ColorProfile::class)]
#[UsesTempDirectory]
final class ColorProfileTest extends TestCase
{
    private const string FIXTURES = __DIR__ . '/../Fixtures';

    public function testProfilesAreReadFromAFile(): void
    {
        $profile = ColorProfile::fromFile(self::FIXTURES . '/srgb.icc');

        $this->assertSame('sRGB built-in', $profile->name());
        $this->assertSame('No copyright, use freely', $profile->copyright());
        $this->assertSame('4.4.0', $profile->profileVersion());
        $this->assertSame(DeviceClass::Display, $profile->deviceClass());
        $this->assertSame(ColorSpace::RGB, $profile->colorSpace());
        $this->assertSame('XYZ', $profile->connectionSpace());
        $this->assertSame('APPL', $profile->primaryPlatform());
        $this->assertSame(RenderingIntent::Perceptual, $profile->renderingIntent());
        $this->assertSame(file_get_contents(self::FIXTURES . '/srgb.icc'), $profile->getData());
    }

    public function testProfilesInAnotherColorSpaceAreRead(): void
    {
        $profile = ColorProfile::fromFile(self::FIXTURES . '/lab.icc');

        $this->assertSame('Lab identity built-in', $profile->name());
        $this->assertSame('2.1.0', $profile->profileVersion());
        $this->assertSame(DeviceClass::AbstractProfile, $profile->deviceClass());
        $this->assertSame(ColorSpace::LAB, $profile->colorSpace());
        $this->assertSame('Lab', $profile->connectionSpace());
    }

    public function testProfilesAreExported(): void
    {
        $profile = ColorProfile::fromFile(self::FIXTURES . '/srgb.icc');

        $profile->export(TESTS_TMP_PATH . '/profile.icc');

        $this->assertSame($profile->getData(), file_get_contents(TESTS_TMP_PATH . '/profile.icc'));
    }

    public function testLegacyDescriptionsAndTextTagsAreRead(): void
    {
        $desc = "desc\x00\x00\x00\x00" . pack('N', 7) . "Legacy\x00" . str_repeat("\x00", 20);
        $text = "text\x00\x00\x00\x00" . "Copyright\x00";

        $profile = new ColorProfile($this->profile(['desc' => $desc, 'cprt' => $text]));

        $this->assertSame('Legacy', $profile->name());
        $this->assertSame('Copyright', $profile->copyright());
    }

    public function testMultiLocalizedStringsAreRead(): void
    {
        $string = mb_convert_encoding('Naïve', 'UTF-16BE', 'UTF-8');
        $mluc = "mluc\x00\x00\x00\x00" . pack('NN', 1, 12) . 'enUS' . pack('NN', strlen($string), 28) . $string;

        $profile = new ColorProfile($this->profile(['desc' => $mluc]));

        $this->assertSame('Naïve', $profile->name());
    }

    public function testMissingAndUnknownTagsHaveDefaults(): void
    {
        $profile = new ColorProfile($this->profile(['desc' => 'xxxxxxxx']));

        $this->assertSame('', $profile->name());
        $this->assertSame('', $profile->copyright());
    }

    public function testMalformedTagValuesHaveDefaults(): void
    {
        $truncatedDesc = "desc\x00\x00\x00\x00\x00\x00";
        $outOfRangeMluc = "mluc\x00\x00\x00\x00" . pack('NN', 1, 12) . 'enUS' . pack('NN', 100, 28);
        $truncatedMluc = "mluc\x00\x00\x00\x00" . pack('NN', 5, 12);

        foreach ([$truncatedDesc, $outOfRangeMluc, $truncatedMluc] as $value) {
            $this->assertSame('', (new ColorProfile($this->profile(['desc' => $value])))->name());
        }
    }

    public function testUnrestrictedDescriptionLengthsAreLimitedToTheTag(): void
    {
        $desc = "desc\x00\x00\x00\x00" . pack('N', 1000) . "Short\x00";

        $this->assertSame('Short', (new ColorProfile($this->profile(['desc' => $desc])))->name());
    }

    #[DataProvider('invalidProfileProvider')]
    public function testInvalidProfilesAreRejected(string $data): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid ICC profile data');

        new ColorProfile($data);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidProfileProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'missing signature' => [str_repeat("\x00", 200)];
        yield 'signature at the wrong offset' => [str_repeat("\x00", 40) . 'acsp' . str_repeat("\x00", 200)];
        yield 'missing tag table' => [substr(str_repeat("\x00", 36) . 'acsp', 0, 40)];
        yield 'truncated tag count' => [str_repeat("\x00", 36) . 'acsp' . str_repeat("\x00", 88) . "\x00\x00"];
        yield 'tag table longer than the data' => [self::header() . pack('N', 5) . 'desc' . pack('NN', 144, 4)];
        yield 'huge tag count' => [self::header() . pack('N', 0xffffffff)];
        yield 'tag outside the profile' => [self::header() . pack('N', 1) . 'desc' . pack('NN', 1000, 20)];
    }

    #[DataProvider('headerValueProvider')]
    public function testHeaderValuesAreRecognized(int $offset, string $value, string $method, DeviceClass|ColorSpace|RenderingIntent $expected): void
    {
        $profile = new ColorProfile(substr_replace($this->profile([]), $value, $offset, strlen($value)));

        $this->assertSame($expected, $profile->{$method}());
    }

    /**
     * @return iterable<string, array{int, string, string, ColorSpace|DeviceClass|RenderingIntent}>
     */
    public static function headerValueProvider(): iterable
    {
        yield 'input device' => [12, 'scnr', 'deviceClass', DeviceClass::Input];
        yield 'display device' => [12, 'mntr', 'deviceClass', DeviceClass::Display];
        yield 'output device' => [12, 'prtr', 'deviceClass', DeviceClass::Output];
        yield 'device link' => [12, 'link', 'deviceClass', DeviceClass::Link];
        yield 'color space conversion' => [12, 'spac', 'deviceClass', DeviceClass::ColorSpace];
        yield 'abstract profile' => [12, 'abst', 'deviceClass', DeviceClass::AbstractProfile];
        yield 'named color' => [12, 'nmcl', 'deviceClass', DeviceClass::NamedColor];
        yield 'XYZ' => [16, 'XYZ ', 'colorSpace', ColorSpace::XYZ];
        yield 'Lab' => [16, 'Lab ', 'colorSpace', ColorSpace::LAB];
        yield 'Luv' => [16, 'Luv ', 'colorSpace', ColorSpace::LUV];
        yield 'YCbCr' => [16, 'YCbr', 'colorSpace', ColorSpace::YCbCr];
        yield 'Yxy' => [16, 'Yxy ', 'colorSpace', ColorSpace::XYY];
        yield 'RGB' => [16, 'RGB ', 'colorSpace', ColorSpace::RGB];
        yield 'gray' => [16, 'GRAY', 'colorSpace', ColorSpace::Grayscale];
        yield 'HSV' => [16, 'HSV ', 'colorSpace', ColorSpace::HSV];
        yield 'HLS' => [16, 'HLS ', 'colorSpace', ColorSpace::HLS];
        yield 'CMYK' => [16, 'CMYK', 'colorSpace', ColorSpace::CMYK];
        yield 'CMY' => [16, 'CMY ', 'colorSpace', ColorSpace::CMY];

        foreach (range(2, 15) as $colors) {
            yield "{$colors} colors" => [16, strtoupper(dechex($colors)) . 'CLR', 'colorSpace', ColorSpace::Palette];
        }

        yield 'perceptual' => [64, pack('N', 0), 'renderingIntent', RenderingIntent::Perceptual];
        yield 'media-relative colorimetric' => [64, pack('N', 1), 'renderingIntent', RenderingIntent::MediaRelative];
        yield 'saturation' => [64, pack('N', 3), 'renderingIntent', RenderingIntent::Saturation];
        yield 'ICC-absolute colorimetric' => [64, pack('N', 4), 'renderingIntent', RenderingIntent::IccAbsolute];
    }

    #[DataProvider('unexpectedValueProvider')]
    public function testUnexpectedHeaderValuesAreRejected(int $offset, string $value, string $method, string $message): void
    {
        $profile = new ColorProfile(substr_replace($this->profile([]), $value, $offset, strlen($value)));

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage($message);

        $profile->{$method}();
    }

    /**
     * @return iterable<string, array{int, string, string, string}>
     */
    public static function unexpectedValueProvider(): iterable
    {
        yield 'device class' => [12, 'zzzz', 'deviceClass', 'Unexpected device class'];
        yield 'color space' => [16, 'zzzz', 'colorSpace', 'Unexpected color space'];
        yield 'rendering intent' => [64, pack('N', 9), 'renderingIntent', 'Unexpected rendering intent'];
    }

    /**
     * @param array<string, string> $tags
     */
    private function profile(array $tags): string
    {
        $table = pack('N', count($tags));
        $body = '';
        $offset = 128 + 4 + 12 * count($tags);

        foreach ($tags as $signature => $value) {
            $table .= $signature . pack('NN', $offset + strlen($body), strlen($value));
            $body .= $value;
        }

        return self::header() . $table . $body;
    }

    private static function header(): string
    {
        return str_pad(str_repeat("\x00", 36) . 'acsp', 128, "\x00");
    }
}
