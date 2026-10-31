<?php

namespace Formwork\Tests\Unit\Images\Handler;

use Formwork\Images\Binary\Bytes;
use Formwork\Images\Binary\Endianness;
use Formwork\Images\ColorProfile\ColorProfile;
use Formwork\Images\ColorProfile\ColorSpace;
use Formwork\Images\Exif\ExifData;
use Formwork\Images\Handler\WebpHandler;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Images\Fixtures\GdImages;
use PHPUnit\Framework\Attributes\CoversClass;
use UnexpectedValueException;

#[CoversClass(WebpHandler::class)]
final class WebpHandlerTest extends TestCase
{
    private const string FIXTURES = __DIR__ . '/../Fixtures';

    public function testInfoIsReadFromAnEncodedImage(): void
    {
        $info = (new WebpHandler(GdImages::webp(40, 30)))->getInfo();

        $this->assertSame('image/webp', $info->mimeType());
        $this->assertSame(40, $info->width());
        $this->assertSame(30, $info->height());
        $this->assertSame(ColorSpace::RGB, $info->colorSpace());
        $this->assertSame(8, $info->colorDepth());
        $this->assertFalse($info->isAnimation());
    }

    public function testInfoIsReadFromLossyImages(): void
    {
        $frame = "\x10\x00\x00\x9d\x01\x2a" . pack('vv', 0xc000 | 123, 0x4000 | 45);
        $handler = new WebpHandler(GdImages::webpFile(GdImages::webpChunk('VP8 ', $frame)));

        $this->assertSame(123, $handler->getInfo()->width());
        $this->assertSame(45, $handler->getInfo()->height());
        $this->assertFalse($handler->getInfo()->hasAlphaChannel());
    }

    public function testInfoIsReadFromLosslessImages(): void
    {
        $bits = (320 - 1) | ((200 - 1) << 14) | (1 << 28);
        $handler = new WebpHandler(GdImages::webpFile(GdImages::webpChunk('VP8L', "\x2f" . pack('V', $bits))));

        $this->assertSame(320, $handler->getInfo()->width());
        $this->assertSame(200, $handler->getInfo()->height());
        $this->assertTrue($handler->getInfo()->hasAlphaChannel());
    }

    public function testInfoIsReadFromExtendedImages(): void
    {
        $handler = new WebpHandler(GdImages::webpFile(GdImages::webpChunk('VP8X', "\x10\x00\x00\x00" . substr(pack('V', 999), 0, 3) . substr(pack('V', 499), 0, 3))));

        $this->assertSame(1000, $handler->getInfo()->width());
        $this->assertSame(500, $handler->getInfo()->height());
        $this->assertTrue($handler->getInfo()->hasAlphaChannel());
    }

    public function testAlphaChannelsAreRecognizedFromTheirChunk(): void
    {
        $frame = "\x10\x00\x00\x9d\x01\x2a" . pack('vv', 8, 8);
        $handler = new WebpHandler(GdImages::webpFile(GdImages::webpChunk('ALPH', "\x00"), GdImages::webpChunk('VP8 ', $frame)));

        $this->assertTrue($handler->getInfo()->hasAlphaChannel());
    }

    public function testAnimationsAreRecognized(): void
    {
        $handler = new WebpHandler(GdImages::webpFile(
            GdImages::webpChunk('VP8X', "\x02\x00\x00\x00\x09\x00\x00\x09\x00\x00"),
            GdImages::webpChunk('ANIM', "\x00\x00\x00\x00" . pack('v', 3)),
            GdImages::webpChunk('ANMF', str_repeat("\x00", 16)),
            GdImages::webpChunk('ANMF', str_repeat("\x00", 16)),
        ));

        $info = $handler->getInfo();

        $this->assertTrue($info->isAnimation());
        $this->assertSame(2, $info->animationFrames());
        $this->assertSame(3, $info->animationRepeatCount());
        $this->assertFalse($handler->supportsTransforms());
    }

    public function testTruncatedChunksAreNotAccepted(): void
    {
        $handler = new WebpHandler(GdImages::webpFile(GdImages::webpChunk('VP8L', "\x2f\x00")));

        $this->expectException(UnexpectedValueException::class);

        $handler->getInfo();
    }

    public function testInvalidExtendedHeadersAreNotAccepted(): void
    {
        $handler = new WebpHandler(GdImages::webpFile(GdImages::webpChunk('VP8X', "\x00\x00")));

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid VP8X chunk');

        $handler->getInfo();
    }

    public function testColorProfilesAreSetAndRead(): void
    {
        $handler = new WebpHandler(GdImages::webp());

        $this->assertFalse($handler->hasColorProfile());
        $this->assertNull($handler->getColorProfile());

        $handler->setColorProfile($this->colorProfile());

        $this->assertTrue($handler->hasColorProfile());
        $this->assertSame($this->colorProfile()->getData(), $handler->getColorProfile()?->getData());
        $this->assertSame(40, $handler->getInfo()->width());
        $this->assertWellFormed($handler);
    }

    public function testColorProfilesOfOddSizeAreNotPadded(): void
    {
        $profile = new ColorProfile($this->colorProfile()->getData() . 'x');
        $handler = new WebpHandler(GdImages::webp());

        $handler->setColorProfile($profile);

        $this->assertSame($profile->getData(), $handler->getColorProfile()?->getData());
        $this->assertWellFormed($handler);
    }

