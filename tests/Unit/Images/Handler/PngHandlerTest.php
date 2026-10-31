<?php

namespace Formwork\Tests\Unit\Images\Handler;

use Formwork\Images\ColorProfile\ColorProfile;
use Formwork\Images\ColorProfile\ColorSpace;
use Formwork\Images\Exif\ExifData;
use Formwork\Images\Handler\JpegHandler;
use Formwork\Images\Handler\PngHandler;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Images\Fixtures\GdImages;
use PHPUnit\Framework\Attributes\CoversClass;
use UnexpectedValueException;

#[CoversClass(PngHandler::class)]
final class PngHandlerTest extends TestCase
{
    private const string FIXTURES = __DIR__ . '/../Fixtures';

    private const string HEADER = "\x89PNG\x0d\x0a\x1a\x0a";

    public function testInfoIsReadFromAnEncodedImage(): void
    {
        $info = (new PngHandler(GdImages::png(40, 30)))->getInfo();

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

    public function testAnimationsAreRecognized(): void
    {
        $data = $this->withChunk(GdImages::png(), 'acTL', pack('NN', 3, 2));

        $handler = new PngHandler($data);

        $this->assertTrue($handler->getInfo()->isAnimation());
        $this->assertSame(3, $handler->getInfo()->animationFrames());
        $this->assertSame(2, $handler->getInfo()->animationRepeatCount());
        $this->assertFalse($handler->supportsTransforms());
    }

    public function testTruncatedHeaderChunksAreNotAccepted(): void
    {
        $handler = new PngHandler(self::HEADER . GdImages::pngChunk('IHDR', "\x00\x00\x00\x01") . GdImages::pngChunk('IEND', ''));

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid IHDR chunk');

        $handler->getInfo();
    }

    public function testInvalidPalettesAreNotAccepted(): void
    {
        $handler = new PngHandler($this->withChunk(GdImages::png(), 'PLTE', "\x00\x00\x00\x00"));

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid palette size');

        $handler->getInfo();
    }

    public function testColorProfilesAreSetAndRead(): void
    {
        $handler = new PngHandler(GdImages::png());

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
        $handler = new PngHandler(GdImages::png());

        $handler->setColorProfile($this->colorProfile());
        $handler->setColorProfile($this->colorProfile());

        $this->assertSame(1, $this->countChunks($handler->getData(), 'iCCP'));
    }

    public function testColorProfilesAreRemoved(): void
    {
        $handler = new PngHandler(GdImages::png());
        $handler->setColorProfile($this->colorProfile());

        $handler->removeColorProfile();

        $this->assertFalse($handler->hasColorProfile());
        $this->assertSame(GdImages::png(), $handler->getData());
    }

    public function testRemovingAMissingColorProfileLeavesTheDataUntouched(): void
    {
        $handler = new PngHandler(GdImages::png());

        $handler->removeColorProfile();

        $this->assertSame(GdImages::png(), $handler->getData());
    }

    public function testInvalidColorProfilesAreNotAccepted(): void
    {
        $handler = new PngHandler($this->withChunk(GdImages::png(), 'iCCP', "name\x00\x00not compressed"));

        $this->assertTrue($handler->hasColorProfile());

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid color profile string');

        $handler->getColorProfile();
    }

    public function testUnterminatedColorProfileNamesAreNotAccepted(): void
    {
        $handler = new PngHandler($this->withChunk(GdImages::png(), 'iCCP', 'name'));

        $this->expectException(UnexpectedValueException::class);

        $handler->getColorProfile();
    }

    public function testExifDataIsSetAndRead(): void
    {
        $handler = new PngHandler(GdImages::png());

        $this->assertFalse($handler->hasExifData());
        $this->assertNull($handler->getExifData());

        $handler->setExifData($this->exifData());

        $this->assertTrue($handler->hasExifData());
        $this->assertSame($this->exifData()->getData(), $handler->getExifData()?->getData());
        $this->assertDecodable($handler);
    }

    public function testExifDataIsReplacedInsteadOfDuplicated(): void
    {
        $handler = new PngHandler(GdImages::png());

        $handler->setExifData($this->exifData());
        $handler->setExifData($this->exifData());

        $this->assertSame(1, $this->countChunks($handler->getData(), 'eXIf'));
    }

    public function testExifDataIsRemoved(): void
    {
        $handler = new PngHandler(GdImages::png());
        $handler->setExifData($this->exifData());

        $handler->removeExifData();

        $this->assertFalse($handler->hasExifData());
        $this->assertSame(GdImages::png(), $handler->getData());
    }

    public function testMetadataIsPreservedWhenConvertingToAnotherFormat(): void
    {
        $handler = new PngHandler(GdImages::png());
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
        $handler = PngHandler::fromGdImage(GdImages::image(16, 12));

        $this->assertSame(16, $handler->getInfo()->width());
        $this->assertSame(12, $handler->getInfo()->height());
        $this->assertTrue($handler->getInfo()->hasAlphaChannel());
    }

    public function testDataFromAnotherFormatIsNotAccepted(): void
    {
        $handler = new PngHandler(GdImages::jpeg());

        $this->expectException(\InvalidArgumentException::class);

        $handler->getInfo();
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

        return substr($png, 0, $offset) . GdImages::pngChunk($type, $data) . substr($png, $offset);
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
