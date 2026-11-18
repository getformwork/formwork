<?php

namespace Formwork\Tests\Unit\Images\Handler;

use Formwork\Images\Binary\Bytes;
use Formwork\Images\ColorProfile\ColorProfile;
use Formwork\Images\ColorProfile\ColorSpace;
use Formwork\Images\Exif\ExifData;
use Formwork\Images\Handler\JpegHandler;
use Formwork\Images\Handler\PngHandler;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Images\Fixtures\GdImagesFixture;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use UnexpectedValueException;

#[CoversClass(PngHandler::class)]
final class PngHandlerTest extends TestCase
{
    private const string FIXTURES = __DIR__ . '/../Fixtures';

    private const string HEADER = "\x89PNG\x0d\x0a\x1a\x0a";

    public function testInfoIsReadFromAnEncodedImage(): void
    {
        $info = (new PngHandler(GdImagesFixture::png()))->getInfo();

        $this->assertSame('image/png', $info->mimeType());
        $this->assertSame(40, $info->width());
        $this->assertSame(30, $info->height());
        $this->assertSame(ColorSpace::RGB, $info->colorSpace());
        $this->assertSame(8, $info->colorDepth());
        $this->assertTrue($info->hasAlphaChannel());
        $this->assertFalse($info->isAnimation());
        $this->assertNull($info->colorNumber());
    }

    public function testPaletteImagesHaveAColorNumber(): void
    {
        $image = imagecreate(8, 8);
        imagecolorallocate($image, 0, 0, 0);
        imagecolorallocate($image, 255, 0, 0);
        imagecolorallocate($image, 0, 255, 0);
        ob_start();
        imagepng($image);
        $data = (string) ob_get_clean();

        $info = (new PngHandler($data))->getInfo();

        $this->assertSame(ColorSpace::Palette, $info->colorSpace());
        $this->assertSame(3, $info->colorNumber());
    }

    #[DataProvider('colorTypeProvider')]
    public function testColorTypesDetermineTheColorSpaceAndTheAlphaChannel(int $colorType, ColorSpace $colorSpace, bool $hasAlphaChannel): void
    {
        $header = pack('NN', 8, 8) . chr(8) . chr($colorType) . "\x00\x00\x00";
        $info = (new PngHandler(self::HEADER . GdImagesFixture::pngChunk('IHDR', $header) . GdImagesFixture::pngChunk('IEND', '')))->getInfo();

        $this->assertSame($colorSpace, $info->colorSpace());
        $this->assertSame($hasAlphaChannel, $info->hasAlphaChannel());
    }

    /**
     * @return iterable<string, array{int, ColorSpace, bool}>
     */
    public static function colorTypeProvider(): iterable
    {
        yield 'grayscale' => [0, ColorSpace::Grayscale, false];
        yield 'RGB' => [2, ColorSpace::RGB, false];
        yield 'palette' => [3, ColorSpace::Palette, false];
        yield 'grayscale with alpha' => [4, ColorSpace::Grayscale, true];
        yield 'RGB with alpha' => [6, ColorSpace::RGB, true];
    }

    public function testTransformsAndMetadataAreSupported(): void
    {
        $this->assertTrue((new PngHandler(GdImagesFixture::png()))->supportsTransforms());
        $this->assertTrue(PngHandler::supportsColorProfile());
        $this->assertTrue(PngHandler::supportsExifData());
    }

    public function testAnimationsAreRecognized(): void
    {
        $data = $this->withChunk(GdImagesFixture::png(), 'acTL', pack('NN', 3, 2));

        $handler = new PngHandler($data);

        $this->assertTrue($handler->getInfo()->isAnimation());
        $this->assertSame(3, $handler->getInfo()->animationFrames());
        $this->assertSame(2, $handler->getInfo()->animationRepeatCount());
        $this->assertFalse($handler->supportsTransforms());
    }

    public function testTruncatedHeaderChunksAreRejected(): void
    {
        $handler = new PngHandler(self::HEADER . GdImagesFixture::pngChunk('IHDR', "\x00\x00\x00\x01") . GdImagesFixture::pngChunk('IEND', ''));

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid IHDR chunk');

        $handler->getInfo();
    }

    public function testInvalidPalettesAreRejected(): void
    {
        $handler = new PngHandler($this->withChunk(GdImagesFixture::png(), 'PLTE', "\x00\x00\x00\x00"));

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid palette size');

        $handler->getInfo();
    }

    public function testColorProfilesAreSetAndRead(): void
    {
        $handler = new PngHandler(GdImagesFixture::png());

        $this->assertFalse($handler->hasColorProfile());
        $this->assertNull($handler->getColorProfile());

        $handler->setColorProfile($this->colorProfile());

        $this->assertTrue($handler->hasColorProfile());
        $this->assertSame($this->colorProfile()->getData(), $handler->getColorProfile()?->getData());
        $this->assertSame('sRGB built-in', $handler->getColorProfile()->name());
        $this->assertDecodable($handler);
    }

