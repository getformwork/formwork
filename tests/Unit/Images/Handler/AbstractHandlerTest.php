<?php

namespace Formwork\Tests\Unit\Images\Handler;

use Formwork\Images\ColorProfile\ColorProfile;
use Formwork\Images\ColorProfile\ColorSpace;
use Formwork\Images\Handler\AbstractHandler;
use Formwork\Images\Handler\AvifHandler;
use Formwork\Images\Handler\JpegHandler;
use Formwork\Images\Handler\PngHandler;
use Formwork\Images\Handler\WebpHandler;
use Formwork\Images\Transform\TransformCollection;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Images\Fixtures\GdImagesFixture;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(AbstractHandler::class)]
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

    private function colorProfile(string $colorSpace): ColorProfile
    {
        $data = (string) file_get_contents(self::FIXTURES . '/srgb.icc');

        return new ColorProfile(substr_replace($data, $colorSpace, 16, 4));
    }
}