    public function testColorProfilesAreReplacedInsteadOfDuplicated(): void
    {
        $handler = new WebpHandler(GdImages::webp());

        $handler->setColorProfile($this->colorProfile());
        $handler->setColorProfile($this->colorProfile());

        $this->assertSame(1, $this->countChunks($handler, 'ICCP'));
    }

    public function testColorProfilesFollowTheExtendedHeader(): void
    {
        $handler = new WebpHandler(GdImages::webp());
        $handler->setExifData($this->exifData());

        $handler->setColorProfile($this->colorProfile());

        $this->assertSame(['VP8X', 'ICCP'], array_slice($this->chunkTypes($handler), 0, 2));
    }

    public function testColorProfilesAreRemoved(): void
    {
        $handler = new WebpHandler(GdImages::webp());
        $handler->setColorProfile($this->colorProfile());

        $handler->removeColorProfile();

        $this->assertFalse($handler->hasColorProfile());
        $this->assertSame(0, $this->flags($handler) & 0b00100000);
        $this->assertWellFormed($handler);
    }

    public function testExifDataIsSetAndRead(): void
    {
        $handler = new WebpHandler(GdImages::webp());

        $this->assertFalse($handler->hasExifData());
        $this->assertNull($handler->getExifData());

        $handler->setExifData($this->exifData());

        $this->assertTrue($handler->hasExifData());
        $this->assertSame($this->exifData()->getData(), $handler->getExifData()?->getData());
        $this->assertSame(0b00001000, $this->flags($handler) & 0b00001000);
        $this->assertWellFormed($handler);
    }

    public function testExifDataIsReplacedInsteadOfDuplicated(): void
    {
        $handler = new WebpHandler(GdImages::webp());

        $handler->setExifData($this->exifData());
        $handler->setExifData($this->exifData());

        $this->assertSame(1, $this->countChunks($handler, 'EXIF'));
    }

    public function testExifDataPrecedesXmpMetadata(): void
    {
        $frame = "\x10\x00\x00\x9d\x01\x2a" . pack('vv', 8, 8);
        $handler = new WebpHandler(GdImages::webpFile(GdImages::webpChunk('VP8 ', $frame), GdImages::webpChunk('XMP ', '<xmp/>')));

        $handler->setExifData($this->exifData());

        $this->assertSame(['VP8X', 'VP8 ', 'EXIF', 'XMP '], $this->chunkTypes($handler));
    }

    public function testExifDataIsRemoved(): void
    {
        $handler = new WebpHandler(GdImages::webp());
        $handler->setExifData($this->exifData());
        $handler->setColorProfile($this->colorProfile());

        $handler->removeExifData();

        $this->assertFalse($handler->hasExifData());
        $this->assertTrue($handler->hasColorProfile());
        $this->assertSame(0, $this->flags($handler) & 0b00001000);
        $this->assertSame(0b00100000, $this->flags($handler) & 0b00100000);
        $this->assertWellFormed($handler);
    }

    public function testRemovingMissingMetadataLeavesTheImageDecodable(): void
    {
        $handler = new WebpHandler(GdImages::webp());

        $handler->removeColorProfile();
        $handler->removeExifData();

        $this->assertWellFormed($handler);
        $this->assertSame(40, $handler->getInfo()->width());
    }

    public function testEncodedImagesCanHoldMetadata(): void
    {
        $handler = WebpHandler::fromGdImage(GdImages::image(16, 12));

        $this->assertSame('VP8X', $this->chunkTypes($handler)[0]);
        $this->assertSame(16, $handler->getInfo()->width());
        $this->assertSame(12, $handler->getInfo()->height());
        $this->assertTrue($handler->getInfo()->hasAlphaChannel());

        $handler->setExifData($this->exifData());

        $this->assertWellFormed($handler);
    }

    private function colorProfile(): ColorProfile
    {
        return new ColorProfile((string) file_get_contents(self::FIXTURES . '/srgb.icc'));
    }

    private function exifData(): ExifData
    {
        return new ExifData((string) file_get_contents(self::FIXTURES . '/exif.tiff'));
    }

    /**
     * @return list<string>
     */
    private function chunkTypes(WebpHandler $handler): array
    {
        $types = [];
        $data = $handler->getData();
        $position = 12;

        while ($position < strlen($data)) {
            $types[] = substr($data, $position, 4);
            $size = Bytes::unpack($data, $position + 4, 4, Endianness::Little);
            $position += 8 + $size + $size % 2;
        }

        return $types;
    }

    private function countChunks(WebpHandler $handler, string $type): int
    {
        return count(array_keys($this->chunkTypes($handler), $type, true));
    }

    private function flags(WebpHandler $handler): int
    {
        return ord($handler->getData()[20]);
    }

    private function assertWellFormed(WebpHandler $handler): void
    {
        $data = $handler->getData();

        $this->assertSame('RIFF', substr($data, 0, 4));
        $this->assertSame(strlen($data) - 8, Bytes::unpack($data, 4, 4, Endianness::Little));
        $this->assertSame(0, strlen($data) % 2);
        $this->assertNotFalse(@imagecreatefromstring($data));
    }
}
