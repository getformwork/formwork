<?php

namespace Formwork\Tests\Unit\Images\Handler;

use Formwork\Images\ColorProfile\ColorProfile;
use Formwork\Images\ColorProfile\ColorSpace;
use Formwork\Images\Exif\ExifData;
use Formwork\Images\Handler\JpegHandler;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Images\Fixtures\GdImages;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use UnexpectedValueException;

#[CoversClass(JpegHandler::class)]
final class JpegHandlerTest extends TestCase
{
    private const string FIXTURES = __DIR__ . '/../Fixtures';

    public function testInfoIsReadFromAnEncodedImage(): void
    {
        $info = (new JpegHandler(GdImages::jpeg(40, 30)))->getInfo();

        $this->assertSame('image/jpeg', $info->mimeType());
        $this->assertSame(40, $info->width());
        $this->assertSame(30, $info->height());
        $this->assertSame(ColorSpace::RGB, $info->colorSpace());
        $this->assertSame(8, $info->colorDepth());
        $this->assertFalse($info->hasAlphaChannel());
        $this->assertFalse($info->isAnimation());
    }

    #[DataProvider('frameProvider')]
    public function testInfoIsReadFromEveryKindOfFrame(int $marker, int $components, ColorSpace $colorSpace): void
    {
        $frame = "\x08" . pack('nn', 20, 10) . chr($components) . str_repeat("\x01\x11\x00", $components);
        $handler = new JpegHandler("\xff\xd8" . GdImages::jpegSegment($marker, $frame) . "\xff\xd9");

        $info = $handler->getInfo();

        $this->assertSame(10, $info->width());
        $this->assertSame(20, $info->height());
        $this->assertSame($colorSpace, $info->colorSpace());
    }

    /**
     * @return iterable<string, array{int, int, ColorSpace}>
     */
    public static function frameProvider(): iterable
    {
        yield 'baseline' => [0xc0, 3, ColorSpace::RGB];
        yield 'extended sequential' => [0xc1, 1, ColorSpace::Grayscale];
        yield 'progressive' => [0xc2, 4, ColorSpace::CMYK];
        yield 'lossless' => [0xc3, 3, ColorSpace::RGB];
        yield 'differential sequential' => [0xc5, 3, ColorSpace::RGB];
        yield 'arithmetic' => [0xc9, 3, ColorSpace::RGB];
        yield 'differential arithmetic' => [0xcd, 3, ColorSpace::RGB];
    }

    public function testTablesAreNotMistakenForFrames(): void
    {
        $handler = new JpegHandler("\xff\xd8" . GdImages::jpegSegment(0xc4, str_repeat("\x00", 20)) . "\xff\xd9");

        $this->assertSame(0, $handler->getInfo()->width());
    }

    public function testTruncatedFramesAreNotAccepted(): void
    {
        $handler = new JpegHandler("\xff\xd8" . GdImages::jpegSegment(0xc0, "\x08\x00") . "\xff\xd9");

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid start of frame segment');

        $handler->getInfo();
    }

    public function testColorProfilesAreSetAndRead(): void
    {
        $handler = new JpegHandler(GdImages::jpeg());

        $this->assertFalse($handler->hasColorProfile());
        $this->assertNull($handler->getColorProfile());

        $handler->setColorProfile($this->colorProfile());

        $this->assertTrue($handler->hasColorProfile());
        $this->assertSame($this->colorProfile()->getData(), $handler->getColorProfile()?->getData());
        $this->assertDecodable($handler);
    }

    public function testLargeColorProfilesAreSplitAndJoined(): void
    {
        $profile = new ColorProfile($this->colorProfile()->getData() . random_bytes(150_000));
        $handler = new JpegHandler(GdImages::jpeg());

        $handler->setColorProfile($profile);

        $this->assertGreaterThan(2, substr_count($handler->getData(), "ICC_PROFILE\x00"));
        $this->assertSame($profile->getData(), $handler->getColorProfile()?->getData());
        $this->assertDecodable($handler);
    }

    public function testAllTheChunksOfAColorProfileAreRemoved(): void
    {
        $profile = new ColorProfile($this->colorProfile()->getData() . random_bytes(150_000));
        $handler = new JpegHandler(GdImages::jpeg());
        $handler->setColorProfile($profile);

        $handler->removeColorProfile();

        $this->assertFalse($handler->hasColorProfile());
        $this->assertSame(GdImages::jpeg(), $handler->getData());
    }

    public function testColorProfilesAreReplacedInsteadOfDuplicated(): void
    {
        $handler = new JpegHandler(GdImages::jpeg());

        $handler->setColorProfile($this->colorProfile());
        $handler->setColorProfile($this->colorProfile());

        $this->assertSame(1, substr_count($handler->getData(), "ICC_PROFILE\x00"));
    }

