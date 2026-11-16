<?php

namespace Formwork\Tests\Unit\Images\Handler;

use Formwork\Images\ColorProfile\ColorProfile;
use Formwork\Images\ColorProfile\ColorSpace;
use Formwork\Images\Handler\AbstractHandler;
use Formwork\Images\Handler\AvifHandler;
use Formwork\Images\Handler\JpegHandler;
use Formwork\Images\Handler\PngHandler;
use Formwork\Images\Handler\SvgHandler;
use Formwork\Images\Handler\WebpHandler;
use Formwork\Images\Transform\Resize;
use Formwork\Images\Transform\TransformCollection;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Images\Fixtures\GdImagesFixture;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use RuntimeException;
use stdClass;
use UnexpectedValueException;

#[CoversClass(AbstractHandler::class)]
#[UsesClass(Resize::class)]
#[UsesTempDirectory]
final class AbstractHandlerTest extends TestCase
{
    private const string FIXTURES = __DIR__ . '/../Fixtures';

    /**
     * @return iterable<string, array{class-string<AbstractHandler>}>
     */
    public static function targetHandlersProvider(): iterable
    {
        yield 'WebP' => [WebpHandler::class];
        yield 'JPEG' => [JpegHandler::class];
        yield 'AVIF' => [AvifHandler::class];
    }

    /**
     * @param class-string<AbstractHandler> $target
     */
    #[DataProvider('targetHandlersProvider')]
    public function testRgbColorProfilesArePreservedWhenProcessing(string $target): void
    {
        $handler = new PngHandler(GdImagesFixture::png());
        $handler->setColorProfile($this->colorProfile('RGB '));

        $processed = $handler->process(null, $target);

        $this->assertSame(ColorSpace::RGB, $processed->getColorProfile()?->colorSpace());
    }

    /**
     * @param class-string<AbstractHandler> $target
     */
    #[DataProvider('targetHandlersProvider')]
    public function testColorProfilesOfOtherColorSpacesAreDroppedWhenProcessing(string $target): void
    {
        foreach (['CMYK', 'GRAY', 'Lab ', 'ABCD'] as $signature) {
            // A JPEG source avoids libpng warnings about profiles inconsistent with the image
            $handler = new JpegHandler(GdImagesFixture::jpeg());
            $handler->setColorProfile($this->colorProfile($signature));

            // An empty collection forces processing even when the target is the source handler
            $processed = $handler->process(new TransformCollection(), $target);

            $this->assertFalse($processed->hasColorProfile(), $signature);
        }
    }

    public function testDataIsSavedAsItIs(): void
    {
        $data = GdImagesFixture::png();
        $handler = new PngHandler($data);

        $handler->saveAs(TESTS_TMP_PATH . '/image.png');

        $this->assertSame(strlen($data), $handler->getSize());
        $this->assertSame($data, FileSystem::read(TESTS_TMP_PATH . '/image.png'));
    }

    public function testImagesAreNotProcessedWithoutTransformsAndConversions(): void
    {
        $handler = new PngHandler(GdImagesFixture::png());

        $this->assertSame($handler, $handler->process());
    }

    public function testTransformsAreAppliedWhenProcessing(): void
    {
        $transformCollection = new TransformCollection();
        $transformCollection->add(new Resize(20, 15));

        $processed = (new PngHandler(GdImagesFixture::png()))->process($transformCollection);

        $this->assertSame([20, 15], [$processed->getInfo()->width(), $processed->getInfo()->height()]);
    }

    public function testImagesThatDoNotSupportTransformsCannotBeProcessed(): void
    {
        $handler = new SvgHandler('<svg xmlns="http://www.w3.org/2000/svg"/>');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Image handler of type ' . SvgHandler::class . ' does not support transforms for the current image');
        $handler->process();
    }

    public function testImagesCanBeProcessedOnlyWithHandlers(): void
    {
        $handler = new PngHandler(GdImagesFixture::png());

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid handler of type string, only instances of ' . AbstractHandler::class . ' are allowed');
        $handler->process(null, stdClass::class);
    }

    public function testImagesWithInvalidDataCannotBeProcessed(): void
    {
        // The signature and the header chunk are enough to read the image info, but the pixels cannot be decoded
        $header = substr(GdImagesFixture::png(), 0, 33);
        $handler = new PngHandler($header . GdImagesFixture::pngChunk('IDAT', 'Not compressed data') . GdImagesFixture::pngChunk('IEND', ''));

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid image data');
        $handler->process(new TransformCollection());
    }

    private function colorProfile(string $colorSpace): ColorProfile
    {
        $data = (string) file_get_contents(self::FIXTURES . '/srgb.icc');

        return new ColorProfile(substr_replace($data, $colorSpace, 16, 4));
    }
}
