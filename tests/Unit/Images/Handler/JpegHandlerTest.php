<?php

namespace Formwork\Tests\Unit\Images\Handler;

use Formwork\Images\ColorProfile\ColorProfile;
use Formwork\Images\ColorProfile\ColorSpace;
use Formwork\Images\Exif\ExifData;
use Formwork\Images\Handler\JpegHandler;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Images\Fixtures\GdImagesFixture;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use UnexpectedValueException;

#[CoversClass(JpegHandler::class)]
final class JpegHandlerTest extends TestCase
{
    private const string FIXTURES = __DIR__ . '/../Fixtures';

    public function testInfoIsReadFromAnEncodedImage(): void
    {
        $info = (new JpegHandler(GdImagesFixture::jpeg()))->getInfo();

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
        $handler = new JpegHandler("\xff\xd8" . GdImagesFixture::jpegSegment($marker, $frame) . "\xff\xd9");

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
        yield 'differential lossless' => [0xc7, 3, ColorSpace::RGB];
        yield 'lossless arithmetic' => [0xcb, 3, ColorSpace::RGB];
        yield 'differential lossless arithmetic' => [0xcf, 3, ColorSpace::RGB];
    }

    public function testTablesAreNotMistakenForFrames(): void
    {
        $handler = new JpegHandler("\xff\xd8" . GdImagesFixture::jpegSegment(0xc4, str_repeat("\x00", 20)) . "\xff\xd9");

        $this->assertSame(0, $handler->getInfo()->width());
    }

    public function testTruncatedFramesAreRejected(): void
    {
        $handler = new JpegHandler("\xff\xd8" . GdImagesFixture::jpegSegment(0xc0, "\x08\x00") . "\xff\xd9");

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid start of frame segment');

        $handler->getInfo();
    }

    public function testColorProfilesAreSetAndRead(): void
    {
        $handler = new JpegHandler(GdImagesFixture::jpeg());

        $this->assertFalse($handler->hasColorProfile());
        $this->assertNull($handler->getColorProfile());

        $handler->setColorProfile($this->colorProfile());

        $this->assertTrue($handler->hasColorProfile());
        $this->assertSame($this->colorProfile()->getData(), $handler->getColorProfile()?->getData());
        $this->assertDecodable($handler);
    }

    public function testOnlyTheFirstFrameDescribesTheImage(): void
    {
        $first = "\x08" . pack('nn', 20, 10) . "\x01\x01\x11\x00";
        $second = "\x08" . pack('nn', 40, 30) . "\x03" . str_repeat("\x01\x11\x00", 3);
        $handler = new JpegHandler("\xff\xd8" . GdImagesFixture::jpegSegment(0xc0, $first) . GdImagesFixture::jpegSegment(0xc0, $second) . "\xff\xd9");

        $info = $handler->getInfo();

        $this->assertSame([10, 20, ColorSpace::Grayscale], [$info->width(), $info->height(), $info->colorSpace()]);
    }

    public function testOtherApplicationSegmentsAreNotColorProfiles(): void
    {
        $handler = new JpegHandler("\xff\xd8" . GdImagesFixture::jpegSegment(0xe2, "FPXR\x00\x00\x01\x02") . "\xff\xd9");

        $this->assertFalse($handler->hasColorProfile());
    }

    public function testLargeColorProfilesAreSplitAndJoined(): void
    {
        $profile = $this->largeColorProfile();
        $handler = new JpegHandler(GdImagesFixture::jpeg());

        $handler->setColorProfile($profile);

        $this->assertGreaterThan(2, substr_count($handler->getData(), "ICC_PROFILE\x00"));
        $this->assertSame($profile->getData(), $handler->getColorProfile()?->getData());
        $this->assertDecodable($handler);
    }

    public function testAllTheChunksOfAColorProfileAreRemoved(): void
    {
        $profile = $this->largeColorProfile();
        $handler = new JpegHandler(GdImagesFixture::jpeg());
        $handler->setColorProfile($profile);

        $handler->removeColorProfile();

        $this->assertFalse($handler->hasColorProfile());
        $this->assertSame(GdImagesFixture::jpeg(), $handler->getData());
    }

    public function testColorProfilesAreReplacedInsteadOfDuplicated(): void
    {
        $handler = new JpegHandler(GdImagesFixture::jpeg());

        $handler->setColorProfile($this->colorProfile());
        $handler->setColorProfile($this->colorProfile());

        $this->assertSame(1, substr_count($handler->getData(), "ICC_PROFILE\x00"));
    }

    public function testIncompleteColorProfilesAreRejected(): void
    {
        $segment = GdImagesFixture::jpegSegment(0xe2, "ICC_PROFILE\x00\x01\x02" . $this->colorProfile()->getData());
        $handler = new JpegHandler("\xff\xd8" . $segment . "\xff\xd9");

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Unexpected profile chunk count');

        $handler->getColorProfile();
    }

    public function testTruncatedColorProfileSegmentsAreRejected(): void
    {
        $handler = new JpegHandler("\xff\xd8" . GdImagesFixture::jpegSegment(0xe2, "ICC_PROFILE\x00\x01") . "\xff\xd9");

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Unexpected end of data');

        $handler->getColorProfile();
    }