    public function testColorProfilesAreReplacedInsteadOfDuplicated(): void
    {
        $handler = new PngHandler(GdImagesFixture::png());

        $handler->setColorProfile($this->colorProfile());
        $handler->setColorProfile($this->colorProfile());

        $this->assertSame(1, $this->countChunks($handler->getData(), 'iCCP'));
    }

    public function testColorProfilesAreRemoved(): void
    {
        $handler = new PngHandler(GdImagesFixture::png());
        $handler->setColorProfile($this->colorProfile());

        $handler->removeColorProfile();

        $this->assertFalse($handler->hasColorProfile());
        $this->assertSame(GdImagesFixture::png(), $handler->getData());
    }

    public function testRemovingAMissingColorProfileLeavesTheDataUntouched(): void
    {
        $handler = new PngHandler(GdImagesFixture::png());

        $handler->removeColorProfile();

        $this->assertSame(GdImagesFixture::png(), $handler->getData());
    }

    public function testInvalidColorProfilesAreRejected(): void
    {
        $handler = new PngHandler($this->withChunk(GdImagesFixture::png(), 'iCCP', "name\x00\x00not compressed"));

        $this->assertTrue($handler->hasColorProfile());

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid color profile string');

        $handler->getColorProfile();
    }

    public function testUnterminatedColorProfileNamesAreRejected(): void
    {
        $handler = new PngHandler($this->withChunk(GdImagesFixture::png(), 'iCCP', 'name'));

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Unexpected end of data');

        $handler->getColorProfile();
    }

    public function testExifDataIsSetAndRead(): void
    {
        $handler = new PngHandler(GdImagesFixture::png());

        $this->assertFalse($handler->hasExifData());
        $this->assertNull($handler->getExifData());

        $handler->setExifData($this->exifData());

        $this->assertTrue($handler->hasExifData());
        $this->assertSame($this->exifData()->getData(), $handler->getExifData()?->getData());
        $this->assertDecodable($handler);
    }

    public function testExifDataIsReplacedInsteadOfDuplicated(): void
    {
        $handler = new PngHandler(GdImagesFixture::png());

        $handler->setExifData($this->exifData());
        $handler->setExifData($this->exifData());

        $this->assertSame(1, $this->countChunks($handler->getData(), 'eXIf'));
    }

    public function testExifDataIsRemoved(): void
    {
        $handler = new PngHandler(GdImagesFixture::png());
        $handler->setExifData($this->exifData());

        $handler->removeExifData();

        $this->assertFalse($handler->hasExifData());
        $this->assertSame(GdImagesFixture::png(), $handler->getData());
    }

    public function testMetadataIsPreservedWhenConvertingToAnotherFormat(): void
    {
        $handler = new PngHandler(GdImagesFixture::png());
        $handler->setColorProfile($this->colorProfile());
        $handler->setExifData($this->exifData());

        $converted = $handler->process(null, JpegHandler::class);

        $this->assertInstanceOf(JpegHandler::class, $converted);
        $this->assertTrue($converted->hasColorProfile());
        $this->assertTrue($converted->hasExifData());
        $this->assertSame(40, $converted->getInfo()->width());
    }

    public function testImagesAreCreatedFromGdImages(): void
    {
        $handler = PngHandler::fromGdImage(GdImagesFixture::image(16, 12));

        $this->assertSame(16, $handler->getInfo()->width());
        $this->assertSame(12, $handler->getInfo()->height());
        $this->assertTrue($handler->getInfo()->hasAlphaChannel());
    }

    public function testDataFromAnotherFormatIsRejected(): void
    {
        $handler = new PngHandler(GdImagesFixture::jpeg());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid PNG data');

        $handler->getInfo();
    }

    public function testWrittenChunksHaveValidChecksumsAndTheProfileName(): void
    {
        $handler = new PngHandler(GdImagesFixture::png());
        $handler->setColorProfile($this->colorProfile());
        $handler->setExifData($this->exifData());

        $data = $handler->getData();
        $position = strlen(self::HEADER);
        $chunks = [];

        while ($position < strlen($data)) {
            $length = Bytes::unpack($data, $position, 4);
            $type = substr($data, $position + 4, 4);
            $value = substr($data, $position + 8, $length);
            $this->assertSame(crc32($type . $value), Bytes::unpack($data, $position + 8 + $length, 4), "Invalid checksum of the {$type} chunk");
            $chunks[$type] = $value;
            $position += 12 + $length;
        }

        $this->assertStringStartsWith("sRGB built-in\x00\x00", $chunks['iCCP']);
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
     * Insert a chunk right after the header chunk
     */
    private function withChunk(string $png, string $type, string $data): string
    {
        $offset = strlen(self::HEADER) + 25;

        return substr($png, 0, $offset) . GdImagesFixture::pngChunk($type, $data) . substr($png, $offset);
    }

    private function countChunks(string $png, string $type): int
    {
        return substr_count($png, $type);
    }

    private function assertDecodable(PngHandler $handler): void
    {
        $this->assertNotFalse(@imagecreatefromstring($handler->getData()));
    }
}