    public function testIncompleteColorProfilesAreNotAccepted(): void
    {
        $segment = GdImages::jpegSegment(0xe2, "ICC_PROFILE\x00\x01\x02" . $this->colorProfile()->getData());
        $handler = new JpegHandler("\xff\xd8" . $segment . "\xff\xd9");

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Unexpected profile chunk count');

        $handler->getColorProfile();
    }

    public function testTruncatedColorProfileSegmentsAreNotAccepted(): void
    {
        $handler = new JpegHandler("\xff\xd8" . GdImages::jpegSegment(0xe2, "ICC_PROFILE\x00\x01") . "\xff\xd9");

        $this->expectException(UnexpectedValueException::class);

        $handler->getColorProfile();
    }

    public function testExifDataIsSetAndRead(): void
    {
        $handler = new JpegHandler(GdImages::jpeg());

        $this->assertFalse($handler->hasExifData());
        $this->assertNull($handler->getExifData());

        $handler->setExifData($this->exifData());

        $this->assertTrue($handler->hasExifData());
        $this->assertSame($this->exifData()->getData(), $handler->getExifData()?->getData());
        $this->assertDecodable($handler);
    }

    public function testExifDataIsReplacedInsteadOfDuplicated(): void
    {
        $handler = new JpegHandler(GdImages::jpeg());

        $handler->setExifData($this->exifData());
        $handler->setExifData($this->exifData());

        $this->assertSame(1, substr_count($handler->getData(), "Exif\x00\x00"));
    }

    public function testExifDataIsRemoved(): void
    {
        $handler = new JpegHandler(GdImages::jpeg());
        $handler->setExifData($this->exifData());

        $handler->removeExifData();

        $this->assertFalse($handler->hasExifData());
        $this->assertSame(GdImages::jpeg(), $handler->getData());
    }

    public function testMetadataFollowsTheJfifSegment(): void
    {
        $handler = new JpegHandler(GdImages::jpeg());
        $handler->setExifData($this->exifData());
        $handler->setColorProfile($this->colorProfile());

        $this->assertSame("\xff\xd8\xff\xe0", substr($handler->getData(), 0, 4));
        $this->assertSame('JFIF', substr($handler->getData(), 6, 4));
    }

    public function testMetadataFollowsTheStartOfImageMarkerWithoutJfifSegment(): void
    {
        $handler = new JpegHandler("\xff\xd8" . GdImages::jpegSegment(0xc0, "\x08\x00\x0a\x00\x14\x03") . "\xff\xd9");
        $handler->setExifData($this->exifData());

        $this->assertSame("\xff\xd8\xff\xe1", substr($handler->getData(), 0, 4));
        $this->assertTrue($handler->hasExifData());
        $this->assertSame(20, $handler->getInfo()->width());
    }

    public function testRemovingMetadataKeepsOtherSegments(): void
    {
        $jpeg = "\xff\xd8" . GdImages::jpegSegment(0xe1, "Exif\x00\x00" . $this->exifData()->getData()) . GdImages::jpegSegment(0xe1, 'http://ns.adobe.com/xap/1.0/') . GdImages::jpegSegment(0xe2, 'other') . "\xff\xd9";
        $handler = new JpegHandler($jpeg);

        $handler->removeExifData();

        $this->assertSame("\xff\xd8" . GdImages::jpegSegment(0xe1, 'http://ns.adobe.com/xap/1.0/') . GdImages::jpegSegment(0xe2, 'other') . "\xff\xd9", $handler->getData());
    }

    public function testMissingStartOfImageMarkersAreNotAccepted(): void
    {
        $handler = new JpegHandler('not a JPEG image');

        $this->expectException(\InvalidArgumentException::class);

        $handler->setExifData($this->exifData());
    }

    public function testExifDataNotFittingInASegmentIsNotAccepted(): void
    {
        $handler = new JpegHandler(GdImages::jpeg());

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Data is too large to be embedded in a JPEG segment');

        $handler->setExifData(new ExifData($this->exifData()->getData() . str_repeat("\x00", 70_000)));
    }

    public function testImagesAreCreatedFromGdImages(): void
    {
        $handler = JpegHandler::fromGdImage(GdImages::image(16, 12));

        $this->assertSame(16, $handler->getInfo()->width());
        $this->assertSame(12, $handler->getInfo()->height());
    }

    private function colorProfile(): ColorProfile
    {
        return new ColorProfile((string) file_get_contents(self::FIXTURES . '/srgb.icc'));
    }

    private function exifData(): ExifData
    {
        return new ExifData((string) file_get_contents(self::FIXTURES . '/exif.tiff'));
    }

    private function assertDecodable(JpegHandler $handler): void
    {
        $this->assertNotFalse(@imagecreatefromstring($handler->getData()));
    }
}
