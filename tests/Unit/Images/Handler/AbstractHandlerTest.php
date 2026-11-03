<?php

namespace Formwork\Tests\Unit\Images\Handler;

use Formwork\Images\ColorProfile\ColorProfile;
use Formwork\Images\ColorProfile\ColorSpace;
use Formwork\Images\Handler\AbstractHandler;
use Formwork\Images\Handler\AvifHandler;
use Formwork\Images\Handler\JpegHandler;
use Formwork\Images\Handler\PngHandler;
use Formwork\Images\Handler\WebpHandler;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Images\Fixtures\GdImages;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(AbstractHandler::class)]
final class AbstractHandlerTest extends TestCase
{
    private const string FIXTURES = __DIR__ . '/../Fixtures';

    /**
     * @return iterable<string, array{class-string<AbstractHandler>}>
     */
    public static function targetHandlers(): iterable
    {
        yield 'WebP' => [WebpHandler::class];
        yield 'JPEG' => [JpegHandler::class];
        yield 'AVIF' => [AvifHandler::class];
    }

    /**
     * @param class-string<AbstractHandler> $target
     */
    #[DataProvider('targetHandlers')]
    public function testRgbColorProfilesArePreservedWhenProcessing(string $target): void
    {
        $handler = new PngHandler(GdImages::png());
        $handler->setColorProfile($this->colorProfile('RGB '));

        $processed = $handler->process(null, $target);

        $this->assertSame(ColorSpace::RGB, $processed->getColorProfile()?->colorSpace());
    }

    /**
     * @param class-string<AbstractHandler> $target
     */
    #[DataProvider('targetHandlers')]
    public function testColorProfilesOfOtherColorSpacesAreDroppedWhenProcessing(string $target): void
    {
        foreach (['CMYK', 'GRAY', 'Lab ', 'ABCD'] as $signature) {
            $handler = new PngHandler(GdImages::png());
            $handler->setColorProfile($this->colorProfile($signature));

            $processed = $handler->process(null, $target);

            $this->assertFalse($processed->hasColorProfile(), $signature);
        }
    }

    private function colorProfile(string $colorSpace): ColorProfile
    {
        $data = (string) file_get_contents(self::FIXTURES . '/srgb.icc');

        return new ColorProfile(substr_replace($data, $colorSpace, 16, 4));
    }
}
