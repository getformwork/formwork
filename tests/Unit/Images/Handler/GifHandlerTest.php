<?php

namespace Formwork\Tests\Unit\Images\Handler;

use Formwork\Images\ColorProfile\ColorProfile;
use Formwork\Images\ColorProfile\ColorSpace;
use Formwork\Images\Exif\ExifData;
use Formwork\Images\Handler\Exceptions\UnsupportedFeatureException;
use Formwork\Images\Handler\GifHandler;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Images\Fixtures\GdImages;
use GdImage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use UnexpectedValueException;

#[CoversClass(GifHandler::class)]
final class GifHandlerTest extends TestCase
{
    private const string FIXTURES = __DIR__ . '/../Fixtures';

    private const string FRAME = ",\x00\x00\x00\x00\x03\x00\x02\x00\x00\x02\x02\x44\x01\x00";

    public function testInfoIsReadFromAnEncodedImage(): void
    {
        $info = (new GifHandler(GdImages::gif(40, 30)))->getInfo();

        $this->assertSame('image/gif', $info->mimeType());
        $this->assertSame(40, $info->width());
        $this->assertSame(30, $info->height());
        $this->assertSame(ColorSpace::Palette, $info->colorSpace());
        $this->assertFalse($info->isAnimation());
        $this->assertNull($info->animationFrames());
    }

    public function testEncodedGdImagesWithTransparencyHaveAnAlphaChannel(): void
    {
        $this->assertTrue(GifHandler::fromGdImage(GdImages::image())->getInfo()->hasAlphaChannel());
    }

    public function testTheNumberOfColorsIsTheSizeOfTheGlobalColorTable(): void
    {
        $this->assertSame(4, $this->handler("\x01\x00\x01\x00\xf1\x00\x00" . str_repeat("\x00", 12) . self::FRAME)->getInfo()->colorNumber());
        $this->assertSame(256, $this->handler("\x01\x00\x01\x00\xf7\x00\x00" . str_repeat("\x00", 768) . self::FRAME)->getInfo()->colorNumber());
    }

    public function testImagesWithoutGlobalColorTableHaveNoColorNumber(): void
    {
        $this->assertNull($this->handler("\x01\x00\x01\x00\x70\x00\x00" . self::FRAME)->getInfo()->colorNumber());
    }

    public function testSingleFramesWithADelayAreNotAnimations(): void
    {
        $extension = "!\xf9\x04\x00\x64\x00\x00\x00";

        $handler = $this->handler("\x03\x00\x02\x00\x00\x00\x00" . $extension . self::FRAME);

        $this->assertFalse($handler->getInfo()->isAnimation());
        $this->assertNull($handler->getInfo()->animationFrames());
        $this->assertTrue($handler->supportsTransforms());
    }

    public function testAnimationsAreRecognized(): void
    {
        $loop = "!\xff\x0bNETSCAPE2.0\x03\x01\x04\x00\x00";

        $handler = $this->handler("\x03\x00\x02\x00\x00\x00\x00" . $loop . self::FRAME . self::FRAME . self::FRAME);

        $info = $handler->getInfo();

        $this->assertTrue($info->isAnimation());
        $this->assertSame(3, $info->animationFrames());
        $this->assertSame(5, $info->animationRepeatCount());
        $this->assertFalse($handler->supportsTransforms());
    }

    public function testTransparencyIsReadFromTheGraphicControlExtension(): void
    {
        $opaque = "!\xf9\x04\x00\x00\x00\x00\x00";
        $transparent = "!\xf9\x04\x01\x00\x00\x00\x00";

        $this->assertFalse($this->handler("\x03\x00\x02\x00\x00\x00\x00" . $opaque . self::FRAME)->getInfo()->hasAlphaChannel());
        $this->assertTrue($this->handler("\x03\x00\x02\x00\x00\x00\x00" . $transparent . self::FRAME)->getInfo()->hasAlphaChannel());
    }

    public function testTruncatedImagesAreNotAccepted(): void
    {
        $handler = new GifHandler(substr(GdImages::gif(), 0, 30));

        $this->expectException(UnexpectedValueException::class);

        $handler->getInfo();
    }

    public function testTransparencyIsPreservedWhenEncodingGdImages(): void
    {
        $handler = GifHandler::fromGdImage(GdImages::image(20, 20));

        $image = imagecreatefromstring($handler->getData());

        $this->assertNotFalse($image);
        $this->assertGreaterThanOrEqual(0, imagecolortransparent($image));
        $this->assertSame(127, $this->alphaAt($image, 2, 2));
        $this->assertSame(0, $this->alphaAt($image, 15, 15));
    }

    public function testTransparencyIsPreservedWhenEncodingPaletteImages(): void
    {
        $palette = imagecreatefromstring(GifHandler::fromGdImage(GdImages::image(20, 20))->getData());
        $this->assertNotFalse($palette);
        $this->assertFalse(imageistruecolor($palette));

        $image = imagecreatefromstring(GifHandler::fromGdImage($palette)->getData());

        $this->assertNotFalse($image);
        $this->assertSame(127, $this->alphaAt($image, 2, 2));
        $this->assertSame(0, $this->alphaAt($image, 15, 15));
    }

    public function testTheNumberOfColorsCanBeLimited(): void
    {
        $handler = GifHandler::fromGdImage(GdImages::image(20, 20), ['gifColors' => 4]);

        $this->assertLessThanOrEqual(4, $handler->getInfo()->colorNumber());
    }

    public function testMetadataIsNotSupported(): void
    {
        $handler = new GifHandler(GdImages::gif());

        $this->assertFalse(GifHandler::supportsColorProfile());
        $this->assertFalse(GifHandler::supportsExifData());
        $this->assertFalse($handler->hasColorProfile());
        $this->assertFalse($handler->hasExifData());
    }

    /**
     * @param callable(GifHandler): mixed $operation
     */
    #[DataProvider('unsupportedOperationProvider')]
    public function testMetadataOperationsAreRejected(callable $operation): void
    {
        $this->expectException(UnsupportedFeatureException::class);

        $operation(new GifHandler(GdImages::gif()));
    }

    /**
     * @return iterable<string, array{callable(GifHandler): mixed}>
     */
    public static function unsupportedOperationProvider(): iterable
    {
        $profile = new ColorProfile((string) file_get_contents(self::FIXTURES . '/srgb.icc'));
        $exif = new ExifData((string) file_get_contents(self::FIXTURES . '/exif.tiff'));

        yield 'get color profile' => [static fn(GifHandler $h): ?ColorProfile => $h->getColorProfile()];
        yield 'set color profile' => [static fn(GifHandler $h) => $h->setColorProfile($profile)];
        yield 'remove color profile' => [static fn(GifHandler $h) => $h->removeColorProfile()];
        yield 'get exif data' => [static fn(GifHandler $h): ?ExifData => $h->getExifData()];
        yield 'set exif data' => [static fn(GifHandler $h) => $h->setExifData($exif)];
        yield 'remove exif data' => [static fn(GifHandler $h) => $h->removeExifData()];
    }

    private function alphaAt(GdImage $image, int $x, int $y): int
    {
        return imagecolorsforindex($image, (int) imagecolorat($image, $x, $y))['alpha'];
    }

    /**
     * Create a handler from the data following the GIF header
     */
    private function handler(string $body): GifHandler
    {
        return new GifHandler('GIF89a' . $body . ';');
    }
}
