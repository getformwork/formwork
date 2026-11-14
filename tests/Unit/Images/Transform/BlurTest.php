<?php

namespace Formwork\Tests\Unit\Images\Transform;

use Formwork\Images\ImageInfo;
use Formwork\Images\Transform\Blur;
use Formwork\Images\Transform\BlurMode;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Blur::class)]
final class BlurTest extends TestCase
{
    #[DataProvider('blurModeProvider')]
    public function testEachBlurModeSpreadsPixelsWithItsOwnWeights(BlurMode $blurMode, int $center, int $side, int $corner): void
    {
        // A single white pixel on black shows the weights of the kernel
        $gdImage = imagecreatetruecolor(3, 3);
        imagesetpixel($gdImage, 1, 1, 0xFFFFFF);

        $gdImage = (new Blur(1, $blurMode))->apply($gdImage, new ImageInfo(['hasAlphaChannel' => false]));

        $this->assertSame([$center, $side, $corner], [
            imagecolorat($gdImage, 1, 1) & 0xFF,
            imagecolorat($gdImage, 1, 0) & 0xFF,
            imagecolorat($gdImage, 0, 0) & 0xFF,
        ]);
    }

    /**
     * @return iterable<string, array{BlurMode, int, int, int}>
     */
    public static function blurModeProvider(): iterable
    {
        yield 'smooth' => [BlurMode::Smooth, 128, 16, 16];
        yield 'mean' => [BlurMode::Mean, 28, 28, 28];
        yield 'gaussian' => [BlurMode::Gaussian, 51, 32, 19];
    }
}