    public function testExifDataIsSetAndRead(): void
    {
        $handler = new JpegHandler(GdImagesFixture::jpeg());

        $this->assertFalse($handler->hasExifData());
        $this->assertNull($handler->getExifData());

        $handler->setExifData($this->exifData());

        $this->assertTrue($handler->hasExifData());
        $this->assertSame($this->exifData()->getData(), $handler->getExifData()?->getData());
        $this->assertDecodable($handler);
    }

    public function testExifDataIsReplacedInsteadOfDuplicated(): void
    {
        $handler = new JpegHandler(GdImagesFixture::jpeg());

        $handler->setExifData($this->exifData());
        $handler->setExifData($this->exifData());

        $this->assertSame(1, substr_count($handler->getData(), "Exif\x00\x00"));
    }

    public function testExifDataIsRemoved(): void
    {
        $handler = new JpegHandler(GdImagesFixture::jpeg());
        $handler->setExifData($this->exifData());

        $handler->removeExifData();

        $this->assertFalse($handler->hasExifData());
        $this->assertSame(GdImagesFixture::jpeg(), $handler->getData());
    }

    public function testMetadataFollowsTheJfifSegment(): void
    {
        $handler = new JpegHandler(GdImagesFixture::jpeg());
        $handler->setExifData($this->exifData());
        $handler->setColorProfile($this->colorProfile());

        $this->assertSame("\xff\xd8\xff\xe0", substr($handler->getData(), 0, 4));
        $this->assertSame('JFIF', substr($handler->getData(), 6, 4));
    }

    public function testMetadataFollowsTheStartOfImageMarkerWithoutJfifSegment(): void
    {
        $handler = new JpegHandler("\xff\xd8" . GdImagesFixture::jpegSegment(0xc0, "\x08\x00\x0a\x00\x14\x03") . "\xff\xd9");
        $handler->setExifData($this->exifData());

        $this->assertSame("\xff\xd8\xff\xe1", substr($handler->getData(), 0, 4));
        $this->assertTrue($handler->hasExifData());
        $this->assertSame(20, $handler->getInfo()->width());
    }

    public function testRemovingMetadataPreservesOtherSegments(): void
    {
        $jpeg = "\xff\xd8" . GdImagesFixture::jpegSegment(0xe1, "Exif\x00\x00" . $this->exifData()->getData()) . GdImagesFixture::jpegSegment(0xe1, 'http://ns.adobe.com/xap/1.0/') . GdImagesFixture::jpegSegment(0xe2, 'other') . "\xff\xd9";
        $handler = new JpegHandler($jpeg);

        $handler->removeExifData();

        $this->assertSame("\xff\xd8" . GdImagesFixture::jpegSegment(0xe1, 'http://ns.adobe.com/xap/1.0/') . GdImagesFixture::jpegSegment(0xe2, 'other') . "\xff\xd9", $handler->getData());
    }

    public function testMissingStartOfImageMarkersAreRejected(): void
    {
        $handler = new JpegHandler('not a JPEG image');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JPEG data');

        $handler->setExifData($this->exifData());
    }

    public function testExifDataNotFittingInASegmentIsRejected(): void
    {
        $handler = new JpegHandler(GdImagesFixture::jpeg());

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Data is too large to be embedded in a JPEG segment');

        $handler->setExifData(new ExifData($this->exifData()->getData() . str_repeat("\x00", 70_000)));
    }

    public function testImagesAreCreatedFromGdImages(): void
    {
        $handler = JpegHandler::fromGdImage(GdImagesFixture::image(16, 12));

        $this->assertSame(16, $handler->getInfo()->width());
        $this->assertSame(12, $handler->getInfo()->height());
    }

    public function testTransformsAndMetadataAreSupported(): void
    {
        $this->assertTrue((new JpegHandler(GdImagesFixture::jpeg()))->supportsTransforms());
        $this->assertTrue(JpegHandler::supportsColorProfile());
        $this->assertTrue(JpegHandler::supportsExifData());
    }

    public function testApplicationSegmentsWithOtherDataAreNotMetadata(): void
    {
        $jpeg = "\xff\xd8" . GdImagesFixture::jpegSegment(0xe1, 'http://ns.adobe.com/xap/1.0/') . GdImagesFixture::jpegSegment(0xe2, 'other') . "\xff\xd9";
        $handler = new JpegHandler($jpeg);

        $this->assertFalse($handler->hasColorProfile());
        $this->assertFalse($handler->hasExifData());
        $this->assertNull($handler->getExifData());
    }

    public function testColorProfilesNotFittingInTheSegmentsAreRejected(): void
    {
        $handler = new JpegHandler(GdImagesFixture::jpeg());

        // A profile can be split into at most 255 segments of 65517 bytes
        $data = str_pad(str_repeat("\x00", 36) . 'acsp', 255 * 65517 + 1, "\x00");

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Color profile is too large to be embedded in a JPEG image');

        $handler->setColorProfile(new ColorProfile($data));
    }

    /**
     * A color profile larger than a JPEG segment, whose bytes change along it so that misplaced chunks are noticed
     */
    private function largeColorProfile(): ColorProfile
    {
        $bytes = implode('', array_map(chr(...), range(0, 255)));

        return new ColorProfile($this->colorProfile()->getData() . substr(str_repeat($bytes, 600), 0, 150_000));
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
