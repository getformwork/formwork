<?php

namespace Formwork\Tests\Unit\Images\Handler;

use Formwork\Images\ColorProfile\ColorProfile;
use Formwork\Images\ColorProfile\ColorSpace;
use Formwork\Images\Exif\ExifData;
use Formwork\Images\Handler\Exceptions\UnsupportedFeatureException;
use Formwork\Images\Handler\SvgHandler;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Images\Fixtures\GdImages;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(SvgHandler::class)]
final class SvgHandlerTest extends TestCase
{
    private const string FIXTURES = __DIR__ . '/../Fixtures';

    public function testInfoIsReadFromTheDimensions(): void
    {
        $info = $this->handler('<svg xmlns="http://www.w3.org/2000/svg" width="120" height="80"/>')->getInfo();

        $this->assertSame('image/svg+xml', $info->mimeType());
        $this->assertSame(120, $info->width());
        $this->assertSame(80, $info->height());
        $this->assertSame(ColorSpace::RGB, $info->colorSpace());
        $this->assertTrue($info->hasAlphaChannel());
        $this->assertFalse($info->isAnimation());
    }

    public function testInfoIsReadFromTheViewBoxWhenTheSizeIsRelative(): void
    {
        $info = $this->handler('<svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewBox="0 0 48 24"/>')->getInfo();

        $this->assertSame(48, $info->width());
        $this->assertSame(24, $info->height());
    }

    public function testImagesWithoutDimensionsHaveNoSize(): void
    {
        $info = $this->handler('<svg xmlns="http://www.w3.org/2000/svg"/>')->getInfo();

        $this->assertSame(0, $info->width());
        $this->assertSame(0, $info->height());
    }

    public function testTransformsAndMetadataAreNotSupported(): void
    {
        $handler = $this->handler('<svg xmlns="http://www.w3.org/2000/svg"/>');

        $this->assertFalse($handler->supportsTransforms());
        $this->assertFalse(SvgHandler::supportsColorProfile());
        $this->assertFalse(SvgHandler::supportsExifData());
        $this->assertFalse($handler->hasColorProfile());
        $this->assertFalse($handler->hasExifData());
    }

    /**
     * @param callable(SvgHandler): mixed $operation
     */
    #[DataProvider('unsupportedOperationProvider')]
    public function testUnsupportedOperationsAreRejected(callable $operation): void
    {
        $this->expectException(UnsupportedFeatureException::class);

        $operation($this->handler('<svg xmlns="http://www.w3.org/2000/svg"/>'));
    }

    /**
     * @return iterable<string, array{callable(SvgHandler): mixed}>
     */
    public static function unsupportedOperationProvider(): iterable
    {
        $profile = new ColorProfile((string) file_get_contents(self::FIXTURES . '/srgb.icc'));
        $exif = new ExifData((string) file_get_contents(self::FIXTURES . '/exif.tiff'));

        yield 'get color profile' => [static fn(SvgHandler $h): ?ColorProfile => $h->getColorProfile()];
        yield 'set color profile' => [static fn(SvgHandler $h) => $h->setColorProfile($profile)];
        yield 'remove color profile' => [static fn(SvgHandler $h) => $h->removeColorProfile()];
        yield 'get exif data' => [static fn(SvgHandler $h): ?ExifData => $h->getExifData()];
        yield 'set exif data' => [static fn(SvgHandler $h) => $h->setExifData($exif)];
        yield 'remove exif data' => [static fn(SvgHandler $h) => $h->removeExifData()];
        yield 'from gd image' => [static fn(SvgHandler $h) => SvgHandler::fromGdImage(GdImages::image())];
    }

    private function handler(string $data): SvgHandler
    {
        return new SvgHandler($data);
    }
}
